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

            // Audit trail of real sends: recipient and IPROG message id only.
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
