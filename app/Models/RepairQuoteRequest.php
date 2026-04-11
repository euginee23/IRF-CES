<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairQuoteRequest extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'manufacturer',
        'model',
        'issue_description',
        'images',
        'status',
        'quoted_price',
        'quote_notes',
        'quoted_at',
        'portal_token',
    ];

    protected $casts = [
        'images' => 'array',
        'quoted_price' => 'decimal:2',
        'quoted_at' => 'datetime',
    ];

    public function getPortalUrlAttribute(): string
    {
        return route('customer.portal.quote', ['token' => $this->portal_token]);
    }
}
