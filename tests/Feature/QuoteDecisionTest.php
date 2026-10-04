<?php

use App\Enums\JobOrderStatus;
use App\Enums\Role;
use App\Exceptions\InvalidStatusTransition;
use App\Mail\QuoteApprovalMail;
use App\Mail\QuoteRequestMail;
use App\Models\JobOrder;
use App\Models\RepairQuoteRequest;
use App\Models\User;
use App\Services\JobOrders\JobOrderWorkflow;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

function decisionStaff(Role $role = Role::COUNTER_STAFF): User
{
    return User::factory()->create(['role' => $role, 'email_verified_at' => now()]);
}

function quotedJobOrder(JobOrderStatus $status = JobOrderStatus::AWAITING_APPROVAL, array $attributes = []): JobOrder
{
    return JobOrder::create(array_merge([
        'customer_name' => 'Maria Santos',
        'customer_email' => 'maria@example.com',
        'customer_phone' => '09171234567',
        'device_brand' => 'Apple',
        'device_model' => 'iPhone 15',
        'issue_description' => 'Cracked screen',
        'expected_completion_date' => today()->addDays(3),
        'status' => $status,
        'received_by' => decisionStaff()->id,
    ], $attributes));
}

// -- Email links -------------------------------------------------------------

test('opening an email link only asks, it never answers', function () {
    $jobOrder = quotedJobOrder();

    $this->get(route('customer.portal.approve.confirm', $jobOrder->portal_token))
        ->assertOk()
        ->assertSee('Approve your repair quote?');

    $this->get(route('customer.portal.decline.confirm', $jobOrder->portal_token))
        ->assertOk()
        ->assertSee('Disapprove your repair quote?');

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::AWAITING_APPROVAL);
});

test('an answered quote sends the link back to the tracking page', function () {
    $jobOrder = quotedJobOrder(JobOrderStatus::APPROVED);

    $this->get(route('customer.portal.decline.confirm', $jobOrder->portal_token))
        ->assertRedirect(route('customer.portal.view', $jobOrder->portal_token));
});

test('the quote email carries both an approve and a disapprove link', function () {
    $jobOrder = quotedJobOrder();

    $html = (new QuoteApprovalMail($jobOrder, 0, 0, 0))->render();

    expect($html)
        ->toContain(route('customer.portal.approve.confirm', $jobOrder->portal_token))
        ->toContain(route('customer.portal.decline.confirm', $jobOrder->portal_token))
        ->toContain('Disapprove');
});

// -- Customer answers ----------------------------------------------------------

test('a customer can approve from the portal', function () {
    $jobOrder = quotedJobOrder();

    $this->post(route('customer.portal.approve', $jobOrder->portal_token))
        ->assertRedirect(route('customer.portal.view', $jobOrder->portal_token));

    expect($jobOrder->fresh())
        ->status->toBe(JobOrderStatus::APPROVED)
        ->approval_method->toBe('customer');
});

test('a customer can disapprove with a reason', function () {
    $jobOrder = quotedJobOrder();

    $this->post(route('customer.portal.decline', $jobOrder->portal_token), ['reason' => 'Too expensive'])
        ->assertRedirect(route('customer.portal.view', $jobOrder->portal_token));

    $fresh = $jobOrder->fresh();

    expect($fresh->status)->toBe(JobOrderStatus::DECLINED)
        ->and($fresh->decline_reason)->toBe('Too expensive')
        ->and($fresh->declined_at)->not->toBeNull()
        ->and($fresh->approval_method)->toBe('customer')
        ->and($fresh->events()->where('description', 'like', '%Too expensive%')->exists())->toBeTrue();

    $this->get(route('customer.portal.view', $jobOrder->portal_token))
        ->assertOk()
        ->assertSee('Quote Disapproved');
});

test('a quote that is not awaiting approval cannot be disapproved', function () {
    $jobOrder = quotedJobOrder(JobOrderStatus::IN_PROGRESS);

    $this->post(route('customer.portal.decline', $jobOrder->portal_token))
        ->assertSessionHas('error');

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::IN_PROGRESS);
});

