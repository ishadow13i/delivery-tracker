<?php

namespace App\Models;

use App\Enums\ScanType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanLog extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'type',
        'barcode_raw',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ScanType::class,
            'scanned_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
