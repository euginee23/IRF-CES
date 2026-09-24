<?php

namespace App\Services\JobOrders;

use App\Enums\JobOrderStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Exceptions\UnpaidBalance;
use App\Models\JobOrder;
use App\Models\JobOrderEvent;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\Auth;

/**
 * The guarded front door for moving a job order through its lifecycle.
 *
 * Status was previously changed by bare update() calls scattered across the
 * Livewire screens, the portal controller and the model itself, with each
 * caller checking (or not checking) its own preconditions. The technician
 * dashboard did no checking at all: it took a status string straight off the
 * wire, so a crafted Livewire payload could jump a repair to "delivered"
 * without it ever being worked on or paid for.
 *
 * Callers that route through here get the transition validated and the
 * lifecycle timestamps set. History is recorded separately by the observer,
 * which also catches the callers that do not.
 *
 * @see \App\Observers\JobOrderObserver
 * @see \App\Enums\JobOrderStatus::allowedTransitions()
 */
class JobOrderWorkflow
{
    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * @throws InvalidStatusTransition when the move is not a legal one.
     */
    public function transitionTo(
        JobOrder $jobOrder,
        JobOrderStatus $to,
        ?string $note = null,
        bool $allowUnpaidDelivery = false,
    ): JobOrder {
        $from = $jobOrder->status;

        if (! $from->canTransitionTo($to)) {
            throw InvalidStatusTransition::between($from, $to);
        }

        // Approving a repair commits the parts to it. When the shop is short,
        // commit() backorders the line and says so, and the repair waits for
        // a delivery rather than being refused at the counter.
        if ($to === JobOrderStatus::APPROVED && $from !== JobOrderStatus::AWAITING_PARTS) {
            $to = $this->inventory->statusAfterCommit($this->inventory->commit($jobOrder));
        }

        if ($to === JobOrderStatus::DONE) {
            $this->inventory->consume($jobOrder);
            $this->freezeFinalCost($jobOrder);
        }

        // The device does not leave until it is paid for. An administrator
        // can still let it go — sometimes they have to — but that is a
        // decision someone makes, not something that happens silently.
        if ($to === JobOrderStatus::DELIVERED && $jobOrder->fresh()->balance() > 0 && ! $allowUnpaidDelivery) {
            throw new UnpaidBalance($jobOrder->fresh()->balance());
        }

        if ($to === JobOrderStatus::CANCELLED) {
            $this->inventory->release($jobOrder);
        }

        $jobOrder->update($this->attributesFor($jobOrder, $to));

        if ($note !== null && trim($note) !== '') {
            $this->note($jobOrder, $note);
        }

        return $jobOrder;
    }

    /**
     * The customer approved the quote on the portal.
     *
     * Approval is where parts are committed, so it goes through the same
     * transition as everything else rather than setting the status directly —
     * otherwise an approved repair would reserve nothing and the shop could
     * promise the same screen to two customers.
     */
    public function approveByCustomer(JobOrder $jobOrder): JobOrder
    {
        $jobOrder->forceFill([
            'approved_by_customer_at' => now(),
            'approval_method' => 'customer',
        ])->save();

        return $this->transitionTo($jobOrder, JobOrderStatus::APPROVED);
    }

    /**
     * A delivery cleared this repair's last backorder.
     *
     * The parts were already reserved by InventoryService::fulfilBackorders,
     * so this only moves the repair back into the queue and says so — passing
     * through APPROVED here must not re-commit stock, which the transition
     * guards against by checking where it came from.
     */
    public function partsArrived(JobOrder $jobOrder): JobOrder
    {
        if ($jobOrder->status !== JobOrderStatus::AWAITING_PARTS) {
            return $jobOrder;
        }

        JobOrderEvent::create([
            'job_order_id' => $jobOrder->id,
            'type' => JobOrderEvent::TYPE_PARTS_ARRIVED,
            'description' => 'The replacement parts have arrived. Your repair is back in the queue.',
            'is_customer_visible' => true,
            'user_id' => Auth::id(),
        ]);

        return $this->transitionTo($jobOrder, JobOrderStatus::APPROVED);
    }

    /** A staff member approved the quote on the customer's behalf. */
    public function approveManually(JobOrder $jobOrder): JobOrder
    {
        $jobOrder->forceFill(['approval_method' => 'manual'])->save();

        return $this->transitionTo($jobOrder, JobOrderStatus::APPROVED);
    }

    /**
     * Fix the invoice total at the moment the work is finished.
     *
     * Until now final_cost was declared and read but never written, so every
     * "revenue" figure was really a sum of estimates. Freezing it here — from
     * the lines' own price snapshots — means a later catalogue change cannot
     * rewrite what a past quarter earned. Set once: a repair reopened and
     * finished again keeps the total the customer was invoiced.
     */
    private function freezeFinalCost(JobOrder $jobOrder): void
    {
        if ($jobOrder->final_cost !== null && (float) $jobOrder->final_cost > 0) {
            return;
        }

        $jobOrder->load(['parts', 'services']);
        $jobOrder->forceFill(['final_cost' => $jobOrder->lineTotal()])->save();
    }

    /**
     * Attach a free-text note to the repair's history without changing its
     * status.
     */
    public function note(JobOrder $jobOrder, string $note, bool $customerVisible = false): JobOrderEvent
    {
        return JobOrderEvent::create([
            'job_order_id' => $jobOrder->id,
            'type' => JobOrderEvent::TYPE_NOTE,
            'description' => trim($note),
            'is_customer_visible' => $customerVisible,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * The status plus whichever lifecycle timestamp this move stamps.
     *
     * Each timestamp is only ever set once. A repair that is reopened from
     * done and completed again keeps the date it was first finished, because
     * that is the date on the customer's receipt.
     *
     * @return array<string, mixed>
     */
    private function attributesFor(JobOrder $jobOrder, JobOrderStatus $to): array
    {
        $attributes = ['status' => $to];

        if ($to === JobOrderStatus::COMPLETED && $jobOrder->completed_at === null) {
            $attributes['completed_at'] = now();
        }

        if ($to === JobOrderStatus::DELIVERED && $jobOrder->delivered_at === null) {
            $attributes['delivered_at'] = now();
        }

        return $attributes;
    }
}
