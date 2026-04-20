<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusLog extends Model
{
    protected $fillable = [
        'order_id',
        'changed_by',
        'old_status',
        'new_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'old_status' => OrderStatus::class,
            'new_status' => OrderStatus::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
