<?php

use App\Services\Sms\IprogSmsSender;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\NullSmsSender;
use App\Services\Sms\PhoneNumber;
use App\Services\Sms\SmsSender;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Re-resolve the singleton after changing config. */
function smsSender(): SmsSender
{
    app()->forgetInstance(SmsSender::class);

    return app(SmsSender::class);
}

/**
 * An IPROG sender with logging off, so tests that only care about the HTTP
 * exchange need no Log expectations.
 */
function iprogSender(string $token = 'tok_123', ?int $provider = null): IprogSmsSender
{
    return new IprogSmsSender(token: $token, provider: $provider, logChannel: null);
}

/** IPROG's documented success body. */
function iprogAccepted(string $messageId = 'iSms-XHYBk'): array
{
    return [
        'status' => 200,
        'message' => 'Your SMS message has been successfully added to the queue and will be processed shortly.',
        'message_id' => $messageId,
    ];
}

test('mixed phone formats all normalise to the same E.164 number', function (string $input) {
    expect(PhoneNumber::toE164($input))->toBe('+639171234567');
})->with([
    '09171234567',
    '+63 917 123 4567',
    '639171234567',
    '(0917) 123-4567',
    '0917-123-4567',
    '+639171234567',
]);

test('a number with no usable digits normalises to null', function (?string $input) {
    expect(PhoneNumber::toE164($input))->toBeNull();
})->with([null, '', '   ', 'not-a-number', '0', '000']);

test('the country code is configurable', function () {
    expect(PhoneNumber::toE164('07911123456', '44'))->toBe('+447911123456');
});

test('the log driver records the message on the sms channel', function () {
    Log::shouldReceive('channel')->once()->with('sms')->andReturnSelf();
    Log::shouldReceive('info')->once()->withArgs(function (string $message, array $context) {
        return $message === 'SMS sent'
            && $context['driver'] === 'log'
            && $context['to'] === '+639171234567'
            && $context['to_raw'] === '09171234567'
            && $context['from'] === 'IRF-CES'
            && $context['message'] === 'Quote ready.'
            && $context['length'] === 12
            && $context['segments'] === 1;
    });

    (new LogSmsSender(channel: 'sms', from: 'IRF-CES'))->send('09171234567', 'Quote ready.');
});

test('a message longer than one segment is reported as two', function () {
    Log::shouldReceive('channel')->once()->andReturnSelf();
    Log::shouldReceive('info')->once()->withArgs(
        fn (string $message, array $context) => $context['length'] === 161 && $context['segments'] === 2
    );

    (new LogSmsSender)->send('09171234567', str_repeat('a', 161));
});

test('the log driver refuses an unusable number', function () {
    Log::shouldReceive('channel')->never();

    expect(fn () => (new LogSmsSender)->send('not-a-number', 'Quote ready.'))
        ->toThrow(RuntimeException::class);
});

test('the null driver writes nothing', function () {
    Log::shouldReceive('channel')->never();
    Log::shouldReceive('info')->never();

    $sender = new NullSmsSender;
    $sender->send('09171234567', 'Quote ready.');

    // Mockery verifies the never() expectations on teardown; this pins the
    // contract so the null driver stays swappable with the log driver.
    expect($sender)->toBeInstanceOf(SmsSender::class);
});

test('the configured driver is what gets resolved', function () {
    config(['sms.default' => 'log']);
    expect(smsSender())->toBeInstanceOf(LogSmsSender::class);

    config(['sms.default' => 'none']);
    expect(smsSender())->toBeInstanceOf(NullSmsSender::class);
});

test('an unknown driver fails with a message naming it', function () {
    config(['sms.default' => 'bogus']);

    expect(fn () => smsSender())
        ->toThrow(InvalidArgumentException::class, 'Unsupported SMS driver [bogus].');
});

test('the legacy null driver name still resolves to the discarding sender', function () {
    config(['sms.default' => 'null']);

    expect(smsSender())->toBeInstanceOf(NullSmsSender::class);
});

test('a blank driver explains the SMS_DRIVER=null pitfall', function () {
    // .env reads a bare `null` as an empty value, so this is what actually
    // reaches the container when someone writes SMS_DRIVER=null.
    config(['sms.default' => null]);

    expect(fn () => smsSender())
        ->toThrow(InvalidArgumentException::class, 'No SMS driver configured.');
});

test('the test:sms command sends through the configured driver', function () {
    config(['sms.default' => 'none']);
    smsSender();

    $this->artisan('test:sms', ['to' => '09171234567', 'message' => 'Quote ready.'])
        ->expectsOutputToContain('+639171234567')
        ->assertExitCode(0);
});

test('the test:sms command rejects an unusable number', function () {
    $this->artisan('test:sms', ['to' => 'not-a-number'])
        ->assertExitCode(1);
});

test('the iprogsms driver posts the documented payload as a form', function () {
    Http::fake(['sms.iprogtech.com/api/v1/sms_messages' => Http::response(iprogAccepted())]);

    iprogSender()->send('0917-123-4567', 'Quote ready.');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://sms.iprogtech.com/api/v1/sms_messages'
        && $request->method() === 'POST'
        && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
        && $request->data() === [
            'api_token' => 'tok_123',
            // Mixed input formats arrive at IPROG as one "+639XXXXXXXXX".
            'phone_number' => '+639171234567',
            'message' => 'Quote ready.',
        ]);

    Http::assertSentCount(1);
});

