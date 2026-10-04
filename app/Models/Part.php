<?php

namespace App\Models;

use App\Models\Concerns\RankedSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends Model
{
    use RankedSearch;

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

    /**
     * Parts that fit the phone on the counter, plus the brand's generic ones.
     *
     * Model is typed freely at intake, so it is compared with spaces and
     * dashes stripped and case ignored, and may be the tail of the catalogue
     * name: "s24 ultra" finds "Galaxy S24 Ultra", "iphone15" finds
     * "iPhone 15". Matching on the tail rather than "contains" keeps
     * "iPhone 15" from also listing every iPhone 15 Pro and Plus part.
     *
     * Brand narrows the match when it is one the shop knows; "Other" or a
     * blank brand matches on model alone.
     */
    public function scopeForDevice(Builder $query, ?string $brand, ?string $model): Builder
    {
        $model = self::normaliseModel($model);

        if ($model === '') {
            return $query;
        }

        $brand = trim((string) $brand);
        $normalisedColumn = "REPLACE(REPLACE(LOWER(COALESCE(model, '')), ' ', ''), '-', '')";

        return $query->where(function (Builder $q) use ($brand, $model, $normalisedColumn) {
            $q->where(function (Builder $fits) use ($model, $normalisedColumn) {
                $fits->whereRaw("{$normalisedColumn} = ?", [$model])
                    ->orWhereRaw("{$normalisedColumn} LIKE ?", ['%'.$model]);
            });

            // Speakers, buttons and the like the starter set lists once per
            // brand rather than per phone.
            if ($brand !== '' && $brand !== 'Other') {
                $q->where('manufacturer', $brand)
                    ->orWhere(fn (Builder $generic) => $generic
                        ->where('manufacturer', $brand)
                        ->where('model', 'Generic'));
            }
        });
    }

    /** "Galaxy S24-Ultra " → "galaxys24ultra", the form scopeForDevice() compares. */
    public static function normaliseModel(?string $model): string
    {
        return str_replace([' ', '-'], '', strtolower(trim((string) $model)));
    }

    /**
     * At or below the reorder point.
     *
     * A reorder point of 0 means the part is listed but not stocked — the
     * catalogue seeds thousands of those — so it is never "low".
     */
    public function isLowStock(): bool
    {
        return $this->reorder_point > 0 && $this->in_stock <= $this->reorder_point;
    }

    /**
     * Search by name, SKU, brand or model, best matches first.
     *
     * $alsoSearch adds columns for screens that need them — the inventory
     * screen also finds parts by supplier.
     *
     * Ordered by relevance only when there is a term, so a caller's own
     * ordering still applies to an empty search.
     */
    public function scopeSearch(Builder $query, ?string $term, array $alsoSearch = []): Builder
    {
        return static::applyRankedSearch($query, $term, ['name', 'sku', 'manufacturer', 'model', ...$alsoSearch]);
    }

    /**
     * Parts at or below their reorder point — the query-side twin of isLowStock().
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('reorder_point', '>', 0)
            ->whereColumn('in_stock', '<=', 'reorder_point');
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
