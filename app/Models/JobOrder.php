<?php

namespace App\Models;

use App\Enums\JobOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOrder extends Model
{
    protected $fillable = [
        'job_order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'device_brand',
        'device_model',
        'serial_number',
        'issue_description',
        'issues',
        'parts_needed',
        'estimated_cost',
        'expected_completion_date',
        'status',
        'received_by',
        'assigned_to',
        'work_performed',
        'final_cost',
        'completed_at',
        'delivered_at',
        'portal_token',
        'approved_by_customer_at',
        'approval_method',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'final_cost' => 'decimal:2',
        'expected_completion_date' => 'date',
        'completed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'approved_by_customer_at' => 'datetime',
        'status' => JobOrderStatus::class,
        'issues' => 'array',
        'parts_needed' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($jobOrder) {
            if (empty($jobOrder->job_order_number)) {
                $base = 'JO-' . date('Ymd') . '-';

                // Start from count of today's records + 1, but verify uniqueness
                $seq = self::whereDate('created_at', today())->count() + 1;

                // Try increasing the sequence until we find an unused job order number
                do {
                    $candidate = $base . str_pad($seq, 4, '0', STR_PAD_LEFT);
                    $exists = self::where('job_order_number', $candidate)->exists();
                    if ($exists) {
                        $seq++;
                        // small sleep to reduce tight-loop race in extreme concurrency
                        usleep(1000);
                    }
                } while ($exists && $seq < 9999);

                // Fallback: if somehow we exhausted attempts, append a unique suffix
                if ($seq >= 9999) {
                    $candidate = $base . uniqid();
                }

                $jobOrder->job_order_number = $candidate;
            }
            
            // Generate unique portal token
            if (empty($jobOrder->portal_token)) {
                $jobOrder->portal_token = bin2hex(random_bytes(32));
            }
        });
    }

    /**
     * Get the staff member who received this job order.
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Get the technician assigned to this job order.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Check if job order is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === JobOrderStatus::COMPLETED;
    }

    /**
     * Check if job order is delivered.
     */
    public function isDelivered(): bool
    {
        return $this->status === JobOrderStatus::DELIVERED;
    }

    /**
     * Check if job order can be edited.
     */
    public function canBeEdited(): bool
    {
        return !in_array($this->status, [JobOrderStatus::COMPLETED, JobOrderStatus::DELIVERED, JobOrderStatus::CANCELLED]);
    }
    
    /**
     * Get the customer portal URL.
     */
    public function getPortalUrlAttribute(): string
    {
        return route('customer.portal.view', ['token' => $this->portal_token]);
    }
    
    /**
     * Mark as approved by customer.
     */
    public function approveByCustomer(): void
    {
        $this->update([
            'status' => JobOrderStatus::APPROVED,
            'approved_by_customer_at' => now(),
            'approval_method' => 'customer',
        ]);
    }
    
    /**
     * Mark as manually approved.
     */
    public function approveManually(): void
    {
        $this->update([
            'status' => JobOrderStatus::APPROVED,
            'approval_method' => 'manual',
        ]);
    }
    
    /**
     * Check if approved by customer.
     */
    public function isApprovedByCustomer(): bool
    {
        return $this->approval_method === 'customer';
    }
}
