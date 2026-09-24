<?php

use App\Mail\CustomerMessageMail;
use App\Models\CustomerMessage;
use App\Models\RepairQuoteRequest;
use App\Models\User;
use App\Services\Sms\SmsSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

/** Switch to the real provider so the network gate is in play. */
function useIprogDriver(bool $senderNameApproved = false): void
{
    config([
        'sms.default' => 'iprogsms',
        'sms.drivers.iprogsms' => [
            'driver' => 'iprogsms',
            'token' => 'tok_123',
            'log_channel' => null,
            'sender_name_approved' => $senderNameApproved,
        ],
    ]);

    app()->forgetInstance(SmsSender::class);
}

/** Both screens mix in the same trait, so both are exercised. */
dataset('quoteRequestScreens', [
    'counter' => ['counter.quote-requests'],
    'admin' => ['admin.quote-requests'],
]);

function uiQuoteRequest(array $overrides = []): RepairQuoteRequest
{
    return RepairQuoteRequest::create(array_merge([
        'name' => 'Juan Dela Cruz',
        'email' => 'juan@example.com',
        'phone' => '09171234567',
        'manufacturer' => 'Samsung',
        'model' => 'Galaxy A52',
        'issue_description' => 'Cracked screen after a drop.',
        'status' => 'pending',
        'quoted_price' => 3500,
        'portal_token' => str_repeat('c', 64),
    ], $overrides));
}

beforeEach(function () {
    config(['sms.default' => 'none']);
    $this->actingAs(User::factory()->create());
    Mail::fake();
});

test('the composer opens on email when the customer has one', function (string $screen) {
    $request = uiQuoteRequest();

    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->assertSet('showContactModal', true)
        ->assertSet('contactChannel', 'email');
})->with('quoteRequestScreens');

test('the composer opens on SMS when there is no email to use', function (string $screen) {
    $request = uiQuoteRequest(['email' => '']);

    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->assertSet('contactChannel', 'sms');
})->with('quoteRequestScreens');

test('picking a template fills the subject and body', function (string $screen) {
    $request = uiQuoteRequest();

    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->set('contactTemplate', 'quote_ready')
        ->assertSet('contactSubject', 'Your repair quote for Samsung Galaxy A52')
        ->assertSee('Juan Dela Cruz');
})->with('quoteRequestScreens');

test('switching to SMS swaps in the short wording instead of the email prose', function (string $screen) {
    $request = uiQuoteRequest();

    $component = Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->set('contactTemplate', 'quote_ready');

    $emailBody = $component->get('contactBody');

    $component->set('contactChannel', 'sms');
    $smsBody = $component->get('contactBody');

    // Leaving email prose in an SMS box would quietly cost several credits.
    expect(mb_strlen($smsBody))->toBeLessThan(mb_strlen($emailBody))
        ->and($smsBody)->toContain($request->portal_url);
})->with('quoteRequestScreens');

test('sending an email from the composer records it and closes the panel', function (string $screen) {
    $request = uiQuoteRequest();

    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->set('contactTemplate', 'quote_ready')
        ->call('sendCustomerMessage')
        ->assertSet('showContactModal', false)
        ->assertDispatched('success');

    Mail::assertSent(CustomerMessageMail::class);

    $message = CustomerMessage::sole();

    expect($message->channel)->toBe('email')
        ->and($message->recipient)->toBe('juan@example.com')
        ->and($message->template)->toBe('quote_ready')
        ->and($message->messageable_id)->toBe($request->id)
        ->and($message->messageable_type)->toBe(RepairQuoteRequest::class);
})->with('quoteRequestScreens');

test('an empty message is rejected before anything is sent', function (string $screen) {
    $request = uiQuoteRequest();

    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->set('contactBody', '')
        ->call('sendCustomerMessage')
        ->assertHasErrors('contactBody');

    Mail::assertNothingSent();
    expect(CustomerMessage::count())->toBe(0);
})->with('quoteRequestScreens');

