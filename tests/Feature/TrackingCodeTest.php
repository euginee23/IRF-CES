<?php

use App\Enums\JobOrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Jobs\SendCustomerMessage;
use App\Models\CustomerMessage;
use App\Models\JobOrder;
use App\Models\User;
use App\Services\JobOrders\JobOrderWorkflow;
use App\Services\Payments\PaymentService;
use App\Services\TrackingCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Volt\Volt;

function trackUser(Role $role = Role::COUNTER_STAFF): User
{
    return User::factory()->create([
        'role' => $role,
        'email_verified_at' => now(),
    ]);
}

function trackJobOrder(array $attributes = []): JobOrder
{
    return JobOrder::create(array_merge([
        'customer_name' => 'Juan Dela Cruz',
        'customer_phone' => '09171234567',
        'device_brand' => 'Samsung',
        'device_model' => 'Galaxy A54',
        'issue_description' => 'Cracked screen',
        'status' => JobOrderStatus::PENDING,
        'received_by' => trackUser()->id,
    ], $attributes));
}

// -- The code itself -------------------------------------------------------

test('a job order is given a tracking code on creation', function () {
    $jobOrder = trackJobOrder();

    expect($jobOrder->tracking_code)->toHaveLength(TrackingCode::LENGTH)
        ->and(TrackingCode::looksValid($jobOrder->tracking_code))->toBeTrue();
});

test('the alphabet leaves out the characters people mistake for each other', function () {
    // The code is read aloud and written down, which is where 0/O and 1/I/L
    // go wrong.
    foreach (['0', 'O', '1', 'I', 'L'] as $ambiguous) {
        expect(TrackingCode::ALPHABET)->not->toContain($ambiguous);
    }
});

test('tracking codes are unique across many job orders', function () {
    $codes = collect(range(1, 40))->map(fn () => trackJobOrder()->tracking_code);

    expect($codes->unique())->toHaveCount(40);
});

test('an explicit tracking code is respected', function () {
    expect(trackJobOrder(['tracking_code' => 'R7K4M2'])->tracking_code)->toBe('R7K4M2');
});

test('what the customer types is tidied up before it is looked up', function () {
    expect(TrackingCode::normalise(' r7k4-m2 '))->toBe('R7K4M2')
        ->and(TrackingCode::normalise('r7k4 m2'))->toBe('R7K4M2');
});

test('something that is not a code is not mistaken for one', function () {
    expect(TrackingCode::looksValid('R7K4M2'))->toBeTrue()
        ->and(TrackingCode::looksValid('R7K4M'))->toBeFalse()
        // 0 and O are not in the alphabet, so a code containing them is not ours.
        ->and(TrackingCode::looksValid('R0K4M2'))->toBeFalse()
        ->and(TrackingCode::looksValid('JO-20260924-0001'))->toBeFalse();
});

// -- Looking it up ---------------------------------------------------------

test('a customer can track a repair with the short code', function () {
    $jobOrder = trackJobOrder(['tracking_code' => 'R7K4M2']);

    $this->post(route('customer.portal.lookup'), ['job_order_number' => 'R7K4M2'])
        ->assertRedirect(route('customer.portal.view', ['token' => $jobOrder->portal_token]));
});

test('the code is accepted however it was typed', function () {
    $jobOrder = trackJobOrder(['tracking_code' => 'R7K4M2']);

    $this->post(route('customer.portal.lookup'), ['job_order_number' => ' r7k4m2 '])
        ->assertRedirect(route('customer.portal.view', ['token' => $jobOrder->portal_token]));
});

test('the full job order number still works', function () {
    $jobOrder = trackJobOrder();

    $this->post(route('customer.portal.lookup'), ['job_order_number' => $jobOrder->job_order_number])
        ->assertRedirect(route('customer.portal.view', ['token' => $jobOrder->portal_token]));
});

