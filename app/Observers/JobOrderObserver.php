<?php

namespace App\Observers;

use App\Enums\JobOrderStatus;
use App\Models\JobOrder;
use App\Models\JobOrderEvent;
use Illuminate\Support\Facades\Auth;

/**
 * Records job order history.
 *
 * This is deliberately an observer rather than a call inside the workflow
 * service. Status is changed from a dozen scattered places — the technician
 * dashboard, the job orders index, the customer portal, the model's own
 * approve helpers — and more will be added. An observer catches all of them,
 * including ones written by someone who has never heard of the workflow
 * service. JobOrderWorkflow decides whether a transition is *allowed*; this
 * decides that it is *remembered*.
 *
 * @see \App\Services\JobOrders\JobOrderWorkflow
 */
class JobOrderObserver
{
    public function created(JobOrder $jobOrder): void
    {
        JobOrderEvent::create([
            'job_order_id' => $jobOrder->id,
            'type' => JobOrderEvent::TYPE_CREATED,
            'to_status' => $jobOrder->status,
            'description' => 'Repair booked in.',
            'meta' => [
                'device' => trim("{$jobOrder->device_brand} {$jobOrder->device_model}"),
            ],
            'is_customer_visible' => true,
            'user_id' => Auth::id(),
        ]);
    }

    public function updated(JobOrder $jobOrder): void
    {
        if ($jobOrder->wasChanged('status')) {
            $this->recordStatusChange($jobOrder);
        }

        if ($jobOrder->wasChanged('assigned_to')) {
            $this->recordAssignment($jobOrder);
        }
    }

    private function recordStatusChange(JobOrder $jobOrder): void
    {
        /** @var JobOrderStatus|null $from */
        $from = $jobOrder->getOriginal('status');
        $to = $jobOrder->status;

        JobOrderEvent::create([
            'job_order_id' => $jobOrder->id,
            'type' => JobOrderEvent::TYPE_STATUS_CHANGED,
            'from_status' => $from,
            'to_status' => $to,
            'description' => self::describe($to),
            'is_customer_visible' => self::isVisibleToCustomer($to),
            // Null on a customer portal approval, which is the point of
            // recording it — "the customer approved this" is not the same
            // event as "a staff member approved it for them".
            'user_id' => Auth::id(),
        ]);
    }

    private function recordAssignment(JobOrder $jobOrder): void
    {
        $technician = $jobOrder->assignedTo()->first();

        JobOrderEvent::create([
            'job_order_id' => $jobOrder->id,
            'type' => JobOrderEvent::TYPE_ASSIGNED,
            'description' => $technician
                ? "Assigned to {$technician->name}."
                : 'Technician unassigned.',
            'meta' => ['technician_id' => $jobOrder->assigned_to],
            // Which technician holds the job is shop business, not the
            // customer's.
            'is_customer_visible' => false,
            'user_id' => Auth::id(),
        ]);
    }

    /** Customer-facing wording for arriving at a status. */
    private static function describe(JobOrderStatus $status): string
    {
        return match ($status) {
            JobOrderStatus::PENDING => 'Waiting to be picked up by a technician.',
            JobOrderStatus::ASSIGNED => 'A technician has been assigned.',
            JobOrderStatus::AWAITING_APPROVAL => 'Your repair quote is ready for approval.',
            JobOrderStatus::APPROVED => 'Quote approved — the repair is scheduled.',
            JobOrderStatus::AWAITING_PARTS => 'We are waiting on a replacement part for your device.',
            JobOrderStatus::IN_PROGRESS => 'Your device is being repaired.',
            JobOrderStatus::DONE => 'The repair work is finished and being checked.',
            JobOrderStatus::COMPLETED => 'Your device is ready for pickup.',
            JobOrderStatus::DELIVERED => 'Your device has been collected. Thank you!',
            JobOrderStatus::CANCELLED => 'This repair was cancelled.',
        };
    }

    /**
     * Intake and technician assignment are shop bookkeeping; everything from
     * the quote onwards is the customer's business.
     */
    private static function isVisibleToCustomer(JobOrderStatus $status): bool
    {
        return ! in_array($status, [
            JobOrderStatus::PENDING,
            JobOrderStatus::ASSIGNED,
        ], strict: true);
    }
}
