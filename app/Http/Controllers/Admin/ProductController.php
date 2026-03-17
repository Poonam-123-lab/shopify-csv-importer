<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ShopifyService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(protected ShopifyService $shopify) {}

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

        try {
            $shopifyProduct = $this->shopify->createProduct([
                'title'      => $product->title,
                'body_html'  => $product->description,
                'variants'   => [[
                    'price' => $product->price,
                    'sku'   => $product->sku,
                ]],
            ]);

            $product->update([
                'shopify_product_id' => $shopifyProduct['id'] ?? null,
                'status'             => 'synced',
            ]);

            return redirect()->back()->with('success', 'Product successfully synced to Shopify.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Retry failed: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        Product::findOrFail($id)->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}