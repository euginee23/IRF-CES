<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a device would leave the shop with money still owed.
 */
class UnpaidBalance extends RuntimeException
{
    public function __construct(public readonly float $balance)
    {
        parent::__construct(
            'PHP '.number_format($balance, 2).' is still owed on this repair. '
            .'Take the balance first, or ask an administrator to release it.'
        );
    }
}
