<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $query = ActivityLog::with(['upload', 'product']);

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('search')) {
            $query->where('message', 'like', '%' . $request->search . '%');
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(30)->withQueryString();

        $levelCounts = [
            'info'    => ActivityLog::where('level', 'info')->count(),
            'warning' => ActivityLog::where('level', 'warning')->count(),
            'error'   => ActivityLog::where('level', 'error')->count(),
            'debug'   => ActivityLog::where('level', 'debug')->count(),
        ];

        $events = ActivityLog::select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        return view('admin.activity-logs.index', compact('logs', 'levelCounts', 'events'));
    }

    public function clearAll()
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        ActivityLog::truncate();

        return redirect()
            ->route('admin.activity-logs.index')
            ->with('success', 'All activity logs cleared.');
    }
}