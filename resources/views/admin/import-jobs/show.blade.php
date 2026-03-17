@extends('layouts.admin')
@section('title', 'Job Details')
@section('page-title', 'Import Job Details')

@section('content')
<div class="py-6">
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-xl text-gray-800">{{ $job->product_title }}</h2>
                <p class="text-gray-500 text-sm">Row #{{ $job->row_number }} &bull; Batch: <a href="{{ route('admin.imports.show', $job->import_batch_id) }}" class="text-violet-600 hover:underline">{{ $job->importBatch->name }}</a></p>
            </div>
            <span class="px-3 py-1.5 rounded-full text-sm font-semibold
                @if($job->status === 'success') bg-green-100 text-green-700
                @elseif($job->status === 'failed') bg-red-100 text-red-700
                @elseif($job->status === 'processing') bg-blue-100 text-blue-700
                @else bg-yellow-100 text-yellow-700 @endif">
                {{ ucfirst($job->status) }}
            </span>
        </div>
        <div class="p-6 space-y-4">
            @if($job->shopify_product_id)
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-green-700 text-sm font-semibold"><i class="fas fa-check-circle mr-2"></i>Successfully imported to Shopify</p>
                <p class="text-green-600 text-sm mt-1">Product ID: <a href="https://laravel-import-test.myshopify.com/admin/products/{{ $job->shopify_product_id }}" target="_blank" class="font-mono underline">{{ $job->shopify_product_id }}</a></p>
            </div>
            @endif
            @if($job->error_message)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-red-700 text-sm font-semibold"><i class="fas fa-exclamation-triangle mr-2"></i>Error Details</p>
                <p class="text-red-600 text-sm mt-1 font-mono">{{ $job->error_message }}</p>
            </div>
            @endif

            <div>
                <h3 class="font-semibold text-gray-700 mb-3 text-sm uppercase tracking-wider">Product Data</h3>
                <div class="bg-gray-50 rounded-lg overflow-hidden border border-gray-200">
                    @foreach($job->product_data as $key => $value)
                    <div class="flex border-b border-gray-100 last:border-0">
                        <div class="w-40 flex-shrink-0 px-4 py-2.5 bg-gray-100 text-xs font-semibold text-gray-600 font-mono">{{ $key }}</div>
                        <div class="px-4 py-2.5 text-sm text-gray-700 break-all">{{ $value ?: '—' }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Attempts:</span> <span class="font-medium">{{ $job->attempts }}</span></div>
                <div><span class="text-gray-500">Processed:</span> <span class="font-medium">{{ $job->processed_at ? $job->processed_at->format('M d, Y H:i:s') : 'Not yet' }}</span></div>
                <div><span class="text-gray-500">Created:</span> <span class="font-medium">{{ $job->created_at->format('M d, Y H:i:s') }}</span></div>
            </div>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
            <a href="{{ route('admin.imports.show', $job->import_batch_id) }}" class="text-violet-600 hover:underline text-sm font-medium flex items-center gap-1">
                <i class="fas fa-arrow-left text-xs"></i> Back to Batch
            </a>
        </div>
    </div>
</div>
</div>
@endsection
