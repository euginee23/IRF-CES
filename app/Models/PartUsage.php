<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartUsage extends Model
{
    protected $fillable = [
        'part_id',
        'repair_quote_request_id',
        'quantity_used',
        'cost_per_unit',
        'total_cost',
        'used_by',
        'notes',
    ];

    protected $casts = [
        'quantity_used' => 'integer',
        'cost_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function repairQuoteRequest(): BelongsTo
    {
        return $this->belongsTo(RepairQuoteRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }
}
