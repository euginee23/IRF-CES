<?php

namespace App\Exceptions;

use App\Enums\JobOrderStatus;
use RuntimeException;

class InvalidStatusTransition extends RuntimeException
{
    public static function between(JobOrderStatus $from, JobOrderStatus $to): self
    {
        return new self(
            "A job order cannot go from {$from->label()} to {$to->label()}."
        );
    }
}
