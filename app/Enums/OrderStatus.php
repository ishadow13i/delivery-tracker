<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Created = 'created';
    case Assigned = 'assigned';
    case Dispatched = 'dispatched';
    case Delivered = 'delivered';
    case Delayed = 'delayed';
    case Rejected = 'rejected';
    case Returned = 'returned';
    case Missing = 'missing';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Assigned => 'Assigned',
            self::Dispatched => 'Dispatched',
            self::Delivered => 'Delivered',
            self::Delayed => 'Delayed',
            self::Rejected => 'Rejected',
            self::Returned => 'Returned',
            self::Missing => 'Missing',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Created => 'gray',
            self::Assigned => 'info',
            self::Dispatched => 'warning',
            self::Delivered => 'success',
            self::Delayed => 'warning',
            self::Rejected => 'danger',
            self::Returned => 'info',
            self::Missing => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Created => 'heroicon-o-document-plus',
            self::Assigned => 'heroicon-o-truck',
            self::Dispatched => 'heroicon-o-paper-airplane',
            self::Delivered => 'heroicon-o-check-circle',
            self::Delayed => 'heroicon-o-clock',
            self::Rejected => 'heroicon-o-x-circle',
            self::Returned => 'heroicon-o-arrow-uturn-left',
            self::Missing => 'heroicon-o-exclamation-triangle',
        };
    }
}
