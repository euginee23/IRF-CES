<?php

namespace App\Services\Messaging;

use App\Contracts\Contactable;
use App\Facades\Sms;
use App\Mail\CustomerMessageMail;
use App\Models\CustomerMessage;
use App\Services\Sms\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Sends a staff-composed message to a customer and records what was sent.
 *
 * The record is written only after the send succeeds, so the history answers
 * "what was this customer actually told" rather than "what did we attempt".
 */
class CustomerMessenger
{
    /**
     * @param  Contactable&Model  $record
     *
     * @throws RuntimeException When the channel is unusable or the send fails.
     */
    public function send(
        Contactable $record,
        string $channel,
        string $body,
        string $subject = '',
        ?string $template = null,
    ): CustomerMessage {
        $body = trim($body);

        if ($body === '') {
            throw new RuntimeException('Cannot send an empty message.');
        }

        $recipient = match ($channel) {
            CustomerMessage::CHANNEL_SMS => $this->smsRecipient($record),
            CustomerMessage::CHANNEL_EMAIL => $this->emailRecipient($record),
            default => throw new RuntimeException("Unsupported message channel [{$channel}]."),
        };

        if ($channel === CustomerMessage::CHANNEL_SMS) {
            Sms::send($recipient, $body);
        } else {
            $subject = trim($subject);

            if ($subject === '') {
                throw new RuntimeException('An email needs a subject.');
            }

            Mail::to($recipient)->send(new CustomerMessageMail(
                messageSubject: $subject,
                messageBody: $body,
                customerName: $record->contactName(),
            ));
        }

        return $record->customerMessages()->create([
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $channel === CustomerMessage::CHANNEL_EMAIL ? $subject : null,
            'body' => $body,
            'template' => $template,
            'sent_by' => Auth::id(),
        ]);
    }

    /**
     * The normalised number, which is also what gets recorded — the raw column
     * holds mixed formats, so storing what the provider was actually given is
     * what makes a "they never got it" dispute answerable.
     */
    private function smsRecipient(Contactable $record): string
    {
        $phone = $record->contactPhone();

        if ($phone === null || trim($phone) === '') {
            throw new RuntimeException('This customer has no phone number on file, so SMS is not available.');
        }

        $normalised = PhoneNumber::toE164($phone, (string) config('sms.country_code', '63'));

        if ($normalised === null) {
            throw new RuntimeException("\"{$phone}\" is not a usable phone number.");
        }

        return $normalised;
    }

    private function emailRecipient(Contactable $record): string
    {
        $email = $record->contactEmail();

        if ($email === null || trim($email) === '') {
            throw new RuntimeException('This customer has no email address on file, so email is not available.');
        }

        return trim($email);
    }
}
