<?php

use App\Mail\CustomerMessageMail;
use App\Models\CustomerMessage;
use App\Models\RepairQuoteRequest;
use App\Models\User;
use App\Services\Messaging\CustomerMessenger;
use App\Services\Messaging\MessageTemplates;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

function quoteRequest(array $overrides = []): RepairQuoteRequest
{
    return RepairQuoteRequest::create(array_merge([
        'name' => 'Juan Dela Cruz',
        'email' => 'juan@example.com',
        'phone' => '09171234567',
        'manufacturer' => 'Samsung',
        'model' => 'Galaxy A52',
        'issue_description' => 'Cracked screen after a drop.',
        'status' => 'pending',
    ], $overrides));
}

function messenger(): CustomerMessenger
{
    return app(CustomerMessenger::class);
}

beforeEach(function () {
    // The log driver is the default, but pin it so a stray .env cannot make
    // the suite spend real SMS credits.
    config(['sms.default' => 'none']);
});

test('an email is sent to the customer and recorded', function () {
    Mail::fake();

    $request = quoteRequest();

    $message = messenger()->send(
        record: $request,
        channel: CustomerMessage::CHANNEL_EMAIL,
        body: 'Your quote is ready.',
        subject: 'Your repair quote',
        template: 'quote_ready',
    );

    Mail::assertSent(CustomerMessageMail::class, fn (CustomerMessageMail $mail) => $mail->hasTo('juan@example.com')
        && $mail->messageSubject === 'Your repair quote'
        && $mail->messageBody === 'Your quote is ready.');

    expect($message->channel)->toBe('email')
        ->and($message->recipient)->toBe('juan@example.com')
        ->and($message->subject)->toBe('Your repair quote')
        ->and($message->template)->toBe('quote_ready')
        ->and($request->customerMessages()->count())->toBe(1);
});

test('an SMS is sent to the normalised number and recorded without a subject', function () {
    config(['sms.default' => 'iprogsms', 'sms.drivers.iprogsms' => [
        'driver' => 'iprogsms', 'token' => 'tok_123', 'log_channel' => null,
    ]]);
    app()->forgetInstance(App\Services\Sms\SmsSender::class);

    Http::fake(['*' => Http::response(['status' => 200, 'message_id' => 'iSms-XHYBk'])]);

    $request = quoteRequest(['phone' => '0917-123-4567']);

    $message = messenger()->send(
        record: $request,
        channel: CustomerMessage::CHANNEL_SMS,
        body: 'Your quote is ready.',
    );

    // Recorded as sent, not as typed: the raw column holds mixed formats.
    expect($message->recipient)->toBe('+639171234567')
        ->and($message->subject)->toBeNull()
        ->and($message->channel)->toBe('sms');

    Http::assertSent(fn ($r) => str_contains($r->url(), 'sms_messages')
        && $r->data()['phone_number'] === '+639171234567'
        && $r->data()['message'] === 'Your quote is ready.');
});

test('the sender is recorded against the message', function () {
    Mail::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $message = messenger()->send(
        record: quoteRequest(),
        channel: CustomerMessage::CHANNEL_EMAIL,
        body: 'Hello.',
        subject: 'Hello',
    );

    expect($message->sent_by)->toBe($user->id)
        ->and($message->sentBy->name)->toBe($user->name);
});

test('a customer with no phone cannot be sent an SMS', function () {
    $request = quoteRequest(['phone' => null]);

    expect(fn () => messenger()->send($request, CustomerMessage::CHANNEL_SMS, 'Hello.'))
        ->toThrow(RuntimeException::class, 'no phone number on file');

    expect(CustomerMessage::count())->toBe(0);
});

test('a customer with no email cannot be sent an email', function () {
    Mail::fake();

    $request = quoteRequest(['email' => '']);

    expect(fn () => messenger()->send($request, CustomerMessage::CHANNEL_EMAIL, 'Hello.', 'Hi'))
        ->toThrow(RuntimeException::class, 'no email address on file');

    Mail::assertNothingSent();
});

test('an unusable phone number is refused before anything is sent', function () {
    $request = quoteRequest(['phone' => 'n/a']);

    expect(fn () => messenger()->send($request, CustomerMessage::CHANNEL_SMS, 'Hello.'))
        ->toThrow(RuntimeException::class, 'not a usable phone number');

    expect(CustomerMessage::count())->toBe(0);
});

test('an email without a subject is refused', function () {
    Mail::fake();

    expect(fn () => messenger()->send(quoteRequest(), CustomerMessage::CHANNEL_EMAIL, 'Hello.', '  '))
        ->toThrow(RuntimeException::class, 'needs a subject');

    Mail::assertNothingSent();
});

test('an empty message is refused', function () {
    expect(fn () => messenger()->send(quoteRequest(), CustomerMessage::CHANNEL_EMAIL, "   \n ", 'Hi'))
        ->toThrow(RuntimeException::class, 'empty message');
});

test('an unknown channel is refused', function () {
    expect(fn () => messenger()->send(quoteRequest(), 'carrier-pigeon', 'Hello.'))
        ->toThrow(RuntimeException::class, 'Unsupported message channel [carrier-pigeon]');
});

