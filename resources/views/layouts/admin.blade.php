<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Shopify CSV Importer') — Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .sidebar-link.active { background: rgba(255,255,255,0.15); border-left: 3px solid #fff; }
        .sidebar-link:hover  { background: rgba(255,255,255,0.10); }
        [x-cloak] { display: none; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 flex-shrink-0 bg-gradient-to-b from-indigo-900 to-indigo-700 text-white flex flex-col">
        <!-- Logo -->
        <div class="px-6 py-5 border-b border-indigo-600">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center">
                    <i class="fa-brands fa-shopify text-indigo-700 text-xl"></i>
                </div>
                <div>
                    <p class="font-bold text-sm leading-tight">CSV Importer</p>
                    <p class="text-indigo-300 text-xs">Shopify Sync Tool</p>
                </div>
            </div>
        </div>

        <!-- Nav Links -->
        <nav class="flex-1 px-3 py-4 space-y-1">
            <a href="{{ route('admin.dashboard') }}"
               class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-gauge-high w-5 text-center"></i>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('admin.uploads.index') }}"
               class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.uploads.*') ? 'active' : '' }}">
                <i class="fa-solid fa-file-csv w-5 text-center"></i>
                <span>CSV Uploads</span>
            </a>

            <a href="{{ route('admin.products.index') }}"
               class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                <i class="fa-solid fa-boxes-stacked w-5 text-center"></i>
                <span>Products</span>
            </a>

            <a href="{{ route('admin.error-logs.index') }}"
               class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.error-logs.*') ? 'active' : '' }}">
                <i class="fa-solid fa-triangle-exclamation w-5 text-center"></i>
                <span>Error Logs</span>
            </a>
        </nav>

        <!-- User Info -->
        <div class="px-4 py-4 border-t border-indigo-600">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-indigo-400 rounded-full flex items-center justify-center text-xs font-bold">
                        {{ strtoupper(substr(session('admin_user', 'A'), 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium">{{ session('admin_user') }}</p>
                        <p class="text-indigo-300 text-xs">Administrator</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" title="Logout"
                            class="text-indigo-300 hover:text-white transition-colors duration-200">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Topbar -->
        <header class="bg-white shadow-sm flex items-center justify-between px-6 py-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h1>
                <p class="text-xs text-gray-400">@yield('page-subtitle', '')</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.uploads.create') }}"
                   class="inline-flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg transition-all duration-200">
                    <i class="fa-solid fa-upload"></i>
                    <span>Upload CSV</span>
                </a>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="px-6 pt-4">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center space-x-2 mb-4">
                    <i class="fa-solid fa-circle-check text-green-500"></i>
                    <span class="text-sm">{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center space-x-2 mb-4">
                    <i class="fa-solid fa-circle-xmark text-red-500"></i>
                    <span class="text-sm">{{ session('error') }}</span>
                </div>
            @endif
        </div>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto px-6 py-2 pb-8">
            @yield('content')
        </main>
    </div>
</div>

</body>
</html>
