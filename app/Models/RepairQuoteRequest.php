<?php

namespace App\Models;

use App\Contracts\Contactable;
use App\Models\Concerns\HasCustomerMessages;
use Illuminate\Database\Eloquent\Model;

class RepairQuoteRequest extends Model implements Contactable
{
    use HasCustomerMessages;

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

    public function contactName(): string
    {
        return (string) $this->name;
    }

    public function contactEmail(): ?string
    {
        return $this->email;
    }

    public function contactPhone(): ?string
    {
        // Optional on the public quote form, so this is often absent.
        return $this->phone;
    }

    /**
     * @return array<string, string>
     */
    public function messagePlaceholders(): array
    {
        return [
            'name' => $this->contactName(),
            'device' => trim("{$this->manufacturer} {$this->model}"),
            'price' => $this->quoted_price !== null
                ? 'PHP ' . number_format((float) $this->quoted_price, 2)
                : 'to be confirmed',
            // A quote request only gets a portal token once it has been quoted,
            // so fall back to the shop's own page rather than emitting a link
            // that would 404.
            'portal_url' => $this->portal_token ? $this->portal_url : url('/'),
            'shop' => (string) config('app.name'),
        ];
    }
}
