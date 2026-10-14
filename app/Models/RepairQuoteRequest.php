<?php

namespace App\Models;

use App\Contracts\Contactable;
use App\Models\Concerns\HasCustomerMessages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
        'approved_at',
        'declined_at',
        'decline_reason',
        'response_method',
        'response_seen_at',
    ];

    protected $casts = [
        'images' => 'array',
        'quoted_price' => 'decimal:2',
        'quoted_at' => 'datetime',
        'approved_at' => 'datetime',
        'declined_at' => 'datetime',
        'response_seen_at' => 'datetime',
    ];

    /**
     * Answered by the customer on the portal, and not yet opened by staff.
     * A staff member recording the answer has seen it by definition.
     */
    public function scopeUnseenResponses(Builder $query): Builder
    {
        return $query->where('response_method', 'customer')
            ->whereIn('status', ['approved', 'declined'])
            ->whereNull('response_seen_at');
    }

    public function hasUnseenResponse(): bool
    {
        return $this->response_method === 'customer'
            && in_array($this->status, ['approved', 'declined'], true)
            && $this->response_seen_at === null;
    }

    /** When the current answer was given, whichever way it went. */
    public function respondedAt(): ?Carbon
    {
        return match ($this->status) {
            'approved' => $this->approved_at,
            'declined' => $this->declined_at,
            default => null,
        };
    }

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
