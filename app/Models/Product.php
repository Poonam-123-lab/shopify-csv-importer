<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'price',
        'sku',
        'vendor',
        'product_type',
        'tags',
        'compare_at_price',
        'inventory_quantity',
        'shopify_product_id',
        'shopify_action',
        'status',
        'upload_id',
    ];

    protected $casts = [
        'price'              => 'decimal:2',
        'compare_at_price'   => 'decimal:2',
        'inventory_quantity' => 'integer',
    ];

    public function upload()
    {
        return $this->belongsTo(Upload::class);
    }

    public function errorLogs()
    {
        return $this->hasMany(ErrorLog::class);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'pending'    => 'bg-yellow-100 text-yellow-800',
            'processing' => 'bg-blue-100 text-blue-800',
            'synced'     => 'bg-green-100 text-green-800',
            'failed'     => 'bg-red-100 text-red-800',
            'skipped'    => 'bg-gray-100 text-gray-800',
            default      => 'bg-gray-100 text-gray-800',
        };
    }

    public function getShopifyUrlAttribute(): ?string
    {
        if (!$this->shopify_product_id) {
            return null;
        }
        $domain = config('shopify.shop_domain');
        return $domain ? "https://{$domain}/admin/products/{$this->shopify_product_id}" : null;
    }
}