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
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'final_cost' => 'decimal:2',
        'expected_completion_date' => 'date',
        'completed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'status' => JobOrderStatus::class,
        'issues' => 'array',
        'parts_needed' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($jobOrder) {
            if (empty($jobOrder->job_order_number)) {
                $jobOrder->job_order_number = 'JO-' . date('Ymd') . '-' . str_pad(
                    self::whereDate('created_at', today())->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );
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
}
