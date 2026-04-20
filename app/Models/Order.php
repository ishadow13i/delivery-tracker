<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'tracking_number',
        'company_id',
        'batch_id',
        'status',
        'dispatched_at',
        'delivered_at',
        'rejected_at',
        'returned_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
            'rejected_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(StatusLog::class);
    }
}
