<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'name',
        'file_name',
        'file_path',
        'total_rows',
        'processed_rows',
        'success_count',
        'failed_count',
        'status',
        'uploaded_by',
        'started_at',
        'completed_at',
        'error_log',
    ];

    protected $casts = [
        'total_rows'     => 'integer',
        'processed_rows' => 'integer',
        'success_count'  => 'integer',
        'failed_count'   => 'integer',
        'started_at'     => 'datetime',
        'completed_at'   => 'datetime',
    ];

    public function importJobs(): HasMany
    {
        return $this->hasMany(ImportJob::class);
    }

    public function getProgressAttribute(): float
    {
        if ($this->total_rows === 0) return 0;
        return round(($this->processed_rows / $this->total_rows) * 100, 1);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed'  => 'green',
            'processing' => 'blue',
            'pending'    => 'yellow',
            'failed'     => 'red',
            default      => 'gray',
        };
    }
}