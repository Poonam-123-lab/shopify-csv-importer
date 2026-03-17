<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ErrorLog;
use App\Services\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncProductToShopify implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries   = 5;
    public int $backoff = 10; // seconds between retries

    public function __construct(
        public int     $productId,
        public ?string $collectionId = null
    ) {}

    public function handle(ShopifyService $shopify): void
    {
        $product = Product::findOrFail($this->productId);

        Log::info('Syncing product to Shopify', [
            'product_id'    => $this->productId,
            'title'         => $product->title,
            'sku'           => $product->sku,
            'collection_id' => $this->collectionId,
            'attempt'       => $this->attempts(),
        ]);

        $product->update(['status' => 'processing']);

        try {
            $result = $shopify->upsertProduct([
                'title'              => $product->title,
                'description'        => $product->description,
                'price'              => $product->price,
                'sku'                => $product->sku,
                'vendor'             => $product->vendor,
                'product_type'       => $product->product_type,
                'tags'               => $product->tags,
                'compare_at_price'   => $product->compare_at_price,
                'inventory_quantity' => $product->inventory_quantity,
            ], $this->collectionId);

            $product->update([
                'shopify_product_id' => $result['shopify_product_id'],
                'status'             => 'synced',
                'shopify_action'     => $result['action'], // 'created' or 'updated'
            ]);

            Log::info('Product synced to Shopify successfully', [
                'product_id'         => $this->productId,
                'shopify_product_id' => $result['shopify_product_id'],
                'action'             => $result['action'],
            ]);

        } catch (\Exception $e) {
            Log::error('Product Shopify sync failed', [
                'product_id' => $this->productId,
                'title'      => $product->title,
                'error'      => $e->getMessage(),
                'attempt'    => $this->attempts(),
            ]);

            // Only mark as failed if we've exhausted all retries
            if ($this->attempts() >= $this->tries) {
                $product->update(['status' => 'failed']);

                ErrorLog::create([
                    'upload_id'  => $product->upload_id,
                    'product_id' => $product->id,
                    'message'    => 'Shopify sync failed after ' . $this->tries . ' attempts: ' . $e->getMessage(),
                    'type'       => 'shopify',
                    'raw_data'   => json_encode([
                        'title' => $product->title,
                        'sku'   => $product->sku,
                        'price' => $product->price,
                    ]),
                ]);
            }

            throw $e; // Re-throw so queue can retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncProductToShopify permanently failed', [
            'product_id' => $this->productId,
            'error'      => $exception->getMessage(),
        ]);

        $product = Product::find($this->productId);
        if ($product) {
            $product->update(['status' => 'failed']);

            ErrorLog::firstOrCreate(
                ['product_id' => $product->id, 'type' => 'shopify'],
                [
                    'upload_id' => $product->upload_id,
                    'message'   => 'Permanently failed: ' . $exception->getMessage(),
                    'type'      => 'shopify',
                ]
            );
        }
    }

    /**
     * Determine retry delay — exponential backoff
     */
    public function backoff(): array
    {
        return [10, 30, 60, 120, 300]; // 10s, 30s, 1m, 2m, 5m
    }
}