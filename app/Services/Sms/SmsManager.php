<?php

namespace App\Services\Sms;

use InvalidArgumentException;

/**
 * Builds SMS senders by driver name.
 *
 * This was the closure in AppServiceProvider. It moved here because routing
 * needs to build a *second* sender on demand — "this number is on Smart, hand
 * it to the provider that can reach Smart" — which a single container binding
 * cannot do.
 *
 * Deliberately not a singleton binding, and it caches nothing: the tests
 * change sms config and then forget the SmsSender instance, and a manager
 * holding a built driver would serve them a stale one.
 */
class SmsManager
{
    /**
     * @param  string|null  $name  A driver name from config('sms.drivers'), or
     *                             null for the configured default.
     */
    public function driver(?string $name = null): SmsSender
    {
        $name = (string) ($name ?? config('sms.default'));

        // .env reads a bare `null` as an empty value, so SMS_DRIVER=null
        // arrives here blank rather than as a driver name.
        if ($name === '') {
            throw new InvalidArgumentException(
                'No SMS driver configured. Set SMS_DRIVER=iprogsms to send, SMS_DRIVER=log to record '
                .'messages without sending, or SMS_DRIVER=none to disable sending. '
                .'Note that SMS_DRIVER=null is read as an empty value.'
            );
        }

        $config = config("sms.drivers.{$name}", []);

        return match ($config['driver'] ?? $name) {
            'iprogsms' => new IprogSmsSender(
                token: (string) ($config['token'] ?? ''),
                baseUrl: (string) (($config['base_url'] ?? '') ?: IprogSmsSender::BASE_URL),
                provider: is_numeric($config['provider'] ?? null) ? (int) $config['provider'] : null,
                countryCode: (string) config('sms.country_code', '63'),
                timeout: (int) ($config['timeout'] ?? 15),
                // array_key_exists, not ??: an explicit null means "do not
                // log", which ?? would quietly turn back into the channel.
                logChannel: array_key_exists('log_channel', $config)
                    ? $config['log_channel']
                    : 'sms',
                senderNameApproved: (bool) ($config['sender_name_approved'] ?? false),
                networkCacheTtl: (int) ($config['network_cache_ttl'] ?? 604800),
            ),
            'semaphore' => new SemaphoreSmsSender(
                apiKey: (string) ($config['api_key'] ?? ''),
                baseUrl: (string) (($config['base_url'] ?? '') ?: SemaphoreSmsSender::BASE_URL),
                senderName: (string) ($config['sender_name'] ?? ''),
                countryCode: (string) config('sms.country_code', '63'),
                timeout: (int) ($config['timeout'] ?? 15),
                logChannel: array_key_exists('log_channel', $config)
                    ? $config['log_channel']
                    : 'sms',
            ),
            'routing' => new RoutingSmsSender(
                manager: $this,
                detectorDriver: (string) ($config['detector'] ?? 'iprogsms'),
                map: (array) ($config['map'] ?? []),
                fallback: (string) ($config['fallback'] ?? ''),
                retryWithFallback: (bool) ($config['retry_with_fallback'] ?? true),
                countryCode: (string) config('sms.country_code', '63'),
                logChannel: array_key_exists('log_channel', $config)
                    ? $config['log_channel']
                    : 'sms',
            ),
            'log' => new LogSmsSender(
                channel: $config['channel'] ?? 'sms',
                from: (string) config('sms.from'),
                countryCode: (string) config('sms.country_code', '63'),
            ),
            'none' => new NullSmsSender,
            default => throw new InvalidArgumentException("Unsupported SMS driver [{$name}]."),
        };
    }

    /**
     * The detector for a driver name, or null when that driver cannot detect.
     *
     * The log and none drivers cannot, which is why routing falls back to
     * sending everything through one driver in local and CI runs instead of
     * failing.
     */
    public function detector(?string $name = null): ?NetworkDetector
    {
        $sender = $this->driver($name);

        return $sender instanceof NetworkDetector ? $sender : null;
    }
}
