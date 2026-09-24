<?php

use App\Contracts\Contactable;
use App\Enums\JobOrderStatus;
use App\Enums\Role;
use App\Models\CustomerMessage;
use App\Models\JobOrder;
use App\Models\User;
use App\Services\Messaging\CustomerMessenger;
use App\Services\Messaging\MessageTemplates;
use Illuminate\Support\Facades\Mail;

function messagingUser(Role $role): User
{
    return User::factory()->create([
        'role' => $role,
        'email_verified_at' => now(),
    ]);
}

function messagingJobOrder(array $attributes = []): JobOrder
{
    return JobOrder::create(array_merge([
        'customer_name' => 'Juan Dela Cruz',
        'customer_email' => 'juan@example.com',
        'customer_phone' => '09171234567',
        'device_brand' => 'Samsung',
        'device_model' => 'Galaxy A54',
        'issue_description' => 'Cracked screen',
        'estimated_cost' => 2500.00,
        'status' => JobOrderStatus::PENDING,
        'received_by' => messagingUser(Role::COUNTER_STAFF)->id,
    ], $attributes));
}

test('a job order is contactable', function () {
    $jobOrder = messagingJobOrder();

    expect($jobOrder)->toBeInstanceOf(Contactable::class)
        ->and($jobOrder->contactName())->toBe('Juan Dela Cruz')
        ->and($jobOrder->contactEmail())->toBe('juan@example.com')
        ->and($jobOrder->contactPhone())->toBe('09171234567');
});

test('a walk-in job order with no email is still contactable by phone', function () {
    $jobOrder = messagingJobOrder(['customer_email' => null]);

    expect($jobOrder->contactEmail())->toBeNull()
        ->and($jobOrder->contactPhone())->toBe('09171234567');
});

test('placeholders describe the device, the price and the portal link', function () {
    $jobOrder = messagingJobOrder();

    $placeholders = $jobOrder->messagePlaceholders();

    expect($placeholders['name'])->toBe('Juan Dela Cruz')
        ->and($placeholders['device'])->toBe('Samsung Galaxy A54')
        ->and($placeholders['price'])->toBe('PHP 2,500.00')
        ->and($placeholders['job_number'])->toBe($jobOrder->job_order_number)
        ->and($placeholders['portal_url'])->toContain($jobOrder->portal_token);
});

test('the final cost takes over from the estimate once it is set', function () {
    $jobOrder = messagingJobOrder(['final_cost' => 3100.00]);

    expect($jobOrder->messagePlaceholders()['price'])->toBe('PHP 3,100.00');
});

test('a zero final cost does not masquerade as a real price', function () {
    // decimal:2 casts return strings, and "0.00" is truthy — the reason this
    // is compared numerically rather than with a bare ?: like the dashboard's.
    $jobOrder = messagingJobOrder(['final_cost' => 0]);

    expect($jobOrder->messagePlaceholders()['price'])->toBe('PHP 2,500.00');
});

test('an unpriced job order says so rather than showing PHP 0.00', function () {
    $jobOrder = messagingJobOrder(['estimated_cost' => null]);

    expect($jobOrder->messagePlaceholders()['price'])->toBe('to be confirmed');
});

test('every message template renders for a job order', function () {
    $jobOrder = messagingJobOrder();

    foreach (array_keys(MessageTemplates::options()) as $key) {
        foreach ([CustomerMessage::CHANNEL_EMAIL, CustomerMessage::CHANNEL_SMS] as $channel) {
            $rendered = MessageTemplates::render($key, $channel, $jobOrder);

            expect($rendered['body'])->not->toContain(':name')
                ->and($rendered['body'])->not->toContain(':device');
        }
    }
});

test('a message sent about a job order is recorded against it', function () {
    Mail::fake();

    $this->actingAs($staff = messagingUser(Role::COUNTER_STAFF));
    $jobOrder = messagingJobOrder();

    app(CustomerMessenger::class)->send(
        record: $jobOrder,
        channel: CustomerMessage::CHANNEL_EMAIL,
        body: 'Your device is ready.',
        subject: 'Ready for pickup',
    );

    $message = $jobOrder->customerMessages()->firstOrFail();

    expect($message->recipient)->toBe('juan@example.com')
        ->and($message->sent_by)->toBe($staff->id)
        ->and($message->messageable_id)->toBe($jobOrder->id)
        ->and($message->messageable_type)->toBe(JobOrder::class);
});

test('an administrator can reach the quote requests screen', function () {
    $this->actingAs(messagingUser(Role::ADMINISTRATOR));

    $this->get(route('admin.quote-requests'))->assertOk();
});

test('a technician cannot reach the admin quote requests screen', function () {
    $this->actingAs(messagingUser(Role::TECHNICIAN));

    $this->get(route('admin.quote-requests'))->assertForbidden();
});
