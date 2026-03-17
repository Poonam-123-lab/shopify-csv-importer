<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    private string $shopDomain;
    private string $apiVersion;
    private string $accessToken;
    private string $baseUrl;

    public function __construct()
    {
        $this->shopDomain  = config('shopify.shop_domain', '');
        $this->apiVersion  = config('shopify.api_version', '2024-01');
        $this->accessToken = config('shopify.access_token', '');
        $this->baseUrl     = "https://{$this->shopDomain}/admin/api/{$this->apiVersion}";
    }

    /**
     * Create a product in Shopify.
     * Returns the created product data array.
     * Throws an exception on failure.
     */
    public function createProduct(array $productData): array
    {
        // If no credentials configured, run in demo/dry-run mode
        if (empty($this->shopDomain) || empty($this->accessToken)) {
            Log::info('Shopify running in DEMO mode - no credentials configured', ['product' => $productData['title'] ?? 'unknown']);
            return [
                'id'     => 'DEMO_' . strtoupper(substr(md5(json_encode($productData)), 0, 8)),
                'title'  => $productData['title'],
                'status' => 'active',
            ];
        }

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type'           => 'application/json',
        ])->post("{$this->baseUrl}/products.json", [
            'product' => $productData,
        ]);

        if ($response->failed()) {
            $error = $response->json('errors') ?? $response->body();
            throw new \Exception('Shopify API error (' . $response->status() . '): ' . (is_array($error) ? json_encode($error) : $error));
        }

        return $response->json('product', []);
    }

    /**
     * Update an existing product in Shopify.
     */
    public function updateProduct(string $shopifyProductId, array $productData): array
    {
        if (empty($this->shopDomain) || empty($this->accessToken)) {
            return array_merge(['id' => $shopifyProductId], $productData);
        }

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type'           => 'application/json',
        ])->put("{$this->baseUrl}/products/{$shopifyProductId}.json", [
            'product' => $productData,
        ]);

        if ($response->failed()) {
            $error = $response->json('errors') ?? $response->body();
            throw new \Exception('Shopify update error (' . $response->status() . '): ' . (is_array($error) ? json_encode($error) : $error));
        }

        return $response->json('product', []);
    }

    /**
     * Delete a product from Shopify.
     */
    public function deleteProduct(string $shopifyProductId): bool
    {
        if (empty($this->shopDomain) || empty($this->accessToken)) {
            return true;
        }

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
        ])->delete("{$this->baseUrl}/products/{$shopifyProductId}.json");

        return $response->successful();
    }
}