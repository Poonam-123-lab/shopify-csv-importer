<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use App\Models\Product;
use App\Models\ErrorLog;
use App\Models\ActivityLog;

class DashboardController extends Controller
{
    public function index()
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        // Upload stats
        $totalUploads     = Upload::count();
        $pendingUploads   = Upload::whereIn('status', ['pending', 'processing'])->count();
        $completedUploads = Upload::where('status', 'completed')->count();
        $failedUploads    = Upload::where('status', 'failed')->count();

        // Product stats
        $totalProducts   = Product::count();
        $syncedProducts  = Product::where('status', 'synced')->count();
        $pendingProducts = Product::whereIn('status', ['pending', 'processing'])->count();
        $failedProducts  = Product::where('status', 'failed')->count();
        $createdInShopify = Product::where('shopify_action', 'created')->count();
        $updatedInShopify = Product::where('shopify_action', 'updated')->count();

        // Error stats
        $totalErrors = ErrorLog::count();

        // Success rate
        $successRate = $totalProducts > 0
            ? round(($syncedProducts / $totalProducts) * 100, 1)
            : 0;

        // Recent uploads with counts
        $recentUploads = Upload::withCount(['products', 'errorLogs'])
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        // Recent activity logs
        $recentActivity = ActivityLog::with('upload')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // Products per status for chart
        $productStatusData = [
            'synced'     => $syncedProducts,
            'pending'    => $pendingProducts,
            'failed'     => $failedProducts,
            'skipped'    => Product::where('status', 'skipped')->count(),
        ];

        return view('admin.dashboard', compact(
            'totalUploads', 'pendingUploads', 'completedUploads', 'failedUploads',
            'totalProducts', 'syncedProducts', 'pendingProducts', 'failedProducts',
            'createdInShopify', 'updatedInShopify',
            'totalErrors', 'successRate',
            'recentUploads', 'recentActivity', 'productStatusData'
        ));
    }
}