test('nothing is recorded when the send itself fails', function () {
    config(['sms.default' => 'iprogsms', 'sms.drivers.iprogsms' => [
        'driver' => 'iprogsms', 'token' => 'tok_123', 'log_channel' => null,
    ]]);
    app()->forgetInstance(App\Services\Sms\SmsSender::class);

    // IPROG reports failure inside a 200 response.
    Http::fake(['*' => Http::response(['status' => 500, 'message' => 'Invalid Token'])]);

    expect(fn () => messenger()->send(quoteRequest(), CustomerMessage::CHANNEL_SMS, 'Hello.'))
        ->toThrow(RuntimeException::class, 'Invalid Token');

    // The history must read as "what the customer was told", not "what we tried".
    expect(CustomerMessage::count())->toBe(0);
});

test('templates fill in the record details', function () {
    $request = quoteRequest(['quoted_price' => 3500, 'portal_token' => str_repeat('a', 64)]);

    $rendered = MessageTemplates::render('quote_ready', 'sms', $request);

    expect($rendered['body'])
        ->toContain('Juan Dela Cruz')
        ->toContain('Samsung Galaxy A52')
        ->toContain('PHP 3,500.00')
        ->toContain($request->portal_url)
        ->and($rendered['subject'])->toBe('');
});

test('the email version of a template carries a subject and longer body', function () {
    $request = quoteRequest(['quoted_price' => 3500, 'portal_token' => str_repeat('a', 64)]);

    $sms = MessageTemplates::render('quote_ready', 'sms', $request);
    $email = MessageTemplates::render('quote_ready', 'email', $request);

    expect($email['subject'])->toBe('Your repair quote for Samsung Galaxy A52')
        ->and(mb_strlen($email['body']))->toBeGreaterThan(mb_strlen($sms['body']));
});

test('an unquoted request renders a placeholder price and no broken portal link', function () {
    $request = quoteRequest(); // no quoted_price, no portal_token

    $placeholders = $request->messagePlaceholders();

    expect($placeholders['price'])->toBe('to be confirmed')
        ->and($placeholders['portal_url'])->toBe(url('/'));
});

test('every configured template renders on both channels', function () {
    $request = quoteRequest(['quoted_price' => 1200, 'portal_token' => str_repeat('b', 64)]);

    foreach (array_keys(MessageTemplates::options()) as $key) {
        foreach (['sms', 'email'] as $channel) {
            $rendered = MessageTemplates::render($key, $channel, $request);

            expect($rendered['body'])->not->toBe('')
                // An unsubstituted ":word" means a typo in the template.
                ->and($rendered['body'])->not->toMatch('/:[a-z_]+/');
        }
    }
});

test('no SMS template costs more than two credits', function () {
    $request = quoteRequest(['quoted_price' => 1200, 'portal_token' => str_repeat('b', 64)]);

    foreach (array_keys(MessageTemplates::options()) as $key) {
        $body = MessageTemplates::render($key, 'sms', $request)['body'];

        // Two segments, not one: the portal link is ~99 characters on its own
        // (a 64-character token), leaving about 60 for wording. The templates
        // that carry a link therefore cost 2 credits, and the composer shows
        // that cost live before sending. A shorter link would buy this back.
        expect(mb_strlen($body))->toBeLessThanOrEqual(320, "Template [{$key}] would cost more than two SMS credits.");
    }
});

test('what each template actually costs to send, sender name included', function () {
    $request = quoteRequest(['quoted_price' => 1200, 'portal_token' => str_repeat('b', 64)]);

    // IPROG prepends the account's sender name and a space to the body, and
    // those characters are billed. "IRF-CES Repair System " is 22.
    $overhead = mb_strlen('IRF-CES Repair System ');

    $segments = [];

    foreach (array_keys(MessageTemplates::options()) as $key) {
        $length = mb_strlen(MessageTemplates::render($key, 'sms', $request)['body']) + $overhead;
        $segments[$key] = (int) ceil($length / 160);
    }

    // Pinned so that a template tipping into another credit — whether from
    // wording, a longer sender name or a longer link — shows up deliberately.
    expect($segments)->toBe([
        'quote_ready' => 2,          // 205 typed + 22 = 227; the portal link alone is 99
        'need_more_info' => 1,       // 126 + 22 = 148
        'awaiting_approval' => 2,    // 209 + 22 = 231; also carries the link
        'ready_for_pickup' => 1,     // 99 + 22 = 121
        'parts_delayed' => 1,        // 132 + 22 = 154
    ]);
});

test('an unknown template is rejected', function () {
    expect(fn () => MessageTemplates::render('nope', 'sms', quoteRequest()))
        ->toThrow(InvalidArgumentException::class, 'Unknown message template [nope].');
});

test('history is newest first', function () {
    Mail::fake();

    $request = quoteRequest();

    messenger()->send($request, CustomerMessage::CHANNEL_EMAIL, 'First.', 'One');
    messenger()->send($request, CustomerMessage::CHANNEL_EMAIL, 'Second.', 'Two');

    expect($request->customerMessages()->pluck('body')->all())->toBe(['Second.', 'First.']);
});
