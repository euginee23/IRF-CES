<?php

namespace App\Models;

use App\Enums\JobOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One thing that happened to a job order.
 *
 * Written by App\Observers\JobOrderObserver (which catches every status
 * change, wherever it was made from) and by the services that know the
 * detail an observer cannot see — why a part was backordered, how much was
 * paid.
 *
 * @see \App\Observers\JobOrderObserver
 */
class JobOrderEvent extends Model
{
    public const TYPE_CREATED = 'created';

    public const TYPE_STATUS_CHANGED = 'status_changed';

    public const TYPE_ASSIGNED = 'assigned';

    public const TYPE_NOTE = 'note';

    public const TYPE_PART_BACKORDERED = 'part_backordered';

    public const TYPE_PARTS_ARRIVED = 'parts_arrived';

    public const TYPE_PAYMENT_RECEIVED = 'payment_received';

    protected $fillable = [
        'job_order_id',
        'type',
        'from_status',
        'to_status',
        'description',
        'meta',
        'is_customer_visible',
        'user_id',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_customer_visible' => 'boolean',
        'from_status' => JobOrderStatus::class,
        'to_status' => JobOrderStatus::class,
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    /** Null when the customer acted, or when nobody did. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param \Illuminate\Database\Eloquent\Builder<self> $query */
    public function scopeCustomerVisible($query)
    {
        return $query->where('is_customer_visible', true);
    }
}
