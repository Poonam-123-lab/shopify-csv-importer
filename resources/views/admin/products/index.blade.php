@extends('layouts.admin')

@section('title', 'Products')
@section('page-title', 'Imported Products')
@section('page-subtitle', 'All products parsed from CSV uploads')

@section('content')
<div class="space-y-5 pt-2">

    <!-- Filters -->
    <form method="GET" action="{{ route('admin.products.index') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <div class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-48">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by title or SKU..."
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <select name="status"
                    class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                <option value="">All Statuses</option>
                <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                <option value="synced"   {{ request('status') === 'synced'   ? 'selected' : '' }}>Synced</option>
                <option value="failed"   {{ request('status') === 'failed'   ? 'selected' : '' }}>Failed</option>
                <option value="skipped"  {{ request('status') === 'skipped'  ? 'selected' : '' }}>Skipped</option>
            </select>
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg transition-colors duration-200">
                <i class="fa-solid fa-magnifying-glass mr-1"></i>Filter
            </button>
            @if(request('search') || request('status'))
            <a href="{{ route('admin.products.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2 rounded-lg border border-gray-200">
                Clear
            </a>
            @endif
        </div>
    </form>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <p class="text-sm text-gray-500">{{ $products->total() }} product{{ $products->total() !== 1 ? 's' : '' }}</p>
        </div>
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase">Price</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase">SKU</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase">Upload</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-5 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($products as $product)
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="px-5 py-4">
                        <a href="{{ route('admin.products.show', $product->id) }}"
                           class="text-sm font-medium text-gray-800 hover:text-indigo-600">{{ $product->title }}</a>
                        @if($product->vendor)
                            <p class="text-xs text-gray-400">{{ $product->vendor }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-sm font-semibold text-gray-800">${{ number_format($product->price, 2) }}</span>
                        @if($product->compare_at_price)
                            <p class="text-xs text-gray-400 line-through">${{ number_format($product->compare_at_price, 2) }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-xs font-mono text-gray-500">{{ $product->sku ?? '—' }}</td>
                    <td class="px-5 py-4">
                        @if($product->upload)
                        <a href="{{ route('admin.uploads.show', $product->upload->id) }}"
                           class="text-xs text-indigo-600 hover:underline truncate max-w-[140px] block">
                            {{ $product->upload->file_name }}
                        </a>
                        @else
                        <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $product->status_badge_class }}">{{ ucfirst($product->status) }}</span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center justify-end space-x-3">
                            <a href="{{ route('admin.products.show', $product->id) }}"
                               class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">View</a>
                            @if($product->status === 'failed')
                            <form method="POST" action="{{ route('admin.products.retry', $product->id) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-yellow-600 hover:text-yellow-800 text-xs font-medium">Retry</button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('admin.products.destroy', $product->id) }}"
                                  onsubmit="return confirm('Delete this product?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-16 text-center">
                        <i class="fa-solid fa-boxes-stacked text-gray-300 text-5xl mb-3"></i>
                        <p class="text-gray-400 font-medium">No products found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($products->hasPages())
    <div>{{ $products->links() }}</div>
    @endif
</div>
@endsection
