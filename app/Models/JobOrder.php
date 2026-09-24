<?php

namespace App\Models;

use App\Contracts\Contactable;
use App\Enums\JobOrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Concerns\HasCustomerMessages;
use App\Services\TrackingCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOrder extends Model implements Contactable
{
    use HasCustomerMessages;

    protected $fillable = [
        'job_order_number',
        'tracking_code',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'device_brand',
        'device_model',
        'serial_number',
        'issue_description',
        'estimated_cost',
        'expected_completion_date',
        'status',
        'received_by',
        'assigned_to',
        'work_performed',
        'final_cost',
        'completed_at',
        'delivered_at',
        'portal_token',
        'approved_by_customer_at',
        'approval_method',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'final_cost' => 'decimal:2',
        'expected_completion_date' => 'date',
        'completed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'approved_by_customer_at' => 'datetime',
        'status' => JobOrderStatus::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($jobOrder) {
            if (empty($jobOrder->job_order_number)) {
                $base = 'JO-'.date('Ymd').'-';

                // Start from count of today's records + 1, but verify uniqueness
                $seq = self::whereDate('created_at', today())->count() + 1;

                // Try increasing the sequence until we find an unused job order number
                do {
                    $candidate = $base.str_pad($seq, 4, '0', STR_PAD_LEFT);
                    $exists = self::where('job_order_number', $candidate)->exists();
                    if ($exists) {
                        $seq++;
                        // small sleep to reduce tight-loop race in extreme concurrency
                        usleep(1000);
                    }
                } while ($exists && $seq < 9999);

                // Fallback: if somehow we exhausted attempts, append a unique suffix
                if ($seq >= 9999) {
                    $candidate = $base.uniqid();
                }

                $jobOrder->job_order_number = $candidate;
            }

            // Generate unique portal token
            if (empty($jobOrder->portal_token)) {
                $jobOrder->portal_token = bin2hex(random_bytes(32));
            }

            // The short code the customer is given.
            if (empty($jobOrder->tracking_code)) {
                $jobOrder->tracking_code = TrackingCode::generate();
            }
        });
    }

    /**
     * Limit a query to the job orders this user is allowed to see.
     *
     * Administrators and counter staff run the shop floor and see everything;
     * a technician sees only the repairs on their own bench. That rule was
     * previously spelled out as a bare where() in each dashboard, which is why
     * the administrator had no way to look at a technician's work at all.
     * Keeping it here means widening the administrator's view cannot
     * accidentally widen the technician's.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isTechnician()) {
            return $query->where('assigned_to', $user->id);
        }

        return $query;
    }

    /**
     * Get the staff member who received this job order.
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Replacement parts on this repair.
     */
    public function parts(): HasMany
    {
        return $this->hasMany(JobOrderPart::class);
    }

    /**
     * Work booked on this repair.
     */
    public function services(): HasMany
    {
        return $this->hasMany(JobOrderService::class);
    }

    /**
     * What the parts come to.
     *
     * Reads the snapshot on each line, so a later catalogue price change
     * cannot rewrite what this customer was quoted.
     */
    public function partsTotal(): float
    {
        return round($this->parts->sum(fn (JobOrderPart $part) => $part->lineTotal()), 2);
    }

    /**
     * What the labour comes to.
     */
    public function laborTotal(): float
    {
        return round((float) $this->services->sum('labor_price'), 2);
    }

    /**
     * The full price of the work: parts plus labour.
     */
    public function lineTotal(): float
    {
        return round($this->partsTotal() + $this->laborTotal(), 2);
    }

    /**
     * What the parts cost the shop, for margin reporting.
     */
    public function partsCost(): float
    {
        return round($this->parts->sum(fn (JobOrderPart $part) => $part->lineCost()), 2);
    }

    /**
     * Payments taken against this repair, newest first.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    /**
     * What the customer is being billed.
     *
     * final_cost once the work is finished, the estimate before that. The
     * estimate can still move while the repair is open; the final cost is
     * frozen, so a later price change cannot rewrite a past invoice.
     *
     * Compared numerically because a decimal:2 cast returns a string, and
     * "0.00" is truthy.
     */
    public function amountDue(): float
    {
        if ($this->final_cost !== null && (float) $this->final_cost > 0) {
            return (float) $this->final_cost;
        }

        return (float) ($this->estimated_cost ?? 0);
    }

    /** What has actually been collected, ignoring voided payments. */
    public function amountPaid(): float
    {
        return round((float) $this->payments()->counted()->sum('amount'), 2);
    }

    /** What is still owed. Never negative — an overpayment is not a debt. */
    public function balance(): float
    {
        return round(max(0, $this->amountDue() - $this->amountPaid()), 2);
    }

    public function paymentStatus(): PaymentStatus
    {
        $paid = $this->amountPaid();
        $due = $this->amountDue();

        return match (true) {
            $paid <= 0 => PaymentStatus::UNPAID,
            $paid > $due => PaymentStatus::OVERPAID,
            $paid >= $due => PaymentStatus::PAID,
            default => PaymentStatus::PARTIAL,
        };
    }

    public function isFullyPaid(): bool
    {
        return $this->paymentStatus()->isSettled();
    }

    /**
     * This repair's history, newest first.
     *
     * Tie-broken by id like customerMessages(), because several events can
     * land in the same second — a status change and the note explaining it —
     * and an arbitrary order there reads as the history being wrong.
     */
    public function events(): HasMany
    {
        return $this->hasMany(JobOrderEvent::class)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * What the customer sees on the tracking page.
     *
     * Visible events merged with the messages actually sent, oldest first.
     * The two are merged at read time rather than copied into one table,
     * because customer_messages is already the record of what the customer
     * was told and a second copy would be a second version of the truth.
     *
     * @return \Illuminate\Support\Collection<int, array{at: \Illuminate\Support\Carbon, kind: string, title: string, body: string|null}>
     */
    public function customerTimeline()
    {
        $events = $this->events()
            ->where('is_customer_visible', true)
            ->get()
            ->map(fn (JobOrderEvent $event) => [
                'at' => $event->created_at,
                'kind' => $event->type,
                'title' => $event->description,
                'body' => null,
            ]);

        $messages = $this->customerMessages()
            ->get()
            ->map(fn (CustomerMessage $message) => [
                'at' => $message->created_at,
                'kind' => $message->isSms() ? 'sms' : 'email',
                'title' => $message->isSms()
                    ? 'We sent you a text message'
                    : ($message->subject ?: 'We emailed you'),
                'body' => $message->body,
            ]);

        return $events->concat($messages)->sortBy('at')->values();
    }

    /**
     * Get the technician assigned to this job order.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Check if job order is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === JobOrderStatus::COMPLETED;
    }

    /**
     * Check if job order is delivered.
     */
    public function isDelivered(): bool
    {
        return $this->status === JobOrderStatus::DELIVERED;
    }

    /**
     * Check if job order can be edited.
     */
    public function canBeEdited(): bool
    {
        return ! in_array($this->status, [JobOrderStatus::COMPLETED, JobOrderStatus::DELIVERED, JobOrderStatus::CANCELLED]);
    }

    /**
     * Get the customer portal URL.
     */
    public function getPortalUrlAttribute(): string
    {
        return route('customer.portal.view', ['token' => $this->portal_token]);
    }

    /**
     * Check if approved by customer.
     */
    public function isApprovedByCustomer(): bool
    {
        return $this->approval_method === 'customer';
    }

    // -- Contactable -------------------------------------------------------

    public function contactName(): string
    {
        return (string) $this->customer_name;
    }

    public function contactEmail(): ?string
    {
        // Optional at intake: a walk-in customer often leaves only a number.
        return $this->customer_email;
    }

    public function contactPhone(): ?string
    {
        // Required at intake, so unlike a quote request this is always set.
        return $this->customer_phone;
    }

    /**
     * @return array<string, string>
     */
    public function messagePlaceholders(): array
    {
        // The final cost once the repair is priced, the estimate before that.
        // Compared numerically because a decimal:2 cast returns a string, and
        // "0.00" is truthy.
        $price = $this->final_cost !== null && (float) $this->final_cost > 0
            ? (float) $this->final_cost
            : ($this->estimated_cost !== null ? (float) $this->estimated_cost : null);

        return [
            'name' => $this->contactName(),
            'device' => trim("{$this->device_brand} {$this->device_model}"),
            'price' => $price !== null
                ? 'PHP '.number_format($price, 2)
                : 'to be confirmed',
            // A job order is given its portal token on create, so unlike a
            // quote request this link always resolves.
            'portal_url' => $this->portal_token ? $this->portal_url : url('/'),
            'shop' => (string) config('app.name'),
            'job_number' => (string) $this->job_order_number,
            'tracking_code' => (string) $this->tracking_code,
        ];
    }
}
