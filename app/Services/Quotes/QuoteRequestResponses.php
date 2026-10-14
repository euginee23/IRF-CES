<?php

namespace App\Services\Quotes;

use App\Models\RepairQuoteRequest;

/**
 * Records the customer's answer to a quote request.
 *
 * The portal buttons and the staff status dropdown both come through here,
 * so an answer always carries its date, its reason and how it was given —
 * 'customer' when they answered on the portal, 'manual' when staff recorded
 * it for them (over the phone, at the counter).
 */
class QuoteRequestResponses
{
    /** No answer on record — the state of a quote still waiting for one. */
    public const CLEARED = [
        'approved_at' => null,
        'declined_at' => null,
        'decline_reason' => null,
        'response_method' => null,
        'response_seen_at' => null,
    ];

    public function approve(RepairQuoteRequest $quoteRequest, string $method): void
    {
        $quoteRequest->update([
            'status' => 'approved',
            'approved_at' => now(),
            'declined_at' => null,
            'decline_reason' => null,
            'response_method' => $method,
            'response_seen_at' => $method === 'manual' ? now() : null,
        ]);
    }

    public function decline(RepairQuoteRequest $quoteRequest, string $method, ?string $reason = null): void
    {
        $quoteRequest->update([
            'status' => 'declined',
            'approved_at' => null,
            'declined_at' => now(),
            'decline_reason' => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
            'response_method' => $method,
            'response_seen_at' => $method === 'manual' ? now() : null,
        ]);
    }

    /**
     * Staff changing the status by hand. Approved and disapproved are
     * recorded as a manual answer; moving back to an earlier status clears
     * the previous answer so the customer can be asked again.
     */
    public function setStatus(RepairQuoteRequest $quoteRequest, string $status): void
    {
        match ($status) {
            'approved' => $this->approve($quoteRequest, 'manual'),
            'declined' => $this->decline($quoteRequest, 'manual'),
            default => $quoteRequest->update(['status' => $status] + self::CLEARED),
        };
    }
}
