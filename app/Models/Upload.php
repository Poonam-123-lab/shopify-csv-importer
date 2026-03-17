<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Upload extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'failed_rows',
    ];

    protected $casts = [
        'total_rows'     => 'integer',
        'processed_rows' => 'integer',
        'failed_rows'    => 'integer',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function errorLogs()
    {
        return $this->hasMany(ErrorLog::class);
    }

    public function getProgressPercentageAttribute(): int
    {
        if (!$this->total_rows || $this->total_rows === 0) {
            return 0;
        }
        return (int) round(($this->processed_rows / $this->total_rows) * 100);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'pending'    => 'bg-yellow-100 text-yellow-800',
            'processing' => 'bg-blue-100 text-blue-800',
            'completed'  => 'bg-green-100 text-green-800',
            'failed'     => 'bg-red-100 text-red-800',
            default      => 'bg-gray-100 text-gray-800',
        };
    }
}