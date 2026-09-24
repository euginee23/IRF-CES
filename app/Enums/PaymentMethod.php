<?php

namespace App\Enums;

/**
 * How a customer paid.
 *
 * GCash and Maya are named separately rather than lumped into "e-wallet"
 * because they reconcile against different accounts at the end of the day.
 */
enum PaymentMethod: string
{
    case CASH = 'cash';
    case GCASH = 'gcash';
    case MAYA = 'maya';
    case BANK_TRANSFER = 'bank_transfer';
    case CARD = 'card';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::GCASH => 'GCash',
            self::MAYA => 'Maya',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CARD => 'Card',
            self::OTHER => 'Other',
        };
    }

    /** Whether a reference number is worth asking for. */
    public function expectsReference(): bool
    {
        return $this !== self::CASH;
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
