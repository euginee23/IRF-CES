<?php

namespace App\Mail;

use App\Models\RepairQuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RepairQuoteRequest $quoteRequest,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Repair Quote - ' . $this->quoteRequest->manufacturer . ' ' . $this->quoteRequest->model,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-request',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
