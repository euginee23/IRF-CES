<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A record a customer can be messaged about.
 *
 * Quote requests and job orders hold the customer's details under different
 * column names and with opposite guarantees — a quote request always has an
 * email and may have no phone, a job order always has a phone and may have no
 * email. This contract is what lets one composer serve both: it asks for the
 * contact details rather than assuming either exists.
 *
 * @see \App\Services\Messaging\CustomerMessenger
 */
interface Contactable
{
    /** The customer's name, for greeting them. */
    public function contactName(): string;

    /** Null when this record has no email address on file. */
    public function contactEmail(): ?string;

    /** Null when this record has no phone number on file. */
    public function contactPhone(): ?string;

    /**
     * Values substituted into message templates, keyed without the colon.
     *
     * @return array<string, string>
     */
    public function messagePlaceholders(): array;

    /**
     * History of messages sent about this record.
     *
     * Satisfied by App\Models\Concerns\HasCustomerMessages.
     *
     * @return MorphMany<\App\Models\CustomerMessage, static>
     */
    public function customerMessages(): MorphMany;
}
