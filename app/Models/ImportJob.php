<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJob extends Model
{
    protected $fillable = [
        'import_batch_id',
        'row_number',
        'product_title',
        'product_data',
        'shopify_product_id',
        'status',
        'error_message',
        'attempts',
        'processed_at',
    ];

    protected $casts = [
        'product_data' => 'array',
        'row_number'   => 'integer',
        'attempts'     => 'integer',
        'processed_at' => 'datetime',
    ];

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'success'    => 'green',
            'failed'     => 'red',
            'processing' => 'blue',
            'pending'    => 'yellow',
            default      => 'gray',
        };
    }
}