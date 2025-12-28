<?php

namespace App\Enums;

enum Role: string
{
    case ADMINISTRATOR = 'administrator';
    case TECHNICIAN = 'technician';
    case COUNTER_STAFF = 'counter_staff';

    public function label(): string
    {
        return match($this) {
            self::ADMINISTRATOR => 'Administrator',
            self::TECHNICIAN => 'Technician',
            self::COUNTER_STAFF => 'Counter Staff',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
