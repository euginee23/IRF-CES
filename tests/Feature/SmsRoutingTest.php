<?php

use App\Services\Sms\IprogSmsSender;
use App\Services\Sms\RoutingSmsSender;
use App\Services\Sms\SemaphoreSmsSender;
use App\Services\Sms\SmsManager;
use App\Services\Sms\SmsSender;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** Build the routing driver from config, with the container instance forgotten. */
function router(array $overrides = []): SmsSender
{
    config()->set('sms.default', 'routing');
    config()->set('sms.drivers.iprogsms.token', 'iprog-token');
    config()->set('sms.drivers.semaphore.api_key', 'semaphore-key');

    foreach ($overrides as $key => $value) {
        config()->set($key, $value);
    }

    app()->forgetInstance(SmsSender::class);
    Cache::flush();

    return app(SmsSender::class);
}

/** IPROG's network lookup answering with a given network. */
function fakeNetwork(string $network, bool $isSmartTnt = false): void
{
    Http::fake([
        'sms.iprogtech.com/api/v1/phone_numbers/detect' => Http::response([
            'data' => ['network' => $network, 'is_smart_tnt' => $isSmartTnt],
        ]),
        'sms.iprogtech.com/api/v1/sms_messages' => Http::response(['status' => 200, 'message_id' => 'ip-1']),
        'api.semaphore.co/api/v4/messages' => Http::response([
            ['message_id' => 'sem-1', 'status' => 'Queued'],
        ]),
    ]);
}

test('the routing driver resolves from config', function () {
    expect(router())->toBeInstanceOf(RoutingSmsSender::class);
});

test('a Globe number goes to IPROG', function () {
    fakeNetwork(IprogSmsSender::NETWORK_GLOBE);

    router()->send('09171234567', 'Your repair is ready.');

    Http::assertSent(fn ($r) => str_contains($r->url(), 'iprogtech.com/api/v1/sms_messages'));
    Http::assertNotSent(fn ($r) => str_contains($r->url(), 'semaphore.co'));
});

test('a DITO number goes to IPROG', function () {
    fakeNetwork(IprogSmsSender::NETWORK_DITO);

    router()->send('09951234567', 'Your repair is ready.');

    Http::assertSent(fn ($r) => str_contains($r->url(), 'iprogtech.com/api/v1/sms_messages'));
});

test('a Smart number goes to Semaphore instead of being refused', function () {
    // This is the whole point of the phase: IPROG would throw for this number.
    fakeNetwork(IprogSmsSender::NETWORK_SMART, isSmartTnt: true);

    router()->send('09181234567', 'Your repair is ready.');

    Http::assertSent(fn ($r) => str_contains($r->url(), 'semaphore.co/api/v4/messages'));
    Http::assertNotSent(fn ($r) => str_contains($r->url(), 'iprogtech.com/api/v1/sms_messages'));
});

test('the number reaches the provider in E.164', function () {
    fakeNetwork(IprogSmsSender::NETWORK_SMART, isSmartTnt: true);

    router()->send('0918 123 4567', 'Hello.');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'semaphore.co')
            && $request['number'] === '+639181234567';
    });
});

test('an unknown network falls back rather than being refused', function () {
    // 0952 comes back as "Unknown Network" and delivers fine, so an
    // unrecognised prefix must not block the send.
    fakeNetwork(IprogSmsSender::NETWORK_UNKNOWN);

    router()->send('09521234567', 'Your repair is ready.');

    Http::assertSent(fn ($r) => str_contains($r->url(), 'semaphore.co/api/v4/messages'));
});

test('a detector that cannot answer falls back', function () {
    Http::fake([
        'sms.iprogtech.com/api/v1/phone_numbers/detect' => Http::response([], 500),
        'api.semaphore.co/api/v4/messages' => Http::response([['message_id' => 'sem-1', 'status' => 'Queued']]),
    ]);

    router()->send('09171234567', 'Your repair is ready.');

    Http::assertSent(fn ($r) => str_contains($r->url(), 'semaphore.co/api/v4/messages'));
});

