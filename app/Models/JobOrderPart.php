<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One replacement part on a job order.
 *
 * Name and both prices are snapshots taken when the line is added: the
 * customer is billed what was agreed on the day, and a historical income
 * figure has to stay reproducible after the catalogue moves.
 */
class JobOrderPart extends Model
{
    /** Chosen for the repair, nothing committed yet. */
    public const STATUS_PENDING = 'pending';

    /** Held on the shelf for this repair. */
    public const STATUS_RESERVED = 'reserved';

    /** Not in stock; waiting on a delivery. */
    public const STATUS_BACKORDERED = 'backordered';

    /** Fitted to the device and gone from stock. */
    public const STATUS_CONSUMED = 'consumed';

    /** Cancelled or removed; any hold has been given back. */
    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'job_order_id',
        'part_id',
        'part_name',
        'sku',
        'quantity',
        'unit_sale_price',
        'unit_cost_price',
        'status',
        'reserved_at',
        'fulfilled_at',
        'consumed_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_sale_price' => 'decimal:2',
        'unit_cost_price' => 'decimal:2',
        'reserved_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    /** Null once the part has been retired from the catalogue. */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    /** What the customer is charged for this line. */
    public function lineTotal(): float
    {
        return (float) $this->unit_sale_price * $this->quantity;
    }

    /** What the line cost the shop, for margin. */
    public function lineCost(): float
    {
        return (float) $this->unit_cost_price * $this->quantity;
    }

    public function isBackordered(): bool
    {
        return $this->status === self::STATUS_BACKORDERED;
    }

    /** @param  Builder<self>  $query */
    public function scopeBackordered(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_BACKORDERED);
    }

    /** @param  Builder<self>  $query */
    public function scopeReserved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RESERVED);
    }
}
