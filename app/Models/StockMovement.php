<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One physical change to a part's stock.
 *
 * Written only by InventoryService, so that the invariant it maintains —
 * SUM(quantity) per part equals parts.in_stock — has a single author.
 *
 * @see \App\Services\Inventory\InventoryService
 */
class StockMovement extends Model
{
    protected $fillable = [
        'part_id',
        'quantity',
        'type',
        'reference_type',
        'reference_id',
        'unit_cost_price',
        'note',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'type' => StockMovementType::class,
        'unit_cost_price' => 'decimal:2',
    ];

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    /** The job order line this movement came from, where there is one. */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** What this movement was worth, as a positive figure. */
    public function value(): float
    {
        return abs($this->quantity) * (float) $this->unit_cost_price;
    }
}
