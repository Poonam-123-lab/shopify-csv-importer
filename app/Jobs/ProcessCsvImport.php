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

    public int $timeout = 600; // 10 minutes for large files
    public int $tries   = 3;
    public int $backoff = 30; // seconds between retries

    public function __construct(
        public int     $uploadId,
        public ?string $collectionId = null  // optional Shopify collection ID
    ) {}

    public function handle(ShopifyService $shopify): void
    {
        $upload = Upload::findOrFail($this->uploadId);

        Log::info('CSV Import job started', [
            'upload_id'     => $this->uploadId,
            'file_name'     => $upload->file_name,
            'collection_id' => $this->collectionId,
            'attempt'       => $this->attempts(),
        ]);

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

            // Read and normalize headers
            $rawHeaders = fgetcsv($handle);
            if (!$rawHeaders) {
                throw new \Exception('CSV file is empty or has no header row.');
            }
            $headers = array_map(fn($h) => strtolower(trim($h)), $rawHeaders);

            // Count total data rows
            $totalRows = 0;
            while (fgetcsv($handle) !== false) {
                $totalRows++;
            }
            rewind($handle);
            fgetcsv($handle); // skip header row again

            $upload->update(['total_rows' => $totalRows]);

            Log::info('CSV parsed', [
                'upload_id'  => $this->uploadId,
                'total_rows' => $totalRows,
                'headers'    => $headers,
            ]);

            $rowNumber = 1;
            $synced    = 0;
            $failed    = 0;
            $updated   = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Combine headers with row values
                $data   = array_combine($headers, array_pad($row, count($headers), null));
                $mapped = $this->mapRow($data);

                // Validate row
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
                    Log::warning('CSV row validation failed', ['row' => $rowNumber, 'errors' => $errors]);
                    continue;
                }

                // Find existing product by SKU (preferred) or title — update if found
                $uniqueKey = !empty($mapped['sku'])
                    ? ['sku' => $mapped['sku']]
                    : ['title' => $mapped['title']];

                $product = Product::updateOrCreate(
                    $uniqueKey,
                    array_merge($mapped, [
                        'upload_id' => $upload->id,
                        'status'    => 'pending',
                    ])
                );

                // Dispatch individual product sync job for better isolation + retry
                SyncProductToShopify::dispatch($product->id, $this->collectionId)
                    ->onQueue('shopify');

                $upload->increment('processed_rows');
            }

            fclose($handle);

            Log::info('CSV Import job completed dispatching', [
                'upload_id'  => $this->uploadId,
                'total_rows' => $totalRows,
                'failed_csv' => $failed,
            ]);

            // Mark upload as completed (individual products sync separately)
            $upload->update(['status' => 'completed']);
        } catch (\Exception $e) {
            Log::error('CSV Import job failed', [
                'upload_id' => $this->uploadId,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            ErrorLog::create([
                'upload_id' => $upload->id,
                'message'   => 'Import job failed: ' . $e->getMessage(),
                'type'      => 'system',
            ]);

            $upload->update(['status' => 'failed']);

            throw $e; // Re-throw for queue retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CSV Import job permanently failed (all retries exhausted)', [
            'upload_id' => $this->uploadId,
            'error'     => $exception->getMessage(),
        ]);

        $upload = Upload::find($this->uploadId);
        if ($upload) {
            $upload->update(['status' => 'failed']);
            ErrorLog::create([
                'upload_id' => $upload->id,
                'message'   => 'Job permanently failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
                'type'      => 'system',
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // CSV Column Mapping
    // -------------------------------------------------------------------------

    private function mapRow(array $data): array
    {
        $get = function (array $keys) use ($data): ?string {
            foreach ($keys as $key) {
                if (isset($data[$key]) && trim((string) $data[$key]) !== '') {
                    return trim($data[$key]);
                }
            }
            return null;
        };

        return [
            'title'              => $get(['title', 'product title', 'product_title', 'name', 'product name']),
            'description'        => $get(['description', 'body html', 'body_html', 'details', 'product description', 'body']),
            'price'              => $get(['price', 'variant price', 'variant_price', 'sale price', 'retail price']),
            'sku'                => $get(['sku', 'variant sku', 'variant_sku', 'product sku', 'barcode', 'item number']),
            'vendor'             => $get(['vendor', 'brand', 'manufacturer', 'supplier']),
            'product_type'       => $get(['type', 'product type', 'product_type', 'category', 'department']),
            'tags'               => $get(['tags', 'keywords', 'labels', 'tag']),
            'compare_at_price'   => $get(['compare at price', 'compare_at_price', 'original price', 'was price', 'rrp']),
            'inventory_quantity' => $get(['inventory quantity', 'inventory_quantity', 'stock', 'qty', 'quantity', 'stock quantity']) ?? '0',
        ];
    }

    // -------------------------------------------------------------------------
    // Row Validation
    // -------------------------------------------------------------------------

    private function validateRow(array $data, int $rowNumber): array
    {
        $errors = [];

        if (empty($data['title'])) {
            $errors[] = "Row {$rowNumber}: 'title' is required but missing or empty.";
        } elseif (strlen($data['title']) > 255) {
            $errors[] = "Row {$rowNumber}: 'title' must not exceed 255 characters (got " . strlen($data['title']) . ").";
        }

        if (empty($data['price'])) {
            $errors[] = "Row {$rowNumber}: 'price' is required but missing or empty.";
        } elseif (!is_numeric($data['price'])) {
            $errors[] = "Row {$rowNumber}: 'price' must be a valid number. Got: '{$data['price']}'.";
        } elseif ((float) $data['price'] < 0) {
            $errors[] = "Row {$rowNumber}: 'price' must be non-negative. Got: '{$data['price']}'.";
        }

        if (!empty($data['compare_at_price']) && !is_numeric($data['compare_at_price'])) {
            $errors[] = "Row {$rowNumber}: 'compare_at_price' must be a valid number. Got: '{$data['compare_at_price']}'.";
        }

        if (!empty($data['inventory_quantity']) && $data['inventory_quantity'] !== '0') {
            if (!ctype_digit((string) $data['inventory_quantity'])) {
                $errors[] = "Row {$rowNumber}: 'inventory_quantity' must be a whole number. Got: '{$data['inventory_quantity']}'.";
            }
        }

        return $errors;
    }
}
