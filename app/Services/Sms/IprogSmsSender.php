<?php

namespace App\Services\Sms;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Sends messages through IPROG SMS (https://sms.iprogtech.com).
 *
 * The account exposes one credential — an API token — and no sender ID, so
 * `sms.from` is unused here. Custom sender names are arranged with IPROG and
 * applied on their side; Smart/TNT require an approved one.
 */
class IprogSmsSender implements SmsSender
{
    /** Root of the IPROG SMS v1 API. */
    public const BASE_URL = 'https://sms.iprogtech.com/api/v1';

    public function __construct(
        private readonly string $token,
        private readonly string $baseUrl = self::BASE_URL,
        private readonly ?int $provider = null,
        private readonly string $countryCode = '63',
        private readonly int $timeout = 15,
        private readonly ?string $logChannel = 'sms',
    ) {
    }

    public function send(string $to, string $message): void
    {
        if (trim($this->token) === '') {
            throw new RuntimeException('Cannot send SMS: IPROGSMS_TOKEN is not set.');
        }

        $normalised = PhoneNumber::toE164($to, $this->countryCode);

        if ($normalised === null) {
            throw new RuntimeException("Cannot send SMS: \"{$to}\" is not a usable phone number.");
        }

        $payload = [
            'api_token' => $this->token,
            // IPROG accepts "09XXXXXXXXX" or "+639XXXXXXXXX"; E.164 is the
            // latter, so the normalised number goes over the wire unchanged.
            'phone_number' => $normalised,
            'message' => $message,
        ];

        if ($this->provider !== null) {
            $payload['sms_provider'] = $this->provider;
        }

        try {
            // Deliberately no retry: a send costs credits and carries no
            // idempotency key, so a request that timed out *after* IPROG
            // accepted it would be delivered twice.
            $response = Http::asForm()
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/sms_messages", $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException("Could not reach IPROG SMS: {$e->getMessage()}", previous: $e);
        }

        $body = $response->json();

        if (! $response->successful() || ! is_array($body)) {
            throw new RuntimeException(
                "IPROG SMS returned HTTP {$response->status()}: " . Str::limit($response->body(), 200)
            );
        }

        // IPROG answers HTTP 200 even when a send fails, so the body's "status"
        // is the real outcome: 200 on success, otherwise 500 or "error".
        if ((int) ($body['status'] ?? 0) !== 200) {
            throw new RuntimeException('IPROG SMS rejected the message: ' . self::describeError($body));
        }

        if ($this->logChannel !== null) {
            // The recipient and provider reference are kept as an audit trail;
            // the message body is not, so customer text stays out of the logs
            // now that sends are real.
            Log::channel($this->logChannel)->info('SMS sent', [
                'driver' => 'iprogsms',
                'to' => $normalised,
                'to_raw' => $to,
                'message_id' => $body['message_id'] ?? null,
                'length' => mb_strlen($message),
            ]);
        }
    }

    /**
     * Current account balance in credits, or null when it cannot be read.
     *
     * Reported rather than thrown: this is a pre-flight courtesy for the
     * `test:sms` command, and an unreadable balance should not block a send.
     */
    public function credits(): ?float
    {
        if (trim($this->token) === '') {
            return null;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/account/sms_credits", ['api_token' => $this->token]);
        } catch (ConnectionException) {
            return null;
        }

        $balance = $response->json('data.load_balance');

        return is_numeric($balance) ? (float) $balance : null;
    }

    /**
     * Flatten IPROG's error shapes into one sentence.
     *
     * Failures arrive either as {"status": 500, "message": "Invalid Token"} or
     * as {"status": "error", "message": ..., "data": {"errors": [...]}}.
     *
     * @param  array<string, mixed>  $body
     */
    private static function describeError(array $body): string
    {
        $message = is_string($body['message'] ?? null) && $body['message'] !== ''
            ? $body['message']
            : 'no reason given (status ' . json_encode($body['status'] ?? null) . ').';

        $errors = $body['data']['errors'] ?? null;

        if (is_array($errors) && $errors !== []) {
            $message .= ' (' . implode('; ', array_map('strval', $errors)) . ')';
        }

        return $message;
    }
}
