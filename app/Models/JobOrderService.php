<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One piece of work booked on a job order, with the technician's diagnosis.
 *
 * service_name and labor_price are snapshots. These used to be resolved by
 * looking up services.name at render time, so renaming a service silently
 * zeroed the labour on every job order that had used it.
 */
class JobOrderService extends Model
{
    protected $fillable = [
        'job_order_id',
        'service_id',
        'service_name',
        'labor_price',
        'diagnosis',
    ];

    protected $casts = [
        'labor_price' => 'decimal:2',
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    /** Null once the service has been removed from the price list. */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
