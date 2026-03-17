<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\ImportJob;
use App\Jobs\ProcessCsvImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ImportController extends Controller
{
    public function index()
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $batches = ImportBatch::orderBy('created_at', 'desc')->paginate(15);
        return view('admin.imports.index', compact('batches'));
    }

    public function create()
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }
        return view('admin.imports.create');
    }

    public function store(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'csv_file'    => 'required|file|mimes:csv,txt|max:51200',
            'batch_name'  => 'nullable|string|max:255',
        ]);

        $file     = $request->file('csv_file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('csv_imports', $fileName, 'local');

        // Parse CSV to get row count
        $fullPath = storage_path('app/' . $filePath);
        $csv      = Reader::createFromPath($fullPath, 'r');
        $csv->setHeaderOffset(0);
        $records    = $csv->getRecords();
        $totalRows  = iterator_count($records);

        $batch = ImportBatch::create([
            'name'        => $request->batch_name ?? $file->getClientOriginalName(),
            'file_name'   => $file->getClientOriginalName(),
            'file_path'   => $filePath,
            'total_rows'  => $totalRows,
            'status'      => 'pending',
            'uploaded_by' => session('admin_email'),
        ]);

        // Dispatch the queue job
        ProcessCsvImport::dispatch($batch->id);

        return redirect()->route('admin.imports.show', $batch->id)
            ->with('success', 'CSV file uploaded successfully. Import is being processed in the background.');
    }

    public function show($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $batch = ImportBatch::findOrFail($id);
        $jobs  = ImportJob::where('import_batch_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $successCount    = ImportJob::where('import_batch_id', $id)->where('status', 'success')->count();
        $failedCount     = ImportJob::where('import_batch_id', $id)->where('status', 'failed')->count();
        $pendingCount    = ImportJob::where('import_batch_id', $id)->where('status', 'pending')->count();
        $processingCount = ImportJob::where('import_batch_id', $id)->where('status', 'processing')->count();

        return view('admin.imports.show', compact('batch', 'jobs', 'successCount', 'failedCount', 'pendingCount', 'processingCount'));
    }

    public function status($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $batch = ImportBatch::findOrFail($id);
        $progressPercent = $batch->total_rows > 0
            ? round((($batch->processed_rows) / $batch->total_rows) * 100, 1)
            : 0;

        return response()->json([
            'status'          => $batch->status,
            'total_rows'      => $batch->total_rows,
            'processed_rows'  => $batch->processed_rows,
            'success_count'   => $batch->success_count,
            'failed_count'    => $batch->failed_count,
            'progress'        => $progressPercent,
        ]);
    }

    public function retry($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $batch = ImportBatch::findOrFail($id);

        if (!in_array($batch->status, ['failed', 'completed'])) {
            return back()->withErrors(['error' => 'Only failed or completed batches can be retried.']);
        }

        // Reset failed jobs to pending
        ImportJob::where('import_batch_id', $id)
            ->where('status', 'failed')
            ->update(['status' => 'pending', 'error_message' => null, 'shopify_product_id' => null]);

        $failedCount = ImportJob::where('import_batch_id', $id)->where('status', 'failed')->count();

        $batch->update([
            'status'          => 'processing',
            'failed_count'    => 0,
        ]);

        ProcessCsvImport::dispatch($batch->id, true);

        return redirect()->route('admin.imports.show', $batch->id)
            ->with('success', 'Retry initiated for failed products.');
    }

    public function destroy($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $batch = ImportBatch::findOrFail($id);

        if (in_array($batch->status, ['processing', 'pending'])) {
            return back()->withErrors(['error' => 'Cannot delete a batch that is currently processing.']);
        }

        ImportJob::where('import_batch_id', $id)->delete();
        Storage::disk('local')->delete($batch->file_path);
        $batch->delete();

        return redirect()->route('admin.imports.index')->with('success', 'Import batch deleted successfully.');
    }
}