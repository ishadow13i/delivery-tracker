<?php

namespace App\Enums;

enum ScanType: string
{
    case Dispatch = 'dispatch';
    case Return = 'return';

    public function label(): string
    {
        return match ($this) {
            self::Dispatch => 'Dispatch',
            self::Return => 'Return',
        };
    }
}