test('the upstream telco route is sent only when it is configured', function () {
    Http::fake(['*' => Http::response(iprogAccepted())]);

    iprogSender()->send('09171234567', 'Quote ready.');
    Http::assertSent(fn (Request $request) => ! array_key_exists('sms_provider', $request->data()));

    iprogSender(provider: 1)->send('09171234567', 'Quote ready.');
    Http::assertSent(fn (Request $request) => ($request->data()['sms_provider'] ?? null) === 1);
});

test('a real send is logged with the IPROG message id but not the message body', function () {
    Http::fake(['*' => Http::response(iprogAccepted('iSms-rQZvhq'))]);

    Log::shouldReceive('channel')->once()->with('sms')->andReturnSelf();
    Log::shouldReceive('info')->once()->withArgs(fn (string $event, array $context) => $event === 'SMS sent'
        && $context['driver'] === 'iprogsms'
        && $context['to'] === '+639171234567'
        && $context['to_raw'] === '09171234567'
        && $context['message_id'] === 'iSms-rQZvhq'
        && $context['length'] === 12
        // Customer message text stays out of the logs once sends are real.
        && ! array_key_exists('message', $context));

    (new IprogSmsSender(token: 'tok_123'))->send('09171234567', 'Quote ready.');
});

test('an error inside a 200 response is treated as a failure, not a send', function () {
    // IPROG answers HTTP 200 even when the send fails.
    Http::fake(['*' => Http::response(['status' => 500, 'message' => 'Invalid Token'], 200)]);

    Log::shouldReceive('channel')->never();

    expect(fn () => (new IprogSmsSender(token: 'wrong'))->send('09171234567', 'Quote ready.'))
        ->toThrow(RuntimeException::class, 'Invalid Token');
});

test('a validation failure reports the reasons IPROG gave', function () {
    Http::fake(['*' => Http::response([
        'status' => 'error',
        'message' => 'Message failed validation',
        'data' => ['passed' => false, 'errors' => ['Message looks suspicious (phishing detected).']],
    ])]);

    expect(fn () => iprogSender()->send('09171234567', 'Click here to claim.'))
        ->toThrow(RuntimeException::class, 'Message failed validation');

    expect(fn () => iprogSender()->send('09171234567', 'Click here to claim.'))
        ->toThrow(RuntimeException::class, 'phishing detected');
});

test('a transport-level error reports the HTTP status', function () {
    Http::fake(['*' => Http::response('Gateway Timeout', 504)]);

    expect(fn () => iprogSender()->send('09171234567', 'Quote ready.'))
        ->toThrow(RuntimeException::class, 'HTTP 504');
});

test('a failed send is never retried, because a send is billed and not idempotent', function () {
    Http::fake(['*' => Http::response('Gateway Timeout', 504)]);

    try {
        iprogSender()->send('09171234567', 'Quote ready.');
    } catch (RuntimeException) {
        // asserted above
    }

    Http::assertSentCount(1);
});

test('an unreachable provider surfaces as a send failure', function () {
    Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

    expect(fn () => iprogSender()->send('09171234567', 'Quote ready.'))
        ->toThrow(RuntimeException::class, 'Could not reach IPROG SMS');
});

test('an unusable number fails before any request is made', function () {
    Http::fake();

    expect(fn () => iprogSender()->send('not-a-number', 'Quote ready.'))
        ->toThrow(RuntimeException::class, 'not a usable phone number');

    Http::assertNothingSent();
});

test('a missing token fails before any request is made and names the env key', function () {
    Http::fake();

    expect(fn () => iprogSender(token: '')->send('09171234567', 'Quote ready.'))
        ->toThrow(RuntimeException::class, 'IPROGSMS_TOKEN is not set');

    Http::assertNothingSent();
});

test('the container builds the iprogsms driver with the configured token', function () {
    config([
        'sms.default' => 'iprogsms',
        'sms.drivers.iprogsms' => [
            'driver' => 'iprogsms',
            'token' => 'tok_from_config',
            'base_url' => IprogSmsSender::BASE_URL,
            'timeout' => 15,
            'log_channel' => null,
        ],
    ]);

    Http::fake(['*' => Http::response(iprogAccepted())]);

    $sender = smsSender();
    expect($sender)->toBeInstanceOf(IprogSmsSender::class);

    $sender->send('09171234567', 'Quote ready.');

    Http::assertSent(fn (Request $request) => $request->data()['api_token'] === 'tok_from_config');
});

test('the credit balance is read from the account endpoint', function () {
    Http::fake(['*' => Http::response([
        'status' => 'success',
        'message' => 'Account found.',
        'data' => ['load_balance' => 10],
    ])]);

    expect(iprogSender()->credits())->toBe(10.0);

    Http::assertSent(fn (Request $request) => $request->method() === 'GET'
        && str_starts_with($request->url(), 'https://sms.iprogtech.com/api/v1/account/sms_credits')
        && $request['api_token'] === 'tok_123');
});

test('an unreadable balance is reported as unknown rather than failing', function (callable $fake) {
    Http::fake($fake);

    expect(iprogSender()->credits())->toBeNull();
})->with([
    'invalid token' => [fn () => fn () => Http::response(['status' => 500, 'message' => 'Invalid Token'])],
    'provider down' => [fn () => fn () => throw new ConnectionException('cURL error 28')],
]);

test('a blank token skips the balance request entirely', function () {
    Http::fake();

    expect(iprogSender(token: '')->credits())->toBeNull();

    Http::assertNothingSent();
});
