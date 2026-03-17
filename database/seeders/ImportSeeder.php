<?php

namespace Database\Seeders;

use App\Models\ImportBatch;
use App\Models\ImportJob;
use Illuminate\Database\Seeder;

class ImportSeeder extends Seeder
{
    public function run(): void
    {
        $batches = [
            [
                'name'           => 'Summer Collection 2024',
                'file_name'      => 'summer_collection.csv',
                'file_path'      => 'csv_imports/summer_collection.csv',
                'total_rows'     => 25,
                'processed_rows' => 25,
                'success_count'  => 23,
                'failed_count'   => 2,
                'status'         => 'completed',
                'uploaded_by'    => 'admin@shopifyimport.com',
                'started_at'     => now()->subHours(3),
                'completed_at'   => now()->subHours(2),
            ],
            [
                'name'           => 'Winter Inventory Upload',
                'file_name'      => 'winter_inventory.csv',
                'file_path'      => 'csv_imports/winter_inventory.csv',
                'total_rows'     => 50,
                'processed_rows' => 50,
                'success_count'  => 48,
                'failed_count'   => 2,
                'status'         => 'completed',
                'uploaded_by'    => 'admin@shopifyimport.com',
                'started_at'     => now()->subDays(2),
                'completed_at'   => now()->subDays(2)->addMinutes(15),
            ],
            [
                'name'           => 'Electronics Batch Q1',
                'file_name'      => 'electronics_q1.csv',
                'file_path'      => 'csv_imports/electronics_q1.csv',
                'total_rows'     => 30,
                'processed_rows' => 10,
                'success_count'  => 9,
                'failed_count'   => 1,
                'status'         => 'processing',
                'uploaded_by'    => 'manager@shopifyimport.com',
                'started_at'     => now()->subMinutes(5),
                'completed_at'   => null,
            ],
            [
                'name'           => 'Accessories Import Failed',
                'file_name'      => 'accessories.csv',
                'file_path'      => 'csv_imports/accessories.csv',
                'total_rows'     => 15,
                'processed_rows' => 5,
                'success_count'  => 3,
                'failed_count'   => 5,
                'status'         => 'failed',
                'uploaded_by'    => 'admin@shopifyimport.com',
                'started_at'     => now()->subDays(1),
                'completed_at'   => now()->subDays(1)->addMinutes(3),
                'error_log'      => 'Shopify API rate limit exceeded during processing.',
            ],
        ];

        foreach ($batches as $batchData) {
            $batch = ImportBatch::create($batchData);

            $products = [
                ['title' => 'Classic White T-Shirt', 'price' => '29.99', 'sku' => 'TSHIRT-WHT-001'],
                ['title' => 'Denim Blue Jeans', 'price' => '79.99', 'sku' => 'JEANS-BLU-002'],
                ['title' => 'Leather Sneakers', 'price' => '119.99', 'sku' => 'SHOE-LTH-003'],
                ['title' => 'Canvas Backpack', 'price' => '59.99', 'sku' => 'BAG-CNV-004'],
                ['title' => 'Wireless Headphones', 'price' => '149.99', 'sku' => 'ELEC-HP-005'],
            ];

            foreach ($products as $index => $product) {
                $status = $index < 3 ? 'success' : ($index === 3 ? 'failed' : 'pending');
                ImportJob::create([
                    'import_batch_id'   => $batch->id,
                    'row_number'        => $index + 1,
                    'product_title'     => $product['title'],
                    'product_data'      => array_merge($product, ['vendor' => 'Sample Store', 'type' => 'Apparel']),
                    'shopify_product_id'=> $status === 'success' ? (string)(8000000000000 + $batch->id * 100 + $index) : null,
                    'status'            => $status,
                    'error_message'     => $status === 'failed' ? 'Product title is required' : null,
                    'attempts'          => $status === 'failed' ? 1 : ($status === 'success' ? 1 : 0),
                    'processed_at'      => in_array($status, ['success', 'failed']) ? now()->subHours(1) : null,
                ]);
            }
        }
    }
}