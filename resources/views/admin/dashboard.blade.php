@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'CSV import operations overview')

@section('content')
<div class="space-y-6 pt-2">

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Total Uploads</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalUploads }}</p>
                    <p class="text-xs text-gray-400 mt-1"><span class="text-yellow-600 font-medium">{{ $pendingUploads }} active</span> · {{ $completedUploads }} done</p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-file-csv text-indigo-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Products Synced</p>
                    <p class="text-3xl font-bold text-green-600 mt-1">{{ $syncedProducts }}</p>
                    <p class="text-xs text-gray-400 mt-1"><span class="text-indigo-500 font-medium">{{ $createdInShopify }} new</span> · <span class="text-purple-500 font-medium">{{ $updatedInShopify }} updated</span></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-circle-check text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Success Rate</p>
                    <p class="text-3xl font-bold mt-1 {{ $successRate >= 80 ? 'text-green-600' : ($successRate >= 50 ? 'text-yellow-600' : 'text-red-600') }}">{{ $successRate }}%</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $totalProducts }} total products</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-chart-line text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Errors Logged</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalErrors }}</p>
                    <p class="text-xs text-gray-400 mt-1"><span class="text-red-500 font-medium">{{ $failedProducts }} failed</span> products</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-triangle-exclamation text-red-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Status Breakdown -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="font-semibold text-gray-800 text-sm mb-4">Product Status Breakdown</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @php
                $statusConfig = [
                    'synced'  => ['label' => 'Synced',     'color' => 'bg-green-500'],
                    'pending' => ['label' => 'Pending',    'color' => 'bg-yellow-400'],
                    'failed'  => ['label' => 'Failed',     'color' => 'bg-red-500'],
                    'skipped' => ['label' => 'Skipped',    'color' => 'bg-gray-400'],
                ];
                $total = array_sum($productStatusData) ?: 1;
            @endphp
            @foreach($statusConfig as $status => $config)
            @php $count = $productStatusData[$status] ?? 0; $pct = round($count / $total * 100); @endphp
            <div>
                <div class="flex justify-between items-center mb-1">
                    <span class="text-xs text-gray-600">{{ $config['label'] }}</span>
                    <span class="text-xs font-semibold text-gray-800">{{ $count }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full {{ $config['color'] }}" style="width: {{ $pct }}%"></div>
                </div>
                <p class="text-xs text-gray-400 mt-0.5">{{ $pct }}%</p>
            </div>
            @endforeach
        </div>
    </div>

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
                    <div class="flex items-center space-x-3 min-w-0">
                        <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-file-csv text-indigo-400 text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <a href="{{ route('admin.uploads.show', $upload->id) }}"
                               class="text-sm font-medium text-gray-800 hover:text-indigo-600 truncate block max-w-[180px]">{{ $upload->file_name }}</a>
                            <p class="text-xs text-gray-400">{{ $upload->created_at->diffForHumans() }} · {{ $upload->products_count }} products</p>
                        </div>
                    </div>
                    <span class="text-xs px-2.5 py-1 rounded-full font-medium flex-shrink-0 {{ $upload->status_badge_class }}">{{ ucfirst($upload->status) }}</span>
                </div>
                @empty
                <div class="px-5 py-8 text-center">
                    <p class="text-gray-400 text-sm">No uploads yet.</p>
                    <a href="{{ route('admin.uploads.create') }}" class="text-indigo-600 text-sm hover:underline mt-1 inline-block">Upload your first CSV</a>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800 text-sm">Recent Activity</h2>
                <a href="{{ route('admin.activity-logs.index') }}" class="text-xs text-indigo-600 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentActivity as $log)
                <div class="px-5 py-3">
                    <div class="flex items-start space-x-2.5">
                        <span class="text-xs px-2 py-0.5 rounded-full mt-0.5 whitespace-nowrap {{ $log->level_badge_class }}">{{ ucfirst($log->level) }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-700 truncate">{{ $log->message }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $log->event }} · {{ $log->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center">
                    <i class="fa-solid fa-scroll text-gray-300 text-3xl mb-2"></i>
                    <p class="text-gray-400 text-sm">No activity yet.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="font-semibold text-gray-800 text-sm mb-4">Quick Actions</h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.uploads.create') }}" class="inline-flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg transition-all">
                <i class="fa-solid fa-upload"></i><span>Upload CSV</span>
            </a>
            <a href="{{ route('admin.products.index') }}?status=failed" class="inline-flex items-center space-x-2 bg-white hover:bg-gray-50 text-gray-700 text-sm px-4 py-2 rounded-lg border border-gray-200 transition-all">
                <i class="fa-solid fa-rotate text-yellow-500"></i><span>View Failed Products</span>
            </a>
            <a href="{{ route('admin.error-logs.index') }}" class="inline-flex items-center space-x-2 bg-white hover:bg-gray-50 text-gray-700 text-sm px-4 py-2 rounded-lg border border-gray-200 transition-all">
                <i class="fa-solid fa-triangle-exclamation text-red-500"></i><span>Review Errors</span>
            </a>
            <a href="{{ route('admin.activity-logs.index') }}" class="inline-flex items-center space-x-2 bg-white hover:bg-gray-50 text-gray-700 text-sm px-4 py-2 rounded-lg border border-gray-200 transition-all">
                <i class="fa-solid fa-scroll text-indigo-500"></i><span>Activity Logs</span>
            </a>
        </div>
    </div>

</div>
@endsection
