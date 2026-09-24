<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One payment taken against a job order.
 *
 * A repair is usually paid in two goes — a deposit at intake and the balance
 * on collection — so each is its own row and the job order's standing is
 * derived from them.
 *
 * @see \App\Services\Payments\PaymentService
 */
class Payment extends Model
{
    protected $fillable = [
        'job_order_id',
        'amount',
        'method',
        'reference_no',
        'receipt_number',
        'paid_at',
        'received_by',
        'note',
        'voided_at',
        'voided_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'method' => PaymentMethod::class,
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    /**
     * Payments that still count as money taken.
     *
     * Every total in the application goes through this, so a voided payment
     * cannot quietly inflate a day's income.
     *
     * @param  Builder<self>  $query
     */
    public function scopeCounted(Builder $query): Builder
    {
        return $query->whereNull('voided_at');
    }
}
