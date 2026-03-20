<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\ProcessCsvImport;
use App\Http\Controllers\Controller;
use App\Models\Upload;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    public function index()
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $uploads = Upload::withCount(['products', 'errorLogs'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.uploads.index', compact('uploads'));
    }

    public function create()
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        return view('admin.uploads.create');
    }

    public function store(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'csv_file'      => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'collection_id' => ['nullable', 'string', 'max:100'],
        ], [
            'csv_file.required' => 'Please select a CSV file to upload.',
            'csv_file.mimes'    => 'Only CSV files are accepted.',
            'csv_file.max'      => 'File size must not exceed 5MB.',
        ]);

        $file     = $request->file('csv_file');
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $filePath = $file->storeAs('csv_uploads', $fileName, 'local');

        $upload = Upload::create([
            'file_name'     => $file->getClientOriginalName(),
            'file_path'     => $filePath,
            'status'        => 'pending',
            'collection_id' => $request->input('collection_id'),
        ]);

        Log::info('CSV file uploaded', [
            'upload_id' => $upload->id,
            'file_name' => $upload->file_name,
            'size'      => $file->getSize(),
        ]);

        ActivityLog::record(
            event: 'file_uploaded',
            message: "CSV file '{$upload->file_name}' uploaded successfully.",
            level: 'info',
            uploadId: $upload->id,
            context: ['file_size' => $file->getSize(), 'collection_id' => $request->input('collection_id')]
        );

        // Dispatch background job
        ProcessCsvImport::dispatch($upload->id, $request->input('collection_id'))
            ->onQueue('csv');

        ActivityLog::record(
            event: 'job_dispatched',
            message: "ProcessCsvImport job dispatched for '{$upload->file_name}'.",
            uploadId: $upload->id
        );

        return redirect()
            ->route('admin.uploads.show', $upload->id)
            ->with('success', 'CSV uploaded! Background processing has started.');
    }

    public function show($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $upload = Upload::with([
            'products'  => fn($q) => $q->orderBy('created_at', 'desc'),
            'errorLogs' => fn($q) => $q->orderBy('created_at', 'desc')->limit(50),
        ])->findOrFail($id);

        $stats = [
            'total'      => $upload->products->count(),
            'synced'     => $upload->products->where('status', 'synced')->count(),
            'pending'    => $upload->products->whereIn('status', ['pending', 'processing'])->count(),
            'failed'     => $upload->products->where('status', 'failed')->count(),
            'created'    => $upload->products->where('shopify_action', 'created')->count(),
            'updated'    => $upload->products->where('shopify_action', 'updated')->count(),
        ];

        $activityLogs = \App\Models\ActivityLog::where('upload_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.uploads.show', compact('upload', 'stats', 'activityLogs'));
    }

    public function destroy($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $upload = Upload::findOrFail($id);

        if (Storage::disk('local')->exists($upload->file_path)) {
            Storage::disk('local')->delete($upload->file_path);
        }

        Log::info('Upload deleted', ['upload_id' => $id, 'file_name' => $upload->file_name]);

        $upload->delete();

        return redirect()
            ->route('admin.uploads.index')
            ->with('success', 'Upload and all associated data deleted.');
    }
}
