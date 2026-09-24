<?php

namespace App\Enums;

enum JobOrderStatus: string
{
    case PENDING = 'pending';
    case ASSIGNED = 'assigned';
    case AWAITING_APPROVAL = 'awaiting_approval';
    case APPROVED = 'approved';
    case AWAITING_PARTS = 'awaiting_parts';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';
    case COMPLETED = 'completed';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ASSIGNED => 'Assigned',
            self::AWAITING_APPROVAL => 'Awaiting Approval',
            self::APPROVED => 'Approved',
            self::AWAITING_PARTS => 'Awaiting Parts',
            self::IN_PROGRESS => 'In Progress',
            self::DONE => 'Done',
            self::COMPLETED => 'Completed',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::ASSIGNED => 'blue',
            self::AWAITING_APPROVAL => 'yellow',
            self::APPROVED => 'emerald',
            self::AWAITING_PARTS => 'orange',
            self::IN_PROGRESS => 'indigo',
            self::DONE => 'cyan',
            self::COMPLETED => 'green',
            self::DELIVERED => 'teal',
            self::CANCELLED => 'red',
        };
    }

    /**
     * Badge classes for this status, as complete literal strings.
     *
     * Every class is spelled out rather than interpolated from color(),
     * because Tailwind v4 finds classes by scanning source text: a
     * "text-{$color}-700" built at runtime is never in the stylesheet, so the
     * badge renders unstyled. The repetition below is what keeps these in the
     * build.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::PENDING => 'text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30',
            self::ASSIGNED => 'text-blue-700 bg-blue-100 dark:text-blue-300 dark:bg-blue-900/30',
            self::AWAITING_APPROVAL => 'text-yellow-700 bg-yellow-100 dark:text-yellow-300 dark:bg-yellow-900/30',
            self::APPROVED => 'text-emerald-700 bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-900/30',
            self::AWAITING_PARTS => 'text-orange-700 bg-orange-100 dark:text-orange-300 dark:bg-orange-900/30',
            self::IN_PROGRESS => 'text-indigo-700 bg-indigo-100 dark:text-indigo-300 dark:bg-indigo-900/30',
            self::DONE => 'text-cyan-700 bg-cyan-100 dark:text-cyan-300 dark:bg-cyan-900/30',
            self::COMPLETED => 'text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/30',
            self::DELIVERED => 'text-teal-700 bg-teal-100 dark:text-teal-300 dark:bg-teal-900/30',
            self::CANCELLED => 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/30',
        };
    }

    /**
     * Statuses this one may move to.
     *
     * The shop's real sequence is pending → assigned → awaiting_approval →
     * approved → in_progress → done → completed → delivered, but it is not a
     * straight line: a job order can be quoted before or after a technician is
     * assigned, work can be reopened from done, and almost anything can be
     * cancelled. The map below allows those, and refuses the jumps that would
     * skip a step that records money or stock — approving without a quote,
     * delivering without completing.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::ASSIGNED, self::AWAITING_APPROVAL, self::APPROVED, self::AWAITING_PARTS, self::IN_PROGRESS, self::CANCELLED],
            self::ASSIGNED => [self::AWAITING_APPROVAL, self::APPROVED, self::AWAITING_PARTS, self::IN_PROGRESS, self::CANCELLED],
            self::AWAITING_APPROVAL => [self::APPROVED, self::ASSIGNED, self::AWAITING_PARTS, self::IN_PROGRESS, self::CANCELLED],
            self::APPROVED => [self::IN_PROGRESS, self::AWAITING_APPROVAL, self::AWAITING_PARTS, self::CANCELLED],
            // Back to approved the moment the delivery clears the backorder.
            self::AWAITING_PARTS => [self::APPROVED, self::IN_PROGRESS, self::AWAITING_APPROVAL, self::CANCELLED],
            // Back to awaiting_approval when the technician opens the device
            // and finds it needs work the customer has not agreed to.
            self::IN_PROGRESS => [self::DONE, self::AWAITING_APPROVAL, self::CANCELLED],
            // Reopening a finished repair is ordinary — the customer collects
            // it, finds the fault still there, and it goes back to the bench.
            self::DONE => [self::COMPLETED, self::IN_PROGRESS, self::CANCELLED],
            self::COMPLETED => [self::DELIVERED, self::IN_PROGRESS, self::CANCELLED],
            // Terminal: the device has left the shop, or the job never ran.
            self::DELIVERED => [],
            self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        // Re-saving a record without changing its status is not a transition.
        if ($this === $status) {
            return true;
        }

        return in_array($status, $this->allowedTransitions(), strict: true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
