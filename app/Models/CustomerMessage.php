<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A message staff sent to a customer, by email or SMS.
 *
 * Only successful sends are recorded — a failure is surfaced to the sender as
 * an error toast, and writing it here would misread as "the customer was told".
 */
class CustomerMessage extends Model
{
    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_SMS = 'sms';

    protected $fillable = [
        'channel',
        'recipient',
        'subject',
        'body',
        'template',
        'sent_by',
    ];

    public function messageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function isSms(): bool
    {
        return $this->channel === self::CHANNEL_SMS;
    }
}