test('an email with no subject is rejected', function (string $screen) {
    $request = uiQuoteRequest();

    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->set('contactBody', 'Hello there.')
        ->set('contactSubject', '')
        ->call('sendCustomerMessage')
        ->assertHasErrors('contactSubject');

    Mail::assertNothingSent();
})->with('quoteRequestScreens');

test('a failed send reports the reason and records nothing', function (string $screen) {
    // No phone on file, so the SMS channel cannot be used.
    $request = uiQuoteRequest(['phone' => null]);

    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->set('contactChannel', 'sms')
        ->set('contactBody', 'Hello there.')
        ->call('sendCustomerMessage')
        ->assertDispatched('error')
        ->assertSet('showContactModal', true);

    expect(CustomerMessage::count())->toBe(0);
})->with('quoteRequestScreens');

test('the composer shows which contact details are on file', function (string $screen) {
    $request = uiQuoteRequest(['phone' => null]);

    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->assertSee('juan@example.com')
        ->assertSee('No phone number on file');
})->with('quoteRequestScreens');

test('previously sent messages are listed in the composer', function (string $screen) {
    $request = uiQuoteRequest();

    $request->customerMessages()->create([
        'channel' => 'sms',
        'recipient' => '+639171234567',
        'body' => 'An earlier message about your repair.',
    ]);

    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->assertSee('Previously sent')
        ->assertSee('An earlier message about your repair.');
})->with('quoteRequestScreens');

test('closing the record also closes the composer', function (string $screen) {
    $request = uiQuoteRequest();

    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->assertSet('showContactModal', true)
        ->call('closeModal')
        ->assertSet('showContactModal', false);
})->with('quoteRequestScreens');

test('a Smart, TNT or Sun number is shown as unsupported and cannot be sent to', function (string $screen) {
    useIprogDriver();
    Http::fake(['*phone_numbers/detect' => Http::response([
        'status' => 'success',
        'data' => ['is_smart_tnt' => true, 'network' => 'Smart/TNT'],
    ])]);

    $request = uiQuoteRequest(['phone' => '09181234567']);

    $component = Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->set('contactChannel', 'sms');

    $component
        ->assertSet('contactNetworkUnreachable', true)
        ->assertSet('contactNetwork', 'Smart/TNT')
        ->assertSee('Smart/TNT is not supported')
        ->assertSee('Unsupported')
        ->assertSee('Use Email instead');

    // Blocked server-side too, not just in the markup.
    $component->set('contactBody', 'Hello there.')
        ->call('sendCustomerMessage')
        ->assertDispatched('error');

    expect(CustomerMessage::count())->toBe(0);
    Http::assertNotSent(fn ($r) => str_contains($r->url(), 'sms_messages'));
})->with('quoteRequestScreens');

test('a Globe number is not flagged and sends', function (string $screen) {
    useIprogDriver();
    Http::fake([
        '*phone_numbers/detect' => Http::response([
            'status' => 'success',
            'data' => ['is_smart_tnt' => false, 'network' => 'Globe/TM'],
        ]),
        '*sms_messages' => Http::response(['status' => 200, 'message_id' => 'iSms-XHYBk']),
    ]);

    $request = uiQuoteRequest(['phone' => '09171234567']);

    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->set('contactChannel', 'sms')
        ->assertSet('contactNetworkUnreachable', false)
        ->assertSee('Globe/TM')
        ->set('contactBody', 'Hello there.')
        ->call('sendCustomerMessage')
        ->assertDispatched('success');

    expect(CustomerMessage::sole()->channel)->toBe('sms');
})->with('quoteRequestScreens');

