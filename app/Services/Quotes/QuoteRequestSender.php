<?php

namespace App\Services\Quotes;

use App\Mail\QuoteRequestMail;
use App\Models\RepairQuoteRequest;
use Illuminate\Support\Facades\Mail;

/**
 * Prices a quote request and emails it to the customer.
 *
 * The email carries the Approve / Disapprove buttons, which only work once
 * the request has a portal token — so pricing and sending live together
 * here, used by both the counter and the admin screens. The admin screen
 * previously marked a request "quoted" without either, which left the
 * customer with nothing to respond to.
 */
class QuoteRequestSender
{
    /** Every status a quote request can be in, in workflow order. */
    public const STATUSES = ['pending', 'reviewed', 'quoted', 'approved', 'declined'];

    public function send(RepairQuoteRequest $quoteRequest, float $price, ?string $notes = null): void
    {
        $quoteRequest->update([
            'quoted_price' => $price,
            'quote_notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
            'quoted_at' => now(),
            'portal_token' => $quoteRequest->portal_token ?? bin2hex(random_bytes(32)),
            'status' => 'quoted',
        ]);

        Mail::to($quoteRequest->email)->send(new QuoteRequestMail($quoteRequest));
    }
}
