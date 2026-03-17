<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ErrorLog extends Model
{
    use HasFactory;

    protected $table = 'error_logs';

    protected $fillable = [
        'upload_id',
        'product_id',
        'message',
        'type',
        'row_number',
        'raw_data',
    ];

    protected $casts = [
        'row_number' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class);
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match($this->type) {
            'validation' => 'bg-orange-100 text-orange-800',
            'shopify'    => 'bg-purple-100 text-purple-800',
            'parsing'    => 'bg-yellow-100 text-yellow-800',
            'system'     => 'bg-red-100 text-red-800',
            default      => 'bg-gray-100 text-gray-800',
        };
    }
}