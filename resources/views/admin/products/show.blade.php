@extends('layouts.admin')

@section('title', $product->title)
@section('page-title', $product->title)
@section('page-subtitle', 'Product detail and Shopify sync status')

@section('content')
<div class="space-y-5 pt-2 max-w-3xl">
    <a href="{{ route('admin.products.index') }}" class="inline-flex items-center space-x-1 text-sm text-gray-500 hover:text-gray-700">
        <i class="fa-solid fa-chevron-left text-xs"></i><span>Back to Products</span>
    </a>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between mb-5">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">{{ $product->title }}</h2>
                <p class="text-gray-400 text-sm mt-0.5">
                    Imported from: <a href="{{ route('admin.uploads.show', $product->upload->id) }}" class="text-indigo-600 hover:underline">{{ $product->upload->file_name }}</a>
                </p>
            </div>
            <span class="text-xs px-3 py-1.5 rounded-full font-semibold {{ $product->status_badge_class }}">{{ ucfirst($product->status) }}</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Price</p>
                <p class="text-lg font-bold text-gray-800">${{ number_format($product->price, 2) }}</p>
                @if($product->compare_at_price)
                    <p class="text-xs text-gray-400 line-through">${{ number_format($product->compare_at_price, 2) }}</p>
                @endif
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">SKU</p>
                <p class="text-sm font-mono text-gray-700">{{ $product->sku ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Vendor</p>
                <p class="text-sm text-gray-700">{{ $product->vendor ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Product Type</p>
                <p class="text-sm text-gray-700">{{ $product->product_type ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Inventory</p>
                <p class="text-sm text-gray-700">{{ $product->inventory_quantity }} units</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Shopify ID</p>
                @if($product->shopify_product_id)
                    <a href="{{ $product->shopify_url }}" target="_blank" class="text-sm font-mono text-indigo-600 hover:underline">{{ $product->shopify_product_id }}</a>
                @else
                    <p class="text-sm text-gray-400">Not synced</p>
                @endif
            </div>
        </div>

        @if($product->description)
        <div class="mt-5 pt-5 border-t border-gray-100">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-2">Description</p>
            <p class="text-sm text-gray-700 leading-relaxed">{{ $product->description }}</p>
        </div>
        @endif

        @if($product->tags)
        <div class="mt-5 pt-5 border-t border-gray-100">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-2">Tags</p>
            <div class="flex flex-wrap gap-2">
                @foreach(explode(',', $product->tags) as $tag)
                <span class="text-xs bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full">{{ trim($tag) }}</span>
                @endforeach
            </div>
        </div>
        @endif

        <div class="flex items-center space-x-3 mt-6 pt-5 border-t border-gray-100">
            @if($product->status === 'failed')
            <form method="POST" action="{{ route('admin.products.retry', $product->id) }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center space-x-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm px-4 py-2 rounded-lg transition-colors duration-200">
                    <i class="fa-solid fa-rotate"></i><span>Retry Shopify Sync</span>
                </button>
            </form>
            @endif
            <form method="POST" action="{{ route('admin.products.destroy', $product->id) }}"
                  onsubmit="return confirm('Delete this product?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center space-x-2 text-red-600 hover:text-red-800 text-sm border border-red-200 hover:bg-red-50 px-4 py-2 rounded-lg transition-colors duration-200">
                    <i class="fa-solid fa-trash"></i><span>Delete Product</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Product Error Logs -->
    @if($product->errorLogs->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-800">Error History ({{ $product->errorLogs->count() }})</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($product->errorLogs as $log)
            <div class="px-5 py-3.5 flex items-start space-x-3">
                <span class="text-xs px-2 py-0.5 rounded-full {{ $log->type_badge_class }} mt-0.5">{{ ucfirst($log->type) }}</span>
                <div class="flex-1">
                    <p class="text-sm text-gray-700">{{ $log->message }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $log->created_at->format('M d, Y H:i') }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
