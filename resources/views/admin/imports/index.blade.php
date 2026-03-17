@extends('layouts.admin')
@section('title', 'Import Batches')
@section('page-title', 'Import Batches')

@section('content')
<div class="py-6 space-y-5">

    <!-- Header bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <p class="text-gray-500 text-sm">{{ $batches->total() }} total import batches</p>
        <a href="{{ route('admin.imports.create') }}" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors shadow">
            <i class="fas fa-plus"></i> Upload New CSV
        </a>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Batch Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">File</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Uploaded</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($batches as $batch)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-800 text-sm">{{ $batch->name }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">by {{ $batch->uploaded_by }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600 font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{ $batch->file_name }}</span>
                        </td>
                        <td class="px-6 py-4 min-w-[180px]">
                            <div class="flex items-center gap-3">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all duration-500
                                        @if($batch->status === 'completed') bg-green-500
                                        @elseif($batch->status === 'processing') bg-blue-500
                                        @elseif($batch->status === 'failed') bg-red-500
                                        @else bg-yellow-400 @endif"
                                        style="width: {{ $batch->progress }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 w-10 text-right">{{ $batch->progress }}%</span>
                            </div>
                            <div class="text-xs text-gray-400 mt-1">{{ $batch->success_count }} ok &bull; {{ $batch->failed_count }} failed &bull; {{ $batch->total_rows }} total</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold
                                @if($batch->status === 'completed') bg-green-100 text-green-700
                                @elseif($batch->status === 'processing') bg-blue-100 text-blue-700 animate-pulse
                                @elseif($batch->status === 'pending') bg-yellow-100 text-yellow-700
                                @else bg-red-100 text-red-700 @endif">
                                @if($batch->status === 'processing')<i class="fas fa-spinner fa-spin text-xs"></i>@endif
                                {{ ucfirst($batch->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $batch->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.imports.show', $batch->id) }}" class="text-violet-600 hover:text-violet-800 text-sm font-medium hover:underline">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                @if(in_array($batch->status, ['failed', 'completed']) && $batch->failed_count > 0)
                                <form action="{{ route('admin.imports.retry', $batch->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-orange-500 hover:text-orange-700 text-sm font-medium hover:underline">
                                        <i class="fas fa-redo"></i> Retry
                                    </button>
                                </form>
                                @endif
                                @if(!in_array($batch->status, ['processing', 'pending']))
                                <form action="{{ route('admin.imports.destroy', $batch->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this import batch and all its records?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium hover:underline">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="text-gray-400">
                                <i class="fas fa-file-csv text-5xl mb-3 block text-gray-300"></i>
                                <p class="text-lg font-medium text-gray-500">No import batches yet</p>
                                <p class="text-sm">Upload your first CSV file to get started</p>
                                <a href="{{ route('admin.imports.create') }}" class="inline-block mt-4 bg-violet-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-violet-700 transition-colors">
                                    <i class="fas fa-upload mr-1"></i> Upload CSV
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($batches->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $batches->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
