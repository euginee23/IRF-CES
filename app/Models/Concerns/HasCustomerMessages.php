<?php

namespace App\Models\Concerns;

use App\Models\CustomerMessage;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Satisfies the customerMessages() half of App\Contracts\Contactable.
 *
 * @see \App\Contracts\Contactable
 */
trait HasCustomerMessages
{
    public function customerMessages(): MorphMany
    {
        // Tie-broken by id: two messages sent in the same second would
        // otherwise come back in whatever order the database felt like, which
        // reads as the history being wrong.
        return $this->morphMany(CustomerMessage::class, 'messageable')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
