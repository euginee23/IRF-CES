<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends Model
{
    /**
     * The most units that may be requested for a single part in one restock
     * request — a supplier may not have more than this available at once.
     */
    public const MAX_RESTOCK_QUANTITY = 20;

    protected $fillable = [
        'name',
        'sku',
        'part_category_id',
        // Superseded by part_category_id and kept only so the old value
        // survives one release; nothing should read it. Dropped once the
        // category screens have shipped.
        'category',
        'description',
        'in_stock',
        'reserved_stock',
        'reorder_point',
        'unit_cost_price',
        'unit_sale_price',
        'supplier',
        'manufacturer',
        'model',
        'is_active',
    ];

    protected $casts = [
        'in_stock' => 'integer',
        'reserved_stock' => 'integer',
        'reorder_point' => 'integer',
        'unit_cost_price' => 'decimal:2',
        'unit_sale_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /** Every time this part was put on a repair. */
    public function jobOrderLines(): HasMany
    {
        return $this->hasMany(JobOrderPart::class);
    }

    /** The physical stock ledger for this part, newest first. */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    /**
     * What can still be promised to a new repair.
     *
     * in_stock counts what is on the shelf, including units already spoken
     * for by an approved repair, so it is not what a new job order can have.
     */
    public function availableStock(): int
    {
        return max(0, $this->in_stock - $this->reserved_stock);
    }

    public function hasAvailable(int $quantity): bool
    {
        return $this->availableStock() >= $quantity;
    }

    public function partCategory(): BelongsTo
    {
        return $this->belongsTo(PartCategory::class);
    }

    /**
     * The category's name, for screens and exports.
     *
     * Falls back to the legacy string so a part that predates the backfill,
     * or whose category was deleted, still reads as something.
     */
    public function getCategoryNameAttribute(): ?string
    {
        return $this->partCategory?->name ?? $this->category;
    }

    public function isLowStock(): bool
    {
        return $this->in_stock <= $this->reorder_point;
    }

    /**
     * Parts at or below their reorder point — the query-side twin of isLowStock().
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('in_stock', '<=', 'reorder_point');
    }

    /**
     * Suggested restock amount: enough to reach double the reorder point,
     * never above the per-part cap.
     */
    public function suggestedRestockQuantity(): int
    {
        return (int) min(
            self::MAX_RESTOCK_QUANTITY,
            max(1, ($this->reorder_point * 2) - $this->in_stock)
        );
    }

    public function deductStock(int $quantity): bool
    {
        if ($this->in_stock >= $quantity) {
            $this->decrement('in_stock', $quantity);

            return true;
        }

        return false;
    }

    public function addStock(int $quantity): void
    {
        $this->increment('in_stock', $quantity);
    }
}
