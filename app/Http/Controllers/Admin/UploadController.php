<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use App\Jobs\ProcessCsvImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'csv_file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:5120', // 5MB
            ],
        ], [
            'csv_file.required' => 'Please select a CSV file to upload.',
            'csv_file.mimes'    => 'Only CSV files are allowed.',
            'csv_file.max'      => 'File size must not exceed 5MB.',
        ]);

        $file     = $request->file('csv_file');
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $filePath = $file->storeAs('csv_uploads', $fileName, 'local');

        $upload = Upload::create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'status'    => 'pending',
        ]);

        // Dispatch async job
        ProcessCsvImport::dispatch($upload->id);

        return redirect()
            ->route('admin.uploads.show', $upload->id)
            ->with('success', 'CSV file uploaded successfully! Processing has started in the background.');
    }

    public function show($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $upload = Upload::with([
            'products',
            'errorLogs' => fn($q) => $q->orderBy('created_at', 'desc'),
        ])->findOrFail($id);

        $stats = [
            'total'   => $upload->products->count(),
            'synced'  => $upload->products->where('status', 'synced')->count(),
            'pending' => $upload->products->where('status', 'pending')->count(),
            'failed'  => $upload->products->where('status', 'failed')->count(),
        ];

        return view('admin.uploads.show', compact('upload', 'stats'));
    }

    public function destroy($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $upload = Upload::findOrFail($id);

        // Delete physical file
        if (Storage::disk('local')->exists($upload->file_path)) {
            Storage::disk('local')->delete($upload->file_path);
        }

        $upload->delete();

        return redirect()
            ->route('admin.uploads.index')
            ->with('success', 'Upload and all associated records deleted successfully.');
    }
}