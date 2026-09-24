<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A bucket parts are grouped into — Display, Battery, Power & Charging.
 *
 * Replaces the free-text parts.category string, which the admin filter and
 * the seeded data had drifted apart on.
 */
class PartCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // The slug is never edited directly, so it is derived here rather than
        // asked for in the form.
        static::saving(function (self $category) {
            if (empty($category->slug) || $category->isDirty('name')) {
                $category->slug = self::uniqueSlug($category->name, $category->id);
            }
        });
    }

    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Shop order first, then name, so the list is stable. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Distinct names can still slug the same, and slug is unique.
     */
    private static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $suffix = 2;

        while (self::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