// -- Transitions -----------------------------------------------------------------

test('a disapproved quote can be re-quoted or cancelled, but not worked on', function () {
    $workflow = app(JobOrderWorkflow::class);

    $requoted = $workflow->declineManually(quotedJobOrder());
    $workflow->requote($requoted);
    expect($requoted->fresh())
        ->status->toBe(JobOrderStatus::AWAITING_APPROVAL)
        ->declined_at->toBeNull();

    $cancelled = $workflow->declineManually(quotedJobOrder());
    $workflow->transitionTo($cancelled, JobOrderStatus::CANCELLED);
    expect($cancelled->fresh()->status)->toBe(JobOrderStatus::CANCELLED);

    $declined = $workflow->declineManually(quotedJobOrder());
    expect(fn () => $workflow->transitionTo($declined, JobOrderStatus::IN_PROGRESS))
        ->toThrow(InvalidStatusTransition::class);
});

test('a refused disapproval writes nothing', function () {
    $jobOrder = quotedJobOrder(JobOrderStatus::PENDING);

    expect(fn () => app(JobOrderWorkflow::class)->declineManually($jobOrder, 'No'))
        ->toThrow(InvalidStatusTransition::class);

    expect($jobOrder->fresh()->declined_at)->toBeNull();
});

// -- Staff ---------------------------------------------------------------------

test('staff can mark a quote disapproved and re-quote it', function () {
    Mail::fake();
    $this->actingAs(decisionStaff());
    $jobOrder = quotedJobOrder();

    Volt::test('job-orders.index')
        ->call('declineQuote', $jobOrder->id, 'Customer called')
        ->assertDispatched('success');

    expect($jobOrder->fresh())
        ->status->toBe(JobOrderStatus::DECLINED)
        ->approval_method->toBe('manual');

    Volt::test('job-orders.index')->call('requote', $jobOrder->id);

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::AWAITING_APPROVAL);
    Mail::assertSent(QuoteApprovalMail::class);
});

// -- Repair quote requests ---------------------------------------------------------

test('an administrator sending a quote request emails it with a working link', function () {
    Mail::fake();
    $this->actingAs(decisionStaff(Role::ADMINISTRATOR));

    $request = RepairQuoteRequest::create([
        'name' => 'Pedro Reyes',
        'email' => 'pedro@example.com',
        'phone' => '09181234567',
        'manufacturer' => 'Samsung',
        'model' => 'Galaxy S24',
        'issue_description' => 'Battery drains fast',
        'status' => 'pending',
    ]);

    Volt::test('admin.quote-requests')
        ->call('viewRequest', $request->id)
        ->set('quotedPrice', '1800')
        ->call('createQuote')
        ->assertHasNoErrors();

    $request->refresh();

    expect($request->status)->toBe('quoted')
        ->and($request->portal_token)->not->toBeNull()
        ->and((float) $request->quoted_price)->toBe(1800.0);

    Mail::assertSent(QuoteRequestMail::class, function (QuoteRequestMail $mail) use ($request) {
        return str_contains($mail->render(), $request->portal_url.'?action=decline');
    });
});

test('a quote request status must be a known one', function () {
    $this->actingAs(decisionStaff());

    $request = RepairQuoteRequest::create([
        'name' => 'Pedro Reyes',
        'email' => 'pedro@example.com',
        'phone' => '09181234567',
        'manufacturer' => 'Samsung',
        'model' => 'Galaxy S24',
        'issue_description' => 'Battery drains fast',
        'status' => 'pending',
    ]);

    Volt::test('counter.quote-requests')->call('updateStatus', $request->id, 'hacked');
    expect($request->fresh()->status)->toBe('pending');

    Volt::test('counter.quote-requests')->call('updateStatus', $request->id, 'declined');
    expect($request->fresh()->status)->toBe('declined');
});
