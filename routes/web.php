<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ErrorLogController;
use App\Http\Controllers\Admin\ActivityLogController;

// Root redirect
Route::get('/', function () {
    return redirect()->route('admin.login');
});

// Admin Authentication
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Admin Dashboard
Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

// CSV Uploads
Route::get('/admin/uploads', [UploadController::class, 'index'])->name('admin.uploads.index');
Route::get('/admin/uploads/create', [UploadController::class, 'create'])->name('admin.uploads.create');
Route::post('/admin/uploads', [UploadController::class, 'store'])->name('admin.uploads.store');
Route::get('/admin/uploads/{id}', [UploadController::class, 'show'])->name('admin.uploads.show');
Route::delete('/admin/uploads/{id}', [UploadController::class, 'destroy'])->name('admin.uploads.destroy');

// Products
Route::get('/admin/products', [ProductController::class, 'index'])->name('admin.products.index');
Route::get('/admin/products/{id}', [ProductController::class, 'show'])->name('admin.products.show');
Route::post('/admin/products/{id}/retry', [ProductController::class, 'retry'])->name('admin.products.retry');
Route::delete('/admin/products/{id}', [ProductController::class, 'destroy'])->name('admin.products.destroy');

// Error Logs
Route::get('/admin/error-logs', [ErrorLogController::class, 'index'])->name('admin.error-logs.index');
Route::delete('/admin/error-logs/{id}', [ErrorLogController::class, 'destroy'])->name('admin.error-logs.destroy');
Route::post('/admin/error-logs/clear', [ErrorLogController::class, 'clearAll'])->name('admin.error-logs.clear');

// Activity / System Logs
Route::get('/admin/activity-logs', [ActivityLogController::class, 'index'])->name('admin.activity-logs.index');
Route::post('/admin/activity-logs/clear', [ActivityLogController::class, 'clearAll'])->name('admin.activity-logs.clear');