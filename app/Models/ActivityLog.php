<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'event',
        'level',
        'message',
        'upload_id',
        'product_id',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function upload()
    {
        return $this->belongsTo(Upload::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getLevelBadgeClassAttribute(): string
    {
        return match($this->level) {
            'info'    => 'bg-blue-100 text-blue-800',
            'warning' => 'bg-yellow-100 text-yellow-800',
            'error'   => 'bg-red-100 text-red-800',
            'debug'   => 'bg-gray-100 text-gray-600',
            default   => 'bg-gray-100 text-gray-600',
        };
    }

    /**
     * Convenience method to create a log entry
     */
    public static function record(
        string  $event,
        string  $message,
        string  $level     = 'info',
        ?int    $uploadId  = null,
        ?int    $productId = null,
        array   $context   = []
    ): self {
        return self::create([
            'event'      => $event,
            'level'      => $level,
            'message'    => $message,
            'upload_id'  => $uploadId,
            'product_id' => $productId,
            'context'    => !empty($context) ? $context : null,
        ]);
    }
}