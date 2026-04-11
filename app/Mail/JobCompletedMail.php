<?php

namespace App\Mail;

use App\Models\JobOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public JobOrder $jobOrder,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Device is Ready for Pickup - ' . $this->jobOrder->job_order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.job-completed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
