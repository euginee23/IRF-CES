<?php

namespace App\Mail;

use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class RestockRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  Collection<int, array{part: \App\Models\Part, quantity: int}>  $items
     */
    public function __construct(
        public Supplier $supplier,
        public Collection $items,
        public string $additionalNotes = '',
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->items->count() === 1
                ? 'Restock Request for ' . $this->items->first()['part']->name
                : 'Restock Request for ' . $this->items->count() . ' items',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.restock-request',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
