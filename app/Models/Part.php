<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'category',
        'description',
        'in_stock',
        'reorder_point',
        'unit_price',
        'supplier',
        'location',
        'is_active',
    ];

    protected $casts = [
        'in_stock' => 'integer',
        'reorder_point' => 'integer',
        'unit_price' => 'decimal:2',
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
