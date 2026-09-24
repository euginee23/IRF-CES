<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default SMS Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default driver used to send all SMS messages.
    | Set SMS_DRIVER=iprogsms to send for real; "log" records each message
    | instead of sending it, which keeps local and CI runs free of charge.
    |
    | Supported: "iprogsms", "log", "none"
    |
    | Use "none" (not "null") to disable sending — .env reads a bare `null`
    | as an empty value, which is not a driver name.
    |
    */

    'default' => env('SMS_DRIVER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Global "From" Sender ID
    |--------------------------------------------------------------------------
    |
    | The sender name recipients see. Recorded on every logged message, but
    | NOT sent to IPROG: their API takes no sender field. A custom sender name
    | is requested from IPROG and applied on their side, so changing this does
    | not change what recipients see on a real send.
    |
    */

    'from' => env('SMS_FROM', env('APP_NAME', 'IRF-CES')),

    /*
    |--------------------------------------------------------------------------
    | Default Country Code
    |--------------------------------------------------------------------------
    |
    | Used to normalise local numbers to E.164 before sending. Customer phone
    | numbers are stored in mixed formats ("09171234567" and "+63 917 123
    | 4567"), so they are normalised to one form. 63 = Philippines.
    |
    */

    'country_code' => env('SMS_COUNTRY_CODE', '63'),

    /*
    |--------------------------------------------------------------------------
    | SMS Drivers
    |--------------------------------------------------------------------------
    |
    | Each driver below may be configured independently. The "log" driver
    | mirrors Laravel's log mailer: nothing leaves the application, every
    | message is written to the configured log channel instead.
    |
    */

    'drivers' => [

        'iprogsms' => [
            'driver' => 'iprogsms',

            // The only credential the IPROG dashboard issues. Every endpoint
            // authenticates with it as a plain request parameter.
            'token' => env('IPROGSMS_TOKEN'),

            'base_url' => env('IPROGSMS_URL', \App\Services\Sms\IprogSmsSender::BASE_URL),

            // Which upstream telco route IPROG should use: 0, 1 or 2. Left
            // unset so IPROG applies its own default (0).
            'provider' => env('IPROGSMS_PROVIDER'),

            'timeout' => env('IPROGSMS_TIMEOUT', 15),

            // IPROG reaches Globe, TM and DITO with its shared sender name.
            // Smart, TNT and Sun only accept traffic under a custom sender
            // name IPROG has approved for the account — about 40% of the
            // market. While this is false, those numbers are refused instead
            // of being charged for a message that never arrives. Set it true
            // once IPROG confirms an approved sender name.
            'sender_name_approved' => env('IPROGSMS_SENDER_NAME_APPROVED', false),

            // IPROG prepends the account's sender name and a space to the body
            // of every message, and those characters count towards the 160 in
            // a billed segment. Set this to the exact name IPROG uses so the
            // composer counts the real budget rather than promising 160.
            // Confirmed by reading back a sent message: a body of "Test."
            // arrived as "IRF-CES Repair System Test.".
            'sender_name' => env('IPROGSMS_SENDER_NAME', ''),

            // Network lookups are cached this long. Not forever: a number can
            // be ported to another network. Seconds; default 7 days.
            'network_cache_ttl' => env('IPROGSMS_NETWORK_CACHE_TTL', 604800),

            // Audit trail of real sends: recipient and IPROG message id only.
            'log_channel' => env('SMS_LOG_CHANNEL', 'sms'),
        ],

        /*
         * Picks a provider per message, by the recipient's network.
         *
         * Set SMS_DRIVER=routing to use it. IPROG keeps Globe, TM and DITO;
         * Smart, TNT and Sun go to a provider that can actually carry them,
         * instead of being refused for want of an approved sender name.
         */
        'routing' => [
            'driver' => 'routing',

            // Which provider is asked what network a number is on. It need
            // not be the one that ends up sending.
            'detector' => env('SMS_DETECTOR', 'iprogsms'),

            'map' => [
                \App\Services\Sms\IprogSmsSender::NETWORK_GLOBE => env('SMS_ROUTE_GLOBE', 'iprogsms'),
                \App\Services\Sms\IprogSmsSender::NETWORK_DITO => env('SMS_ROUTE_DITO', 'iprogsms'),
                \App\Services\Sms\IprogSmsSender::NETWORK_SMART => env('SMS_ROUTE_SMART', 'semaphore'),
            ],

            // Used when the network cannot be determined, and as the second
            // attempt when the mapped provider refuses the message.
            'fallback' => env('SMS_ROUTE_FALLBACK', 'semaphore'),

            // A second attempt costs a second credit, so it is worth being
            // able to switch off.
            'retry_with_fallback' => env('SMS_ROUTE_RETRY', true),

            'log_channel' => env('SMS_LOG_CHANNEL', 'sms'),
        ],

        /*
         * Semaphore (https://semaphore.co) — reaches Smart, TNT and Sun on a
         * shared sender name with nothing to register in advance, which is
         * why it is the second provider rather than a second IPROG account.
         */
        'semaphore' => [
            'driver' => 'semaphore',

            'api_key' => env('SEMAPHORE_API_KEY'),

            'base_url' => env('SEMAPHORE_URL', \App\Services\Sms\SemaphoreSmsSender::BASE_URL),

            // Unlike IPROG, Semaphore does take a sender name per request.
            // Blank uses the account default, which needs no approval.
            'sender_name' => env('SEMAPHORE_SENDER_NAME', ''),

            'timeout' => env('SEMAPHORE_TIMEOUT', 15),

            'log_channel' => env('SMS_LOG_CHANNEL', 'sms'),
        ],

        'log' => [
            'driver' => 'log',
            'channel' => env('SMS_LOG_CHANNEL', 'sms'),
        ],

        // Discards every message — SMS switched off without removing the calls.
        'none' => [
            'driver' => 'none',
        ],

        // Alias, for anyone reaching for Laravel's usual "null" naming.
        'null' => [
            'driver' => 'none',
        ],

        // To add another provider: write a sender class implementing
        // App\Services\Sms\SmsSender, add its config here, and register it in
        // AppServiceProvider's match on the driver name.

    ],

];
