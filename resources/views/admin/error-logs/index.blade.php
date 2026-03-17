@extends('layouts.admin')

@section('title', 'Error Logs')
@section('page-title', 'Error Logs')
@section('page-subtitle', 'All validation, Shopify sync, and system errors')

@section('content')
<div class="space-y-5 pt-2">

    <!-- Type Summary -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @php $typeColors = ['validation' => 'orange', 'shopify' => 'purple', 'parsing' => 'yellow', 'system' => 'red']; @endphp
        @foreach($typeCounts as $type => $count)
        <a href="{{ route('admin.error-logs.index') }}?type={{ $type }}"
           class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 hover:border-indigo-200 transition-colors duration-200 {{ request('type') === $type ? 'ring-2 ring-indigo-500' : '' }}">
            <p class="text-2xl font-bold text-gray-800">{{ $count }}</p>
            <p class="text-xs text-gray-500 mt-0.5 capitalize">{{ $type }} errors</p>
        </a>
        @endforeach
    </div>

    <!-- Filters + Actions -->
    <div class="flex flex-wrap items-center gap-3">
        <form method="GET" action="{{ route('admin.error-logs.index') }}" class="flex flex-wrap gap-3 flex-1">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search error messages..."
                   class="flex-1 min-w-48 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
            <select name="type"
                    class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                <option value="">All Types</option>
                <option value="validation" {{ request('type') === 'validation' ? 'selected' : '' }}>Validation</option>
                <option value="shopify"    {{ request('type') === 'shopify'    ? 'selected' : '' }}>Shopify</option>
                <option value="parsing"    {{ request('type') === 'parsing'    ? 'selected' : '' }}>Parsing</option>
                <option value="system"     {{ request('type') === 'system'     ? 'selected' : '' }}>System</option>
            </select>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg transition-colors duration-200">
                <i class="fa-solid fa-magnifying-glass mr-1"></i>Filter
            </button>
            @if(request()->hasAny(['search', 'type']))
            <a href="{{ route('admin.error-logs.index') }}" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2 border border-gray-200 rounded-lg">Clear</a>
            @endif
        </form>

        @if($logs->total() > 0)
        <form method="POST" action="{{ route('admin.error-logs.clear') }}"
              onsubmit="return confirm('Clear all error logs? This cannot be undone.')">
            @csrf
            <button type="submit" class="text-red-600 hover:text-red-800 text-sm border border-red-200 hover:bg-red-50 px-4 py-2 rounded-lg transition-colors duration-200">
                <i class="fa-solid fa-broom mr-1"></i>Clear All
            </button>
        </form>
        @endif
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <p class="text-sm text-gray-500">{{ $logs->total() }} log{{ $logs->total() !== 1 ? 's' : '' }}</p>
        </div>
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase">Row</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase">Message</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase">Upload</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase">Time</th>
                    <th class="px-5 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-4 text-xs font-mono text-gray-500">
                        {{ $log->row_number ? '#' . $log->row_number : '—' }}
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $log->type_badge_class }}">{{ ucfirst($log->type) }}</span>
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-sm text-gray-700 max-w-sm">{{ $log->message }}</p>
                        @if($log->raw_data)
                        <details class="mt-1">
                            <summary class="text-xs text-indigo-500 cursor-pointer hover:underline">View raw data</summary>
                            <pre class="text-xs bg-gray-50 rounded p-2 mt-1 overflow-x-auto">{{ json_encode(json_decode($log->raw_data), JSON_PRETTY_PRINT) }}</pre>
                        </details>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        @if($log->upload)
                        <a href="{{ route('admin.uploads.show', $log->upload->id) }}"
                           class="text-xs text-indigo-600 hover:underline truncate max-w-[120px] block">{{ $log->upload->file_name }}</a>
                        @else
                        <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</td>
                    <td class="px-5 py-4">
                        <form method="POST" action="{{ route('admin.error-logs.destroy', $log->id) }}" class="flex justify-end">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-400 hover:text-red-600 text-xs">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-16 text-center">
                        <i class="fa-solid fa-circle-check text-green-300 text-5xl mb-3"></i>
                        <p class="text-gray-400 font-medium">No errors found</p>
                        <p class="text-gray-400 text-sm mt-1">Your imports are running cleanly!</p>
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
