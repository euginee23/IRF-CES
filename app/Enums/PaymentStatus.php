<?php

namespace App\Enums;

/**
 * Where a job order stands on being paid.
 *
 * Derived from the payments against it rather than stored, so it cannot drift
 * out of step with the money actually recorded.
 */
enum PaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PARTIAL = 'partial';
    case PAID = 'paid';

    /** Took more than was owed — a refund is due, and staff should see that. */
    case OVERPAID = 'overpaid';

    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'Unpaid',
            self::PARTIAL => 'Partially Paid',
            self::PAID => 'Paid',
            self::OVERPAID => 'Overpaid',
        };
    }

    public function badgeClasses(): string
    {
        // Spelled out rather than interpolated, so Tailwind's source scan
        // finds them. See JobOrderStatus::badgeClasses().
        return match ($this) {
            self::UNPAID => 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/30',
            self::PARTIAL => 'text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30',
            self::PAID => 'text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/30',
            self::OVERPAID => 'text-purple-700 bg-purple-100 dark:text-purple-300 dark:bg-purple-900/30',
        };
    }

    public function isSettled(): bool
    {
        return $this === self::PAID || $this === self::OVERPAID;
    }
}
