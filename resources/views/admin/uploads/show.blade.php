@extends('layouts.admin')

@section('title', 'Upload Details')
@section('page-title', 'Upload Details')
@section('page-subtitle', $upload->file_name)

@section('content')
<div class="space-y-5 pt-2">

    <!-- Header Row -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.uploads.index') }}" class="inline-flex items-center space-x-1 text-sm text-gray-500 hover:text-gray-700">
            <i class="fa-solid fa-chevron-left text-xs"></i><span>Back to Uploads</span>
        </a>
        <form method="POST" action="{{ route('admin.uploads.destroy', $upload->id) }}"
              onsubmit="return confirm('Delete this upload and all its records?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex items-center space-x-1.5 text-red-600 hover:text-red-800 text-sm border border-red-200 hover:bg-red-50 px-3 py-1.5 rounded-lg transition-colors duration-200">
                <i class="fa-solid fa-trash text-xs"></i><span>Delete Upload</span>
            </button>
        </form>
    </div>

    <!-- Status Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-3 mb-1">
                    <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $upload->status_badge_class }}">{{ ucfirst($upload->status) }}</span>
                    <h2 class="text-lg font-semibold text-gray-800">{{ $upload->file_name }}</h2>
                </div>
                <p class="text-sm text-gray-400">Uploaded {{ $upload->created_at->format('M d, Y \a\t H:i') }}</p>
            </div>
            <div class="w-40">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>{{ $upload->processed_rows }}/{{ $upload->total_rows }}</span>
                    <span>{{ $upload->progress_percentage }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="h-2 rounded-full {{ $upload->status === 'completed' ? 'bg-green-500' : ($upload->status === 'failed' ? 'bg-red-500' : 'bg-indigo-500') }}"
                         style="width: {{ $upload->progress_percentage }}%"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-5 border-t border-gray-100">
            <div class="text-center">
                <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Total Products</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-green-600">{{ $stats['synced'] }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Synced to Shopify</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-yellow-500">{{ $stats['pending'] }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Pending</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-red-500">{{ $stats['failed'] }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Failed</p>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    @if($upload->products->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm">Imported Products ({{ $upload->products->count() }})</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Title</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">SKU</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Price</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Shopify ID</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($upload->products as $product)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3.5">
                        <a href="{{ route('admin.products.show', $product->id) }}"
                           class="text-sm font-medium text-gray-800 hover:text-indigo-600">{{ $product->title }}</a>
                    </td>
                    <td class="px-5 py-3.5 text-sm text-gray-500 font-mono">{{ $product->sku ?? '—' }}</td>
                    <td class="px-5 py-3.5 text-sm font-medium text-gray-800">${{ number_format($product->price, 2) }}</td>
                    <td class="px-5 py-3.5">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $product->status_badge_class }}">{{ ucfirst($product->status) }}</span>
                    </td>
                    <td class="px-5 py-3.5 text-xs font-mono text-gray-400">{{ $product->shopify_product_id ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Error Logs -->
    @if($upload->errorLogs->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm">Error Logs ({{ $upload->errorLogs->count() }})</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Row</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Message</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($upload->errorLogs as $log)
                <tr>
                    <td class="px-5 py-3.5 text-xs font-mono text-gray-500">#{{ $log->row_number ?? '—' }}</td>
                    <td class="px-5 py-3.5">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $log->type_badge_class }}">{{ ucfirst($log->type) }}</span>
                    </td>
                    <td class="px-5 py-3.5 text-sm text-gray-700">{{ $log->message }}</td>
                    <td class="px-5 py-3.5 text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>
@endsection
