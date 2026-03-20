<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Shopify CSV Importer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-900 via-indigo-800 to-purple-900 flex items-center justify-center px-4">

<div class="w-full max-w-md">
    <!-- Logo -->
    <div class="text-center mb-8">
       
        <h1 class="text-3xl font-bold text-white">CSV Importer</h1>
        <p class="text-indigo-300 mt-1">Shopify Product Sync Tool</p>
    </div>

    <!-- Login Card -->
    <div class="bg-white rounded-2xl shadow-2xl p-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Sign In to Admin Panel</h2>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-5 text-sm flex items-center space-x-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                <div class="relative">
                    <i class="fa-solid fa-envelope absolute left-3 top-3 text-gray-400 text-sm"></i>
                    <input type="email" name="email" value="{{ old('email') }}"
                           placeholder="admin@shopifyimporter.com"
                           class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                           required autofocus>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-3 top-3 text-gray-400 text-sm"></i>
                    <input type="password" name="password"
                           placeholder="Enter your password"
                           class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                           required>
                </div>
            </div>

            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg transition-all duration-200 text-sm">
                <i class="fa-solid fa-right-to-bracket mr-2"></i>
                Sign In
            </button>
        </form>

        <!-- Demo Credentials -->
        <div class="mt-6 pt-6 border-t border-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Demo Credentials</p>
            <div class="space-y-2">
                <div class="bg-indigo-50 rounded-lg px-3 py-2.5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-indigo-800">admin@shopifyimporter.com</p>
                        <p class="text-xs text-indigo-500">Password: admin123</p>
                    </div>
                    <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-medium">Admin</span>
                </div>
                
            </div>
        </div>
    </div>

  
</div>

</body>
</html>
