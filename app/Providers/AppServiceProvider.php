<?php

namespace App\Providers;

use App\Services\Sms\IprogSmsSender;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\NullSmsSender;
use App\Services\Sms\SmsSender;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsSender::class, function () {
            $name = (string) config('sms.default');

            // .env reads a bare `null` as an empty value, so SMS_DRIVER=null
            // arrives here blank rather than as a driver name.
            if ($name === '') {
                throw new InvalidArgumentException(
                    'No SMS driver configured. Set SMS_DRIVER=iprogsms to send, SMS_DRIVER=log to record '
                    . 'messages without sending, or SMS_DRIVER=none to disable sending. '
                    . 'Note that SMS_DRIVER=null is read as an empty value.'
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
                    logChannel: $config['log_channel'] ?? 'sms',
                ),
                'log' => new LogSmsSender(
                    channel: $config['channel'] ?? 'sms',
                    from: (string) config('sms.from'),
                    countryCode: (string) config('sms.country_code', '63'),
                ),
                'none' => new NullSmsSender(),
                default => throw new InvalidArgumentException("Unsupported SMS driver [{$name}]."),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