test('a provider that refuses is retried once on the fallback', function () {
    Http::fake([
        'sms.iprogtech.com/api/v1/phone_numbers/detect' => Http::response([
            'data' => ['network' => IprogSmsSender::NETWORK_GLOBE, 'is_smart_tnt' => false],
        ]),
        // IPROG answers HTTP 200 even when a send fails.
        'sms.iprogtech.com/api/v1/sms_messages' => Http::response(['status' => 500, 'message' => 'Out of credits']),
        'api.semaphore.co/api/v4/messages' => Http::response([['message_id' => 'sem-1', 'status' => 'Queued']]),
    ]);

    router()->send('09171234567', 'Your repair is ready.');

    Http::assertSent(fn ($r) => str_contains($r->url(), 'semaphore.co/api/v4/messages'));
});

test('with the retry switched off the provider error is surfaced', function () {
    Http::fake([
        'sms.iprogtech.com/api/v1/phone_numbers/detect' => Http::response([
            'data' => ['network' => IprogSmsSender::NETWORK_GLOBE, 'is_smart_tnt' => false],
        ]),
        'sms.iprogtech.com/api/v1/sms_messages' => Http::response(['status' => 500, 'message' => 'Out of credits']),
        'api.semaphore.co/api/v4/messages' => Http::response([['message_id' => 'sem-1', 'status' => 'Queued']]),
    ]);

    $sender = router(['sms.drivers.routing.retry_with_fallback' => false]);

    expect(fn () => $sender->send('09171234567', 'Hello.'))
        ->toThrow(RuntimeException::class, 'Out of credits');

    Http::assertNotSent(fn ($r) => str_contains($r->url(), 'semaphore.co'));
});

test('a successful send is never sent twice', function () {
    fakeNetwork(IprogSmsSender::NETWORK_GLOBE);

    router()->send('09171234567', 'Your repair is ready.');

    // A credit per attempt, and no idempotency key — a duplicate here is a
    // message the customer receives twice.
    Http::assertSentCount(2); // one detect, one send
});

test('a number that cannot be normalised is refused before any provider is called', function () {
    fakeNetwork(IprogSmsSender::NETWORK_GLOBE);

    expect(fn () => router()->send('   ', 'Hello.'))
        ->toThrow(RuntimeException::class, 'not a usable phone number');

    Http::assertNothingSent();
});

// -- The Semaphore provider on its own -------------------------------------

test('semaphore posts the documented form fields', function () {
    Http::fake(['api.semaphore.co/api/v4/messages' => Http::response([['message_id' => 'sem-1', 'status' => 'Queued']])]);

    (new SemaphoreSmsSender(apiKey: 'key-123', senderName: 'IRFCES'))
        ->send('09181234567', 'Ready for pickup.');

    Http::assertSent(function ($request) {
        return $request['apikey'] === 'key-123'
            && $request['number'] === '+639181234567'
            && $request['message'] === 'Ready for pickup.'
            && $request['sendername'] === 'IRFCES';
    });
});

test('semaphore omits the sender name when none is set, using the account default', function () {
    Http::fake(['api.semaphore.co/api/v4/messages' => Http::response([['message_id' => 'sem-1', 'status' => 'Queued']])]);

    (new SemaphoreSmsSender(apiKey: 'key-123'))->send('09181234567', 'Hello.');

    Http::assertSent(fn ($request) => ! isset($request['sendername']));
});

test('semaphore without an api key refuses rather than calling out', function () {
    Http::fake();

    expect(fn () => (new SemaphoreSmsSender(apiKey: ''))->send('09181234567', 'Hello.'))
        ->toThrow(RuntimeException::class, 'SEMAPHORE_API_KEY is not set');

    Http::assertNothingSent();
});

