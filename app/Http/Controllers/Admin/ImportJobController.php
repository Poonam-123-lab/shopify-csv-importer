<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportJob;

class ImportJobController extends Controller
{
    public function show($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $job = ImportJob::with('importBatch')->findOrFail($id);
        return view('admin.import-jobs.show', compact('job'));
    }
}