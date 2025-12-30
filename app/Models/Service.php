<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'category',
        'description',
        'labor_price',
        'estimated_duration',
        'is_active',
    ];

    protected $casts = [
        'labor_price' => 'decimal:2',
        'estimated_duration' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to only get active services
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get services grouped by category
     */
    public static function getGroupedByCategory()
    {
        return self::active()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');
    }
}
