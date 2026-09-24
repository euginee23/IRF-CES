<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Sends each message through whichever provider can reach that network.
 *
 * IPROG covers Globe, TM and DITO on its shared sender name, but Smart, TNT
 * and Sun — roughly 40% of the Philippine market — only accept traffic under
 * a custom sender name IPROG has approved for the account. Rather than wait
 * on that approval, this asks one provider which network a number is on and
 * hands the message to the provider configured for it.
 *
 * It never sends twice on success and never retries a message the provider
 * accepted, because a send costs credits and carries no idempotency key. The
 * fallback runs only when the primary refused the message outright.
 */
class RoutingSmsSender implements SmsSender
{
    /**
     * @param  array<string, string>  $map  Network name => driver name.
     */
    public function __construct(
        private readonly SmsManager $manager,
        private readonly string $detectorDriver = 'iprogsms',
        private readonly array $map = [],
        /** Driver used for an unknown network, or when the mapped one refuses. */
        private readonly string $fallback = '',
        private readonly bool $retryWithFallback = true,
        private readonly string $countryCode = '63',
        private readonly ?string $logChannel = 'sms',
    ) {}

    public function send(string $to, string $message): void
    {
        $normalised = PhoneNumber::toE164($to, $this->countryCode);

        if ($normalised === null) {
            throw new RuntimeException("Cannot send SMS: \"{$to}\" is not a usable phone number.");
        }

        $network = $this->networkFor($normalised);
        $primary = $this->driverFor($network);

        try {
            $this->manager->driver($primary)->send($normalised, $message);
            $this->record($normalised, $network, $primary, null);

            return;
        } catch (Throwable $e) {
            $fallback = $this->fallbackFor($primary);

            // Nothing left to try: surface the provider's own reason rather
            // than a routing message, because that is what tells staff why.
            if (! $this->retryWithFallback || $fallback === null) {
                throw $e;
            }

            $this->manager->driver($fallback)->send($normalised, $message);
            $this->record($normalised, $network, $fallback, $primary);
        }
    }

    /**
     * Which network the number is on, or null when nothing can say.
     *
     * A failure here is not a delivery problem — the detector may be
     * unconfigured, offline, or simply not list the prefix — so it resolves
     * to the fallback driver rather than refusing the message.
     */
    private function networkFor(string $normalised): ?string
    {
        $detector = $this->manager->detector($this->detectorDriver);

        if (! $detector) {
            return null;
        }

        try {
            $detected = $detector->detectNetwork($normalised);
        } catch (Throwable) {
            return null;
        }

        $network = $detected['network'] ?? null;

        // "Unknown Network" is the detector saying it has no answer, not that
        // the number is unreachable, so it routes like an undetected one.
        return $network === IprogSmsSender::NETWORK_UNKNOWN ? null : $network;
    }

    private function driverFor(?string $network): string
    {
        $driver = $network !== null ? ($this->map[$network] ?? null) : null;

        $driver ??= $this->fallback !== '' ? $this->fallback : null;

        if ($driver === null) {
            throw new RuntimeException(
                'No SMS driver is configured for '
                .($network !== null ? "the {$network} network" : 'an unrecognised network')
                .', and no fallback driver is set. Set SMS_ROUTE_FALLBACK.'
            );
        }

        return $driver;
    }

    /** The fallback, unless it is the driver that already refused. */
    private function fallbackFor(string $attempted): ?string
    {
        if ($this->fallback === '' || $this->fallback === $attempted) {
            return null;
        }

        return $this->fallback;
    }

    private function record(string $to, ?string $network, string $driver, ?string $failedDriver): void
    {
        if ($this->logChannel === null) {
            return;
        }

        Log::channel($this->logChannel)->info('SMS routed', [
            'to' => $to,
            'network' => $network ?? 'undetected',
            'driver' => $driver,
            // Present only when the first choice refused, which is the thing
            // worth noticing in the log.
            'after_failure_of' => $failedDriver,
        ]);
    }
}
