<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use Illuminate\Http\Request;

class ErrorLogController extends Controller
{
    public function index(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $query = ErrorLog::with(['product', 'upload']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where('message', 'like', '%' . $request->search . '%');
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

        $typeCounts = [
            'validation' => ErrorLog::where('type', 'validation')->count(),
            'shopify'    => ErrorLog::where('type', 'shopify')->count(),
            'parsing'    => ErrorLog::where('type', 'parsing')->count(),
            'system'     => ErrorLog::where('type', 'system')->count(),
        ];

        return view('admin.error-logs.index', compact('logs', 'typeCounts'));
    }

    public function destroy($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        ErrorLog::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Error log deleted.');
    }

    public function clearAll()
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        ErrorLog::truncate();

        return redirect()
            ->route('admin.error-logs.index')
            ->with('success', 'All error logs cleared successfully.');
    }
}