test('an unknown code is refused without saying which repairs exist', function () {
    trackJobOrder(['tracking_code' => 'R7K4M2']);

    $this->post(route('customer.portal.lookup'), ['job_order_number' => 'ZZZZZZ'])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('the tracking page shows the code back to the customer', function () {
    $jobOrder = trackJobOrder(['tracking_code' => 'R7K4M2']);

    $this->get(route('customer.portal.view', ['token' => $jobOrder->portal_token]))
        ->assertOk()
        ->assertSee('R7K4M2');
});

// -- Telling the customer --------------------------------------------------

test('booking a repair in queues the tracking code to the customer', function () {
    Queue::fake();

    $this->actingAs(trackUser(Role::COUNTER_STAFF));

    Volt::test('job-orders.create')
        ->set('customer_name', 'Juan Dela Cruz')
        ->set('customer_phone', '09171234567')
        ->set('device_brand', 'Samsung')
        ->set('device_model', 'Galaxy A54')
        ->set('issue_description', 'Cracked screen')
        ->set('services', [['type' => 'Screen Repair', 'diagnosis' => '']])
        ->call('save')
        ->assertHasNoErrors();

    // Queued, not sent inline: the provider takes up to 15 seconds and the
    // counter has someone waiting.
    Queue::assertPushed(SendCustomerMessage::class);
});

test('a customer with no phone or email is not messaged', function () {
    Queue::fake();

    $this->actingAs(trackUser());
    $jobOrder = trackJobOrder(['customer_phone' => '', 'customer_email' => null]);

    expect(SendCustomerMessage::bestChannelFor($jobOrder))->toBeNull();
});

test('the intake message carries the code and the tracking link', function () {
    $jobOrder = trackJobOrder(['tracking_code' => 'R7K4M2']);

    $rendered = \App\Services\Messaging\MessageTemplates::renderSystem(
        'repair_booked',
        CustomerMessage::CHANNEL_SMS,
        $jobOrder,
    );

    expect($rendered['body'])->toContain('R7K4M2')
        ->and($rendered['body'])->toContain($jobOrder->portal_token)
        ->and($rendered['body'])->not->toContain(':tracking_code');
});

test('the intake text fits in one credit', function () {
    $jobOrder = trackJobOrder();

    $body = \App\Services\Messaging\MessageTemplates::renderSystem(
        'repair_booked',
        CustomerMessage::CHANNEL_SMS,
        $jobOrder,
    )['body'];

    // The portal link alone is ~99 characters, so this is worth pinning.
    expect(mb_strlen($body) + mb_strlen('IRF-CES Repair System '))->toBeLessThanOrEqual(320);
});

test('the intake message is not offered in the staff composer', function () {
    // "Your repair has been booked in" is never the right thing to send by
    // hand, so it lives outside the picker's list.
    expect(array_keys(\App\Services\Messaging\MessageTemplates::options()))
        ->not->toContain('repair_booked');
});

// -- The thread ------------------------------------------------------------

test('the customer timeline merges progress with messages sent', function () {
    $this->actingAs(trackUser());
    $jobOrder = trackJobOrder(['status' => JobOrderStatus::IN_PROGRESS, 'estimated_cost' => 1000]);

    app(JobOrderWorkflow::class)->transitionTo($jobOrder, JobOrderStatus::DONE);
    app(PaymentService::class)->take($jobOrder->fresh(), 1000, PaymentMethod::CASH);

    $jobOrder->customerMessages()->create([
        'channel' => CustomerMessage::CHANNEL_SMS,
        'recipient' => '+639171234567',
        'body' => 'Your device is ready.',
    ]);

    $timeline = $jobOrder->fresh()->customerTimeline();

    expect($timeline->pluck('kind'))->toContain('sms')
        ->and($timeline->pluck('kind'))->toContain('payment_received')
        // Oldest first: a customer reads their repair forwards.
        ->and($timeline->first()['at']->lessThanOrEqualTo($timeline->last()['at']))->toBeTrue();
});

test('internal events stay off the customer timeline', function () {
    $this->actingAs(trackUser());
    $technician = trackUser(Role::TECHNICIAN);
    $jobOrder = trackJobOrder();

    $jobOrder->update(['assigned_to' => $technician->id]);
    app(JobOrderWorkflow::class)->note($jobOrder, 'Customer haggled over the price.');

    $titles = $jobOrder->fresh()->customerTimeline()->pluck('title');

    expect($titles)->not->toContain('Customer haggled over the price.')
        ->and($titles->filter(fn ($t) => str_contains($t, $technician->name)))->toBeEmpty();
});

test('the tracking page shows the progress thread', function () {
    $this->actingAs(trackUser());
    $jobOrder = trackJobOrder(['status' => JobOrderStatus::IN_PROGRESS]);

    app(JobOrderWorkflow::class)->transitionTo($jobOrder, JobOrderStatus::DONE);

    auth()->logout();

    $this->get(route('customer.portal.view', ['token' => $jobOrder->portal_token]))
        ->assertOk()
        ->assertSee('Repair Progress')
        ->assertSee('Repair booked in.');
});

// -- The backfill ----------------------------------------------------------

test('the migration gives existing job orders a code', function () {
    $jobOrder = trackJobOrder();

    DB::table('job_orders')->where('id', $jobOrder->id)->update(['tracking_code' => null]);

    $migration = require database_path('migrations/2026_09_24_000011_add_tracking_code_to_job_orders_table.php');
    $migration->up();

    expect($jobOrder->fresh()->tracking_code)->not->toBeNull();
});