test('semaphore validation errors are reported with their reason', function () {
    Http::fake([
        'api.semaphore.co/api/v4/messages' => Http::response(['number' => ['The number field is invalid.']]),
    ]);

    expect(fn () => (new SemaphoreSmsSender(apiKey: 'key-123'))->send('09181234567', 'Hello.'))
        ->toThrow(RuntimeException::class, 'The number field is invalid.');
});

test('a failed semaphore status is treated as a failure', function () {
    Http::fake([
        'api.semaphore.co/api/v4/messages' => Http::response([['message_id' => 'sem-1', 'status' => 'Failed']]),
    ]);

    expect(fn () => (new SemaphoreSmsSender(apiKey: 'key-123'))->send('09181234567', 'Hello.'))
        ->toThrow(RuntimeException::class, 'could not deliver');
});

test('an http error from semaphore is reported', function () {
    Http::fake(['api.semaphore.co/api/v4/messages' => Http::response('Gateway timeout', 504)]);

    expect(fn () => (new SemaphoreSmsSender(apiKey: 'key-123'))->send('09181234567', 'Hello.'))
        ->toThrow(RuntimeException::class, 'HTTP 504');
});

// -- The manager -----------------------------------------------------------

test('the manager builds each configured driver', function () {
    config()->set('sms.drivers.iprogsms.token', 'x');
    config()->set('sms.drivers.semaphore.api_key', 'y');

    $manager = app(SmsManager::class);

    expect($manager->driver('iprogsms'))->toBeInstanceOf(IprogSmsSender::class)
        ->and($manager->driver('semaphore'))->toBeInstanceOf(SemaphoreSmsSender::class)
        ->and($manager->driver('routing'))->toBeInstanceOf(RoutingSmsSender::class);
});

test('only a provider that can detect is offered as a detector', function () {
    $manager = app(SmsManager::class);

    expect($manager->detector('iprogsms'))->toBeInstanceOf(IprogSmsSender::class)
        // The log driver has nothing to ask, which is why routing degrades to
        // the fallback in local and CI runs instead of erroring.
        ->and($manager->detector('log'))->toBeNull()
        ->and($manager->detector('none'))->toBeNull();
});

test('an unknown driver name is rejected', function () {
    expect(fn () => app(SmsManager::class)->driver('carrier-pigeon'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported SMS driver [carrier-pigeon]');
});

// -- What the composer shows under routing ---------------------------------

test('smart traffic routed away from iprog is reachable', function () {
    router();

    // Nothing to warn about: Semaphore carries these without a sender name.
    expect(config('sms.drivers.routing.map.'.IprogSmsSender::NETWORK_SMART))->toBe('semaphore')
        ->and(config('sms.drivers.routing.fallback'))->toBe('semaphore');
});

test('pointing every route back at iprog is still a valid configuration', function () {
    // What the shop sets once IPROG approves a custom sender name.
    $sender = router([
        'sms.drivers.routing.map' => [
            IprogSmsSender::NETWORK_GLOBE => 'iprogsms',
            IprogSmsSender::NETWORK_DITO => 'iprogsms',
            IprogSmsSender::NETWORK_SMART => 'iprogsms',
        ],
        'sms.drivers.routing.fallback' => 'iprogsms',
        'sms.drivers.iprogsms.sender_name_approved' => true,
    ]);

    fakeNetwork(IprogSmsSender::NETWORK_SMART, isSmartTnt: true);

    $sender->send('09181234567', 'Your repair is ready.');

    Http::assertSent(fn ($r) => str_contains($r->url(), 'iprogtech.com/api/v1/sms_messages'));
    Http::assertNotSent(fn ($r) => str_contains($r->url(), 'semaphore.co'));
});

test('with no fallback and no mapped driver the message is refused clearly', function () {
    $sender = router([
        'sms.drivers.routing.map' => [],
        'sms.drivers.routing.fallback' => '',
    ]);

    fakeNetwork(IprogSmsSender::NETWORK_SMART, isSmartTnt: true);

    expect(fn () => $sender->send('09181234567', 'Hello.'))
        ->toThrow(RuntimeException::class, 'SMS_ROUTE_FALLBACK');
});
