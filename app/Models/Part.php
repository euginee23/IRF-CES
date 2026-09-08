<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        'category',
        'description',
        'in_stock',
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
        'reorder_point' => 'integer',
        'unit_cost_price' => 'decimal:2',
        'unit_sale_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(PartUsage::class);
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
