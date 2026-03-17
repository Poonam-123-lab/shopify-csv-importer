<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use App\Models\Product;
use App\Models\ErrorLog;

class DashboardController extends Controller
{
    public function index()
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $totalUploads      = Upload::count();
        $pendingUploads    = Upload::where('status', 'pending')->orWhere('status', 'processing')->count();
        $completedUploads  = Upload::where('status', 'completed')->count();
        $failedUploads     = Upload::where('status', 'failed')->count();

        $totalProducts     = Product::count();
        $syncedProducts    = Product::where('status', 'synced')->count();
        $pendingProducts   = Product::where('status', 'pending')->count();
        $failedProducts    = Product::where('status', 'failed')->count();

        $totalErrors       = ErrorLog::count();
        $recentErrors      = ErrorLog::with('product')
                                ->orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get();

        $recentUploads     = Upload::orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get();

        $successRate = $totalProducts > 0
            ? round(($syncedProducts / $totalProducts) * 100, 1)
            : 0;

        $uploadsByDay = Upload::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get();

        return view('admin.dashboard', compact(
            'totalUploads', 'pendingUploads', 'completedUploads', 'failedUploads',
            'totalProducts', 'syncedProducts', 'pendingProducts', 'failedProducts',
            'totalErrors', 'recentErrors', 'recentUploads', 'successRate', 'uploadsByDay'
        ));
    }
}