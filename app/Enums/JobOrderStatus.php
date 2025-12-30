<?php

namespace App\Enums;

enum JobOrderStatus: string
{
    case PENDING = 'pending';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::ASSIGNED => 'Assigned',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'amber',
            self::ASSIGNED => 'blue',
            self::IN_PROGRESS => 'indigo',
            self::COMPLETED => 'green',
            self::DELIVERED => 'teal',
            self::CANCELLED => 'red',
        };
    }

    public function badgeClasses(): string
    {
        $color = $this->color();
        return "bg-{$color}-100 text-{$color}-800 ring-1 ring-{$color}-300 dark:bg-{$color}-900/40 dark:text-{$color}-300 dark:ring-{$color}-700/50";
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
