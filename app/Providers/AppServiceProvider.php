<?php

namespace App\Providers;

use App\Models\JobOrder;
use App\Models\Part;
use App\Observers\JobOrderObserver;
use App\Observers\PartObserver;
use App\Services\Sms\SmsManager;
use App\Services\Sms\SmsSender;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bound, not a singleton: the SMS tests change config and then forget
        // the SmsSender instance, and a cached manager would hand them a
        // driver built from the old config.
        $this->app->bind(SmsManager::class);

        $this->app->singleton(
            SmsSender::class,
            fn ($app) => $app->make(SmsManager::class)->driver(),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JobOrder::observe(JobOrderObserver::class);
        Part::observe(PartObserver::class);
    }
}
