@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Overview of your Shopify CSV import operations')

@section('content')
<div class="space-y-6 pt-2">

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        <!-- Total Uploads -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Total Uploads</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalUploads }}</p>
                    <p class="text-xs text-gray-400 mt-1">
                        <span class="text-yellow-600 font-medium">{{ $pendingUploads }} active</span> · {{ $completedUploads }} done
                    </p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-file-csv text-indigo-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Products -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Products Processed</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalProducts }}</p>
                    <p class="text-xs text-gray-400 mt-1">
                        <span class="text-green-600 font-medium">{{ $syncedProducts }} synced</span> · {{ $pendingProducts }} pending
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-boxes-stacked text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Sync Success Rate</p>
                    <p class="text-3xl font-bold mt-1 {{ $successRate >= 80 ? 'text-green-600' : ($successRate >= 50 ? 'text-yellow-600' : 'text-red-600') }}">{{ $successRate }}%</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $failedProducts }} products failed</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-chart-line text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Error Logs -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Error Logs</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalErrors }}</p>
                    <p class="text-xs text-gray-400 mt-1">Across all import operations</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-triangle-exclamation text-red-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        <!-- Recent Uploads -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800 text-sm">Recent Uploads</h2>
                <a href="{{ route('admin.uploads.index') }}" class="text-xs text-indigo-600 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentUploads as $upload)
                <div class="flex items-center justify-between px-5 py-3.5">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-file-csv text-indigo-400 text-sm"></i>
                        </div>
                        <div>
                            <a href="{{ route('admin.uploads.show', $upload->id) }}"
                               class="text-sm font-medium text-gray-800 hover:text-indigo-600 truncate max-w-[180px] block">
                                {{ $upload->file_name }}
                            </a>
                            <p class="text-xs text-gray-400">{{ $upload->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $upload->status_badge_class }}">
                        {{ ucfirst($upload->status) }}
                    </span>
                </div>
                @empty
                <div class="px-5 py-8 text-center">
                    <i class="fa-solid fa-inbox text-gray-300 text-3xl mb-2"></i>
                    <p class="text-gray-400 text-sm">No uploads yet.</p>
                    <a href="{{ route('admin.uploads.create') }}" class="text-indigo-600 text-sm hover:underline mt-1 inline-block">Upload your first CSV</a>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Errors -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800 text-sm">Recent Errors</h2>
                <a href="{{ route('admin.error-logs.index') }}" class="text-xs text-indigo-600 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentErrors as $error)
                <div class="px-5 py-3.5">
                    <div class="flex items-start space-x-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium mt-0.5 {{ $error->type_badge_class }}">{{ ucfirst($error->type) }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-700 truncate">{{ $error->message }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $error->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center">
                    <i class="fa-solid fa-circle-check text-green-300 text-3xl mb-2"></i>
                    <p class="text-gray-400 text-sm">No errors — everything is running smoothly!</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="font-semibold text-gray-800 text-sm mb-4">Quick Actions</h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.uploads.create') }}"
               class="inline-flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg transition-all duration-200">
                <i class="fa-solid fa-upload"></i><span>Upload New CSV</span>
            </a>
            <a href="{{ route('admin.products.index') }}"
               class="inline-flex items-center space-x-2 bg-white hover:bg-gray-50 text-gray-700 text-sm px-4 py-2 rounded-lg border border-gray-200 transition-all duration-200">
                <i class="fa-solid fa-boxes-stacked text-indigo-500"></i><span>View All Products</span>
            </a>
            <a href="{{ route('admin.products.index') }}?status=failed"
               class="inline-flex items-center space-x-2 bg-white hover:bg-gray-50 text-gray-700 text-sm px-4 py-2 rounded-lg border border-gray-200 transition-all duration-200">
                <i class="fa-solid fa-rotate text-yellow-500"></i><span>Retry Failed Products</span>
            </a>
            <a href="{{ route('admin.error-logs.index') }}"
               class="inline-flex items-center space-x-2 bg-white hover:bg-gray-50 text-gray-700 text-sm px-4 py-2 rounded-lg border border-gray-200 transition-all duration-200">
                <i class="fa-solid fa-triangle-exclamation text-red-500"></i><span>Review Error Logs</span>
            </a>
        </div>
    </div>

</div>
@endsection
