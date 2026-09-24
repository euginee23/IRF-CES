<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A free-form message composed by staff in the "Contact Customer" panel.
 *
 * Unlike the other mailables here, the wording is not fixed by a template —
 * staff pick a preset and then edit it, so the subject and body arrive as text.
 *
 * The properties are named messageSubject/messageBody rather than subject/body
 * because Mailable already declares an untyped $subject, and PHP forbids a
 * subclass from adding a type to an inherited property.
 */
class CustomerMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $messageSubject,
        public string $messageBody,
        public string $customerName = '',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->messageSubject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.customer-message');
    }

    public function attachments(): array
    {
        return [];
    }
}
