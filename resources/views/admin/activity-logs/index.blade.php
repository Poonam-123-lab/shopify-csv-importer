@extends('layouts.admin')

@section('title', 'Activity Logs')
@section('page-title', 'Activity Logs')
@section('page-subtitle', 'System events: file uploads, job processing, API requests')

@section('content')
<div class="space-y-5 pt-2">

    <!-- Level Summary -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach($levelCounts as $level => $count)
        <a href="{{ route('admin.activity-logs.index') }}?level={{ $level }}"
           class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 hover:border-indigo-200 transition-colors {{ request('level') === $level ? 'ring-2 ring-indigo-500' : '' }}">
            <p class="text-2xl font-bold text-gray-800">{{ number_format($count) }}</p>
            <p class="text-xs text-gray-500 mt-0.5 capitalize">{{ $level }} events</p>
        </a>
        @endforeach
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-3">
        <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="flex flex-wrap gap-3 flex-1">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search messages..."
                   class="flex-1 min-w-48 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
            <select name="level" class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                <option value="">All Levels</option>
                @foreach(['info','warning','error','debug'] as $lvl)
                <option value="{{ $lvl }}" {{ request('level') === $lvl ? 'selected' : '' }}>{{ ucfirst($lvl) }}</option>
                @endforeach
            </select>
            <select name="event" class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                <option value="">All Events</option>
                @foreach($events as $evt)
                <option value="{{ $evt }}" {{ request('event') === $evt ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($evt)) }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg transition-colors">
                <i class="fa-solid fa-magnifying-glass mr-1"></i>Filter
            </button>
            @if(request()->hasAny(['search','level','event']))
            <a href="{{ route('admin.activity-logs.index') }}" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2 border border-gray-200 rounded-lg">Clear</a>
            @endif
        </form>
        @if($logs->total() > 0)
        <form method="POST" action="{{ route('admin.activity-logs.clear') }}" onsubmit="return confirm('Clear all activity logs?')">
            @csrf
            <button type="submit" class="text-red-600 hover:text-red-800 text-sm border border-red-200 hover:bg-red-50 px-4 py-2 rounded-lg transition-colors">
                <i class="fa-solid fa-broom mr-1"></i>Clear All
            </button>
        </form>
        @endif
    </div>

    <!-- Log Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <p class="text-sm text-gray-500">{{ number_format($logs->total()) }} log{{ $logs->total() !== 1 ? 's' : '' }}</p>
        </div>
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase">Time</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase">Level</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase">Event</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase">Message</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase">Upload</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3.5 text-xs text-gray-400 whitespace-nowrap">{{ $log->created_at->format('M d H:i:s') }}</td>
                    <td class="px-5 py-3.5">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $log->level_badge_class }}">{{ ucfirst($log->level) }}</span>
                    </td>
                    <td class="px-5 py-3.5">
                        <code class="text-xs bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded">{{ $log->event }}</code>
                    </td>
                    <td class="px-5 py-3.5">
                        <p class="text-sm text-gray-700">{{ $log->message }}</p>
                        @if($log->context)
                        <details class="mt-1">
                            <summary class="text-xs text-indigo-500 cursor-pointer hover:underline">View context</summary>
                            <pre class="text-xs bg-gray-50 rounded p-2 mt-1 overflow-x-auto max-w-sm">{{ json_encode($log->context, JSON_PRETTY_PRINT) }}</pre>
                        </details>
                        @endif
                    </td>
                    <td class="px-5 py-3.5">
                        @if($log->upload)
                        <a href="{{ route('admin.uploads.show', $log->upload->id) }}"
                           class="text-xs text-indigo-600 hover:underline truncate max-w-[120px] block">{{ $log->upload->file_name }}</a>
                        @else
                        <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-16 text-center">
                        <i class="fa-solid fa-scroll text-gray-300 text-5xl mb-3"></i>
                        <p class="text-gray-400 font-medium">No activity logs yet</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div>{{ $logs->links() }}</div>
    @endif
</div>
@endsection