test('an approved sender name stops Smart numbers being flagged', function (string $screen) {
    useIprogDriver(senderNameApproved: true);
    Http::fake(['*' => Http::response(['status' => 200, 'message_id' => 'iSms-XHYBk'])]);

    $request = uiQuoteRequest(['phone' => '09181234567']);

    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->set('contactChannel', 'sms')
        ->assertSet('contactNetworkUnreachable', false)
        ->assertDontSee('is not supported');
})->with('quoteRequestScreens');

test('a prefix IPROG cannot identify is treated as ordinary, with no warning', function (string $screen) {
    useIprogDriver();
    Http::fake([
        '*phone_numbers/detect' => Http::response([
            'status' => 'success',
            'data' => ['is_smart_tnt' => false, 'network' => 'Unknown Network'],
        ]),
        '*sms_messages' => Http::response(['status' => 200, 'message_id' => 'iSms-XHYBk']),
    ]);

    // 0952 is a Globe number that IPROG's lookup does not list, yet delivers
    // normally — so an unlisted prefix must not raise anything.
    $request = uiQuoteRequest(['phone' => '09524529089']);

    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->set('contactChannel', 'sms')
        ->assertSet('contactNetworkUnreachable', false)
        ->assertSet('contactNetwork', null)
        ->assertDontSee('does not recognise')
        ->assertDontSee('not supported')
        ->assertDontSee('Unknown Network')
        ->set('contactBody', 'Hello there.')
        ->call('sendCustomerMessage')
        ->assertDispatched('success');

    expect(CustomerMessage::sole()->channel)->toBe('sms');
})->with('quoteRequestScreens');

test('the sender name IPROG prepends is counted against the segment budget', function (string $screen) {
    useIprogDriver();
    config(['sms.drivers.iprogsms.sender_name' => 'IRF-CES Repair System']); // 21 chars + a space
    Http::fake(['*phone_numbers/detect' => Http::response([
        'status' => 'success',
        'data' => ['is_smart_tnt' => false, 'network' => 'Globe/TM'],
    ])]);

    $request = uiQuoteRequest();

    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->set('contactChannel', 'sms')
        ->set('contactBody', str_repeat('a', 100))
        // 100 typed + 22 prepended. Counting only what was typed would show
        // "100 / 160" for a message the provider bills at 122.
        ->assertSee('122 / 160')
        ->assertSee('Keep it under 138 characters');
})->with('quoteRequestScreens');

test('a message that fits when typed but not once prepended is shown as two credits', function (string $screen) {
    useIprogDriver();
    config(['sms.drivers.iprogsms.sender_name' => 'IRF-CES Repair System']);
    Http::fake(['*phone_numbers/detect' => Http::response([
        'status' => 'success',
        'data' => ['is_smart_tnt' => false, 'network' => 'Globe/TM'],
    ])]);

    $request = uiQuoteRequest();

    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->set('contactChannel', 'sms')
        ->set('contactBody', str_repeat('a', 150)) // under 160 typed, 172 billed
        ->assertSee('172 / 160')
        ->assertSee('2 credits');
})->with('quoteRequestScreens');

test('no overhead is counted when the provider prepends nothing', function (string $screen) {
    useIprogDriver();
    config(['sms.drivers.iprogsms.sender_name' => '']);
    Http::fake(['*phone_numbers/detect' => Http::response([
        'status' => 'success',
        'data' => ['is_smart_tnt' => false, 'network' => 'Globe/TM'],
    ])]);

    $request = uiQuoteRequest();

    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->set('contactChannel', 'sms')
        ->set('contactBody', str_repeat('a', 100))
        ->assertSee('100 / 160');
})->with('quoteRequestScreens');

test('the SMS driver in use is stated in the composer', function (string $screen) {
    $request = uiQuoteRequest();

    // On "none" or "log" nothing is delivered, and staff need to know that
    // before they tell a customer the text is on its way.
    Volt::test($screen)
        ->call('viewRequest', $request->id)
        ->call('openContactModal')
        ->set('contactChannel', 'sms')
        ->assertSee('nothing will actually be delivered');
})->with('quoteRequestScreens');
