@extends('layouts.admin')

@section('title', 'CSV Uploads')
@section('page-title', 'CSV Uploads')
@section('page-subtitle', 'Manage and monitor your CSV import files')

@section('content')
<div class="space-y-5 pt-2">
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">{{ $uploads->total() }} total upload{{ $uploads->total() !== 1 ? 's' : '' }}</p>
        <a href="{{ route('admin.uploads.create') }}"
           class="inline-flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg transition-all duration-200">
            <i class="fa-solid fa-upload"></i><span>Upload CSV</span>
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">File Name</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Progress</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Products</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Errors</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Uploaded</th>
                    <th class="px-5 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($uploads as $upload)
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="px-5 py-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-file-csv text-indigo-400 text-sm"></i>
                            </div>
                            <div>
                                <a href="{{ route('admin.uploads.show', $upload->id) }}"
                                   class="text-sm font-medium text-gray-800 hover:text-indigo-600">
                                    {{ $upload->file_name }}
                                </a>
                                <p class="text-xs text-gray-400">{{ $upload->total_rows }} rows total</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $upload->status_badge_class }}">
                            {{ ucfirst($upload->status) }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="w-32">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>{{ $upload->processed_rows }}/{{ $upload->total_rows }}</span>
                                <span>{{ $upload->progress_percentage }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full {{ $upload->status === 'completed' ? 'bg-green-500' : 'bg-indigo-500' }}"
                                     style="width: {{ $upload->progress_percentage }}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $upload->products_count }}</td>
                    <td class="px-5 py-4">
                        @if($upload->error_logs_count > 0)
                            <span class="text-xs text-red-600 font-medium">{{ $upload->error_logs_count }} errors</span>
                        @else
                            <span class="text-xs text-gray-400">None</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-xs text-gray-400">{{ $upload->created_at->format('M d, Y H:i') }}</td>
                    <td class="px-5 py-4">
                        <div class="flex items-center justify-end space-x-2">
                            <a href="{{ route('admin.uploads.show', $upload->id) }}"
                               class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">View</a>
                            <form method="POST" action="{{ route('admin.uploads.destroy', $upload->id) }}"
                                  onsubmit="return confirm('Delete this upload and all its products?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-16 text-center">
                        <i class="fa-solid fa-file-csv text-gray-300 text-5xl mb-3"></i>
                        <p class="text-gray-400 font-medium">No uploads yet</p>
                        <p class="text-gray-400 text-sm mt-1">Upload your first CSV file to get started.</p>
                        <a href="{{ route('admin.uploads.create') }}"
                           class="inline-block mt-4 bg-indigo-600 text-white text-sm px-5 py-2 rounded-lg hover:bg-indigo-700">Upload CSV</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($uploads->hasPages())
    <div>{{ $uploads->links() }}</div>
    @endif
</div>
@endsection
