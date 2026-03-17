<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ActivityLog;
use App\Jobs\SyncProductToShopify;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $query = Product::with('upload');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function show($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $product = Product::with(['upload', 'errorLogs'])->findOrFail($id);

        return view('admin.products.show', compact('product'));
    }

    public function retry($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $product = Product::findOrFail($id);
        $product->update(['status' => 'pending']);

        $collectionId = $product->upload->collection_id ?? null;

        SyncProductToShopify::dispatch($product->id, $collectionId)
            ->onQueue('shopify');

        ActivityLog::record(
            event:     'product_retry',
            message:   "Manual retry dispatched for product '{$product->title}'.",
            uploadId:  $product->upload_id,
            productId: $product->id
        );

        return redirect()->back()->with('success', 'Retry dispatched — product will sync shortly.');
    }

    public function destroy($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        Product::findOrFail($id)->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted.');
    }
}