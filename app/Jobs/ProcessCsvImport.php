<?php

namespace App\Jobs;

use App\Models\Upload;
use App\Models\Product;
use App\Models\ErrorLog;
use App\Services\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes
    public int $tries   = 3;

    public function __construct(public int $uploadId) {}

    public function handle(ShopifyService $shopify): void
    {
        $upload = Upload::findOrFail($this->uploadId);
        $upload->update(['status' => 'processing']);

        try {
            if (!Storage::disk('local')->exists($upload->file_path)) {
                throw new \Exception('CSV file not found on disk: ' . $upload->file_path);
            }

            $fullPath = Storage::disk('local')->path($upload->file_path);
            $handle   = fopen($fullPath, 'r');

            if (!$handle) {
                throw new \Exception('Unable to open CSV file for reading.');
            }

            // Read headers from first row
            $headers = fgetcsv($handle);
            if (!$headers) {
                throw new \Exception('CSV file is empty or has no headers.');
            }

            // Normalize headers (lowercase, trim)
            $headers   = array_map(fn($h) => strtolower(trim($h)), $headers);
            $rowNumber = 1;
            $totalRows = 0;
            $failed    = 0;

            // Count total rows first
            while (fgetcsv($handle) !== false) {
                $totalRows++;
            }
            rewind($handle);
            fgetcsv($handle); // skip header again

            $upload->update(['total_rows' => $totalRows]);

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if (empty(array_filter($row))) {
                    continue; // skip blank rows
                }

                $data = array_combine($headers, array_pad($row, count($headers), null));

                // Map CSV columns to product fields
                $mapped = $this->mapRow($data);

                // Validate the mapped row
                $errors = $this->validateRow($mapped, $rowNumber);

                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        ErrorLog::create([
                            'upload_id'  => $upload->id,
                            'product_id' => null,
                            'message'    => $error,
                            'type'       => 'validation',
                            'row_number' => $rowNumber,
                            'raw_data'   => json_encode($data),
                        ]);
                    }
                    $failed++;
                    $upload->increment('failed_rows');
                    continue;
                }

                // Create product record
                $product = Product::create(array_merge($mapped, [
                    'upload_id' => $upload->id,
                    'status'    => 'pending',
                ]));

                // Push to Shopify
                try {
                    $shopifyPayload = [
                        'title'       => $product->title,
                        'body_html'   => $product->description ?? '',
                        'vendor'      => $product->vendor ?? '',
                        'product_type'=> $product->product_type ?? '',
                        'tags'        => $product->tags ?? '',
                        'variants'    => [[
                            'price'            => (string) $product->price,
                            'sku'              => $product->sku ?? '',
                            'compare_at_price' => $product->compare_at_price ? (string) $product->compare_at_price : null,
                            'inventory_quantity'=> $product->inventory_quantity ?? 0,
                        ]],
                    ];

                    $result = $shopify->createProduct($shopifyPayload);

                    $product->update([
                        'shopify_product_id' => $result['id'] ?? null,
                        'status'             => 'synced',
                    ]);
                } catch (\Exception $e) {
                    $product->update(['status' => 'failed']);

                    ErrorLog::create([
                        'upload_id'  => $upload->id,
                        'product_id' => $product->id,
                        'message'    => 'Shopify sync failed: ' . $e->getMessage(),
                        'type'       => 'shopify',
                        'row_number' => $rowNumber,
                        'raw_data'   => json_encode($shopifyPayload ?? []),
                    ]);

                    $failed++;
                    $upload->increment('failed_rows');
                }

                $upload->increment('processed_rows');
            }

            fclose($handle);

            $upload->update([
                'status' => $failed === $totalRows ? 'failed' : 'completed',
            ]);

        } catch (\Exception $e) {
            Log::error('CSV Import Job failed', [
                'upload_id' => $this->uploadId,
                'error'     => $e->getMessage(),
            ]);

            ErrorLog::create([
                'upload_id' => $upload->id,
                'message'   => 'Import job failed: ' . $e->getMessage(),
                'type'      => 'system',
            ]);

            $upload->update(['status' => 'failed']);
        }
    }

    private function mapRow(array $data): array
    {
        // Flexible column mapping: supports various CSV header names
        $get = function (array $keys) use ($data): ?string {
            foreach ($keys as $key) {
                if (isset($data[$key]) && $data[$key] !== '') {
                    return trim($data[$key]);
                }
            }
            return null;
        };

        return [
            'title'              => $get(['title', 'product title', 'name', 'product name']),
            'description'        => $get(['description', 'body html', 'body_html', 'details', 'product description']),
            'price'              => $get(['price', 'variant price', 'variant_price', 'sale price']),
            'sku'                => $get(['sku', 'variant sku', 'variant_sku', 'product sku', 'barcode']),
            'vendor'             => $get(['vendor', 'brand', 'manufacturer']),
            'product_type'       => $get(['type', 'product type', 'product_type', 'category']),
            'tags'               => $get(['tags', 'keywords', 'labels']),
            'compare_at_price'   => $get(['compare at price', 'compare_at_price', 'original price', 'was price']),
            'inventory_quantity' => $get(['inventory quantity', 'inventory_quantity', 'stock', 'qty', 'quantity']) ?? '0',
        ];
    }

    private function validateRow(array $data, int $rowNumber): array
    {
        $errors = [];

        if (empty($data['title'])) {
            $errors[] = "Row {$rowNumber}: Title is required.";
        }

        if (empty($data['price'])) {
            $errors[] = "Row {$rowNumber}: Price is required.";
        } elseif (!is_numeric($data['price']) || (float) $data['price'] < 0) {
            $errors[] = "Row {$rowNumber}: Price must be a valid non-negative number. Got: '{$data['price']}'.";
        }

        if (!empty($data['compare_at_price']) && !is_numeric($data['compare_at_price'])) {
            $errors[] = "Row {$rowNumber}: Compare-at price must be a valid number.";
        }

        if (!empty($data['inventory_quantity']) && !ctype_digit((string) $data['inventory_quantity'])) {
            $errors[] = "Row {$rowNumber}: Inventory quantity must be a whole number.";
        }

        if (!empty($data['title']) && strlen($data['title']) > 255) {
            $errors[] = "Row {$rowNumber}: Title must not exceed 255 characters.";
        }

        return $errors;
    }
}