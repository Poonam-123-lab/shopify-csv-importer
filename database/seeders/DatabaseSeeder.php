<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Upload;
use App\Models\Product;
use App\Models\ErrorLog;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed demo uploads
        $uploads = [
            ['file_name' => 'summer_collection_2024.csv', 'file_path' => 'csv_uploads/demo_summer.csv', 'status' => 'completed', 'total_rows' => 25, 'processed_rows' => 23, 'failed_rows' => 2],
            ['file_name' => 'electronics_inventory.csv',  'file_path' => 'csv_uploads/demo_electronics.csv', 'status' => 'completed', 'total_rows' => 40, 'processed_rows' => 38, 'failed_rows' => 2],
            ['file_name' => 'home_decor_products.csv',    'file_path' => 'csv_uploads/demo_home.csv', 'status' => 'processing', 'total_rows' => 15, 'processed_rows' => 8, 'failed_rows' => 0],
            ['file_name' => 'sports_gear_q1.csv',         'file_path' => 'csv_uploads/demo_sports.csv', 'status' => 'failed', 'total_rows' => 10, 'processed_rows' => 0, 'failed_rows' => 10],
            ['file_name' => 'beauty_products_march.csv',  'file_path' => 'csv_uploads/demo_beauty.csv', 'status' => 'pending', 'total_rows' => 0, 'processed_rows' => 0, 'failed_rows' => 0],
        ];

        foreach ($uploads as $uploadData) {
            $upload = Upload::create($uploadData);

            // Seed products for completed/processing uploads
            if (in_array($uploadData['status'], ['completed', 'processing'])) {
                $sampleProducts = [
                    ['title' => 'Classic Cotton T-Shirt',       'price' => 29.99,  'sku' => 'TSH-001', 'status' => 'synced'],
                    ['title' => 'Premium Denim Jeans',          'price' => 79.95,  'sku' => 'JNS-002', 'status' => 'synced'],
                    ['title' => 'Wireless Bluetooth Headphones','price' => 149.00, 'sku' => 'WBH-010', 'status' => 'synced'],
                    ['title' => 'Ergonomic Office Chair',       'price' => 349.99, 'sku' => 'OFC-020', 'status' => 'synced'],
                    ['title' => 'Stainless Steel Water Bottle', 'price' => 24.95,  'sku' => 'WBT-003', 'status' => 'failed'],
                    ['title' => 'Yoga Mat Pro',                 'price' => 59.99,  'sku' => 'YGA-005', 'status' => 'synced'],
                    ['title' => 'LED Desk Lamp',                'price' => 44.50,  'sku' => 'LMP-015', 'status' => 'synced'],
                    ['title' => 'Running Shoes Air Boost',      'price' => 119.99, 'sku' => 'SHO-040', 'status' => 'synced'],
                ];

                foreach ($sampleProducts as $idx => $productData) {
                    Product::create(array_merge($productData, [
                        'upload_id'          => $upload->id,
                        'description'        => 'High-quality ' . strtolower($productData['title']) . ' designed for everyday use. Premium materials, excellent craftsmanship.',
                        'vendor'             => ['Nike', 'Adidas', 'Apple', 'Samsung', 'Generic'][array_rand(['Nike', 'Adidas', 'Apple', 'Samsung', 'Generic'])],
                        'product_type'       => ['Apparel', 'Electronics', 'Home & Garden', 'Sports'][array_rand(['Apparel', 'Electronics', 'Home & Garden', 'Sports'])],
                        'inventory_quantity' => rand(5, 150),
                        'shopify_product_id' => $productData['status'] === 'synced' ? 'DEMO_' . strtoupper(substr(md5($productData['sku']), 0, 8)) : null,
                    ]));
                }
            }

            // Seed some error logs
            if ($uploadData['failed_rows'] > 0 || $uploadData['status'] === 'failed') {
                $errorMessages = [
                    ['message' => 'Row 3: Title is required.', 'type' => 'validation', 'row_number' => 3],
                    ['message' => 'Row 7: Price must be a valid non-negative number. Got: "N/A".', 'type' => 'validation', 'row_number' => 7],
                    ['message' => 'Row 12: Shopify sync failed: API rate limit exceeded.', 'type' => 'shopify', 'row_number' => 12],
                    ['message' => 'Row 18: Title must not exceed 255 characters.', 'type' => 'validation', 'row_number' => 18],
                ];

                foreach (array_slice($errorMessages, 0, $uploadData['failed_rows'] ?: 2) as $err) {
                    ErrorLog::create(array_merge($err, [
                        'upload_id'  => $upload->id,
                        'product_id' => null,
                        'raw_data'   => json_encode(['title' => '', 'price' => 'N/A', 'sku' => 'ERR-999']),
                    ]));
                }
            }
        }
    }
}