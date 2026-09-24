<?php

namespace App\Services\Sms;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Sends messages through Semaphore (https://semaphore.co).
 *
 * Here to reach Smart, TNT and Sun, which IPROG will not carry without an
 * approved custom sender name. Semaphore delivers to those on its own shared
 * sender name, with no pre-registration, which is the whole reason it is the
 * second provider.
 *
 * @see \App\Services\Sms\RoutingSmsSender
 */
class SemaphoreSmsSender implements SmsSender
{
    public const BASE_URL = 'https://api.semaphore.co/api/v4';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = self::BASE_URL,
        /** Blank uses the account's default, which needs no registration. */
        private readonly string $senderName = '',
        private readonly string $countryCode = '63',
        private readonly int $timeout = 15,
        private readonly ?string $logChannel = 'sms',
    ) {}

    public function send(string $to, string $message): void
    {
        if (trim($this->apiKey) === '') {
            throw new RuntimeException('Cannot send SMS: SEMAPHORE_API_KEY is not set.');
        }

        $normalised = PhoneNumber::toE164($to, $this->countryCode);

        if ($normalised === null) {
            throw new RuntimeException("Cannot send SMS: \"{$to}\" is not a usable phone number.");
        }

        $payload = [
            'apikey' => $this->apiKey,
            'number' => $normalised,
            'message' => $message,
        ];

        if (trim($this->senderName) !== '') {
            $payload['sendername'] = $this->senderName;
        }

        try {
            // No retry, for the same reason as IPROG: a send costs credits and
            // carries no idempotency key, so a request that timed out after
            // Semaphore accepted it would be delivered twice.
            $response = Http::asForm()
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/messages", $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException("Could not reach Semaphore: {$e->getMessage()}", previous: $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                "Semaphore returned HTTP {$response->status()}: ".Str::limit($response->body(), 200)
            );
        }

        $body = $response->json();

        // A successful send answers with a list of one entry per recipient.
        // Anything else — including an object of validation errors — means
        // nothing was queued.
        if (! is_array($body) || $body === []) {
            throw new RuntimeException('Semaphore rejected the message: '.Str::limit($response->body(), 200));
        }

        $first = is_array($body[0] ?? null) ? $body[0] : null;

        if ($first === null) {
            throw new RuntimeException('Semaphore rejected the message: '.self::describeError($body));
        }

        $status = strtolower((string) ($first['status'] ?? ''));

        if ($status === 'failed' || $status === 'refunded') {
            throw new RuntimeException("Semaphore could not deliver the message (status: {$status}).");
        }

        if ($this->logChannel !== null) {
            // Recipient and provider reference only; the message body stays
            // out of the logs now that sends are real.
            Log::channel($this->logChannel)->info('SMS sent', [
                'driver' => 'semaphore',
                'to' => $normalised,
                'to_raw' => $to,
                'message_id' => $first['message_id'] ?? null,
                'length' => mb_strlen($message),
            ]);
        }
    }

    /**
     * Semaphore reports validation problems as {"field": ["reason", ...]}.
     *
     * @param  array<mixed>  $body
     */
    private static function describeError(array $body): string
    {
        $parts = [];

        foreach ($body as $field => $reasons) {
            $reasons = is_array($reasons) ? $reasons : [$reasons];

            foreach ($reasons as $reason) {
                $parts[] = is_string($field) ? "{$field}: {$reason}" : (string) $reason;
            }
        }

        return $parts === [] ? 'no reason given' : implode('; ', $parts);
    }
}
