@extends('layouts.admin')
@section('title', 'Import Details')
@section('page-title', 'Import Details')

@section('content')
<div class="py-6 space-y-6">

    <!-- Batch Overview -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $batch->name }}</h2>
                <p class="text-gray-500 text-sm mt-0.5">{{ $batch->file_name }} &bull; Uploaded by {{ $batch->uploaded_by }} &bull; {{ $batch->created_at->format('M d, Y H:i') }}</p>
            </div>
            <div class="flex items-center gap-3">
                @if(in_array($batch->status, ['failed', 'completed']) && $batch->failed_count > 0)
                <form action="{{ route('admin.imports.retry', $batch->id) }}" method="POST">
                    @csrf
                    <button class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors flex items-center gap-2">
                        <i class="fas fa-redo"></i> Retry Failed ({{ $batch->failed_count }})
                    </button>
                </form>
                @endif
                <span class="px-3 py-1.5 rounded-full text-sm font-semibold
                    @if($batch->status === 'completed') bg-green-100 text-green-700
                    @elseif($batch->status === 'processing') bg-blue-100 text-blue-700
                    @elseif($batch->status === 'pending') bg-yellow-100 text-yellow-700
                    @else bg-red-100 text-red-700 @endif">
                    @if($batch->status === 'processing')<i class="fas fa-spinner fa-spin mr-1"></i>@endif
                    {{ ucfirst($batch->status) }}
                </span>
            </div>
        </div>

        <!-- Progress & Stats -->
        <div class="px-6 py-5">
            <div class="mb-4">
                <div class="flex justify-between text-sm text-gray-600 mb-1.5">
                    <span>Overall Progress</span>
                    <span id="progress-percent">{{ $batch->progress }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div id="progress-bar" class="h-3 rounded-full transition-all duration-500
                        @if($batch->status === 'completed') bg-green-500
                        @elseif($batch->status === 'failed') bg-red-500
                        @else bg-violet-500 @endif" style="width: {{ $batch->progress }}%"></div>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-800">{{ $batch->total_rows }}</p>
                    <p class="text-xs text-gray-500 mt-1">Total Rows</p>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-green-600" id="success-count">{{ $successCount }}</p>
                    <p class="text-xs text-green-600 mt-1">Successful</p>
                </div>
                <div class="bg-red-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-red-500" id="failed-count">{{ $failedCount }}</p>
                    <p class="text-xs text-red-500 mt-1">Failed</p>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-yellow-600" id="pending-count">{{ $pendingCount + $processingCount }}</p>
                    <p class="text-xs text-yellow-600 mt-1">Pending/Processing</p>
                </div>
            </div>

            @if($batch->error_log)
            <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-red-700 text-sm font-semibold flex items-center gap-2"><i class="fas fa-exclamation-triangle"></i> Error Log</p>
                <p class="text-red-600 text-sm mt-1">{{ $batch->error_log }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Product Jobs Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800"><i class="fas fa-list text-violet-500 mr-2"></i>Product Import Jobs</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Row</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Product Title</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Shopify ID</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Attempts</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Processed</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Error</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($jobs as $job)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 text-sm text-gray-500">#{{ $job->row_number }}</td>
                        <td class="px-6 py-3">
                            <span class="text-sm font-medium text-gray-800">{{ $job->product_title }}</span>
                        </td>
                        <td class="px-6 py-3">
                            @if($job->shopify_product_id)
                                <a href="https://laravel-import-test.myshopify.com/admin/products/{{ $job->shopify_product_id }}" target="_blank" class="text-violet-600 hover:underline text-xs font-mono">
                                    {{ $job->shopify_product_id }} <i class="fas fa-external-link-alt text-xs"></i>
                                </a>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-3">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold
                                @if($job->status === 'success') bg-green-100 text-green-700
                                @elseif($job->status === 'failed') bg-red-100 text-red-700
                                @elseif($job->status === 'processing') bg-blue-100 text-blue-700
                                @else bg-yellow-100 text-yellow-700 @endif">
                                {{ ucfirst($job->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-500">{{ $job->attempts }}</td>
                        <td class="px-6 py-3 text-xs text-gray-400">{{ $job->processed_at ? $job->processed_at->format('M d H:i:s') : '—' }}</td>
                        <td class="px-6 py-3 text-right">
                            @if($job->error_message)
                                <span class="text-xs text-red-500 max-w-xs block truncate" title="{{ $job->error_message }}">{{ $job->error_message }}</span>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-10 text-center text-gray-400 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i>Products are being processed...</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($jobs->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">{{ $jobs->links() }}</div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
// Auto-refresh progress for active batches
@if(in_array($batch->status, ['pending', 'processing']))
const batchId = {{ $batch->id }};
const statusUrl = '{{ route('admin.imports.status', $batch->id) }}';

const refreshProgress = () => {
    fetch(statusUrl)
        .then(r => r.json())
        .then(data => {
            document.getElementById('progress-percent').textContent = data.progress + '%';
            document.getElementById('progress-bar').style.width = data.progress + '%';
            document.getElementById('success-count').textContent = data.success_count;
            document.getElementById('failed-count').textContent = data.failed_count;
            document.getElementById('pending-count').textContent = data.total_rows - data.processed_rows;

            if (!['pending', 'processing'].includes(data.status)) {
                clearInterval(intervalId);
                setTimeout(() => window.location.reload(), 1500);
            }
        })
        .catch(console.error);
};

const intervalId = setInterval(refreshProgress, 3000);
@endif
</script>
@endpush
