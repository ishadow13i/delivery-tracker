<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Dispatched = 'dispatched';
    case Delivered = 'delivered';
    case Delayed = 'delayed';
    case Rejected = 'rejected';
    case Returned = 'returned';
    case Missing = 'missing';

    public function label(): string
    {
        return match ($this) {
            self::Dispatched => 'مُرسل',
            self::Delivered => 'تم التوصيل',
            self::Delayed => 'مؤجل',
            self::Rejected => 'مرفوض',
            self::Returned => 'مُرتجع',
            self::Missing => 'مفقود',
        };
    }

    public function color(): string
    {
        return match ($this) {
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
            self::Dispatched => 'heroicon-o-paper-airplane',
            self::Delivered => 'heroicon-o-check-circle',
            self::Delayed => 'heroicon-o-clock',
            self::Rejected => 'heroicon-o-x-circle',
            self::Returned => 'heroicon-o-arrow-uturn-left',
            self::Missing => 'heroicon-o-exclamation-triangle',
        };
    }
}
