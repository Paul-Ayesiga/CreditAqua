<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Commission extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'manufacturer_id',
        'lease_agreement_id',
        'payment_id',
        'commission_type',
        'base_amount',
        'commission_rate',
        'commission_amount',
        'currency',
        'status',
        'calculation_date',
        'due_date',
        'paid_date',
        'payment_reference',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'calculation_date' => 'date',
            'due_date' => 'date',
            'paid_date' => 'date',
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'commission_type',
                'base_amount',
                'commission_rate',
                'commission_amount',
                'status',
                'paid_date',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Commission belongs to manufacturer
     */
    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * Relationship: Commission belongs to lease agreement
     */
    public function leaseAgreement()
    {
        return $this->belongsTo(LeaseAgreement::class);
    }

    /**
     * Relationship: Commission belongs to payment
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Scope: Filter by commission type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('commission_type', $type);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Pending commissions only
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Approved commissions only
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: Paid commissions only
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope: Cancelled commissions only
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope: Lease commissions only
     */
    public function scopeLeaseCommissions($query)
    {
        return $query->where('commission_type', 'lease_commission');
    }

    /**
     * Scope: Maintenance commissions only
     */
    public function scopeMaintenanceCommissions($query)
    {
        return $query->where('commission_type', 'maintenance_commission');
    }

    /**
     * Scope: Bonus commissions only
     */
    public function scopeBonuses($query)
    {
        return $query->where('commission_type', 'bonus');
    }

    /**
     * Scope: Due commissions
     */
    public function scopeDue($query)
    {
        return $query->where('due_date', '<=', now()->toDateString())
            ->whereIn('status', ['pending', 'approved']);
    }

    /**
     * Scope: Overdue commissions
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->whereIn('status', ['pending', 'approved']);
    }

    /**
     * Check if commission is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if commission is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if commission is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if commission is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if commission is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               in_array($this->status, ['pending', 'approved']);
    }

    /**
     * Check if commission is a lease commission
     */
    public function isLeaseCommission(): bool
    {
        return $this->commission_type === 'lease_commission';
    }

    /**
     * Check if commission is a maintenance commission
     */
    public function isMaintenanceCommission(): bool
    {
        return $this->commission_type === 'maintenance_commission';
    }

    /**
     * Check if commission is a bonus
     */
    public function isBonus(): bool
    {
        return $this->commission_type === 'bonus';
    }

    /**
     * Get commission type badge color
     */
    public function getCommissionTypeBadgeColorAttribute(): string
    {
        return match ($this->commission_type) {
            'lease_commission' => 'blue',
            'maintenance_commission' => 'green',
            'bonus' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'approved' => 'blue',
            'paid' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get formatted commission amount with currency
     */
    public function getFormattedCommissionAmountAttribute(): string
    {
        return number_format($this->commission_amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get formatted base amount with currency
     */
    public function getFormattedBaseAmountAttribute(): string
    {
        return number_format($this->base_amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get formatted commission rate as percentage
     */
    public function getFormattedCommissionRateAttribute(): string
    {
        return number_format($this->commission_rate, 2) . '%';
    }

    /**
     * Get days until due (negative if overdue)
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Calculate commission amount based on base amount and rate
     */
    public function calculateCommissionAmount(): float
    {
        return ($this->base_amount * $this->commission_rate) / 100;
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($commission) {
            if (empty($commission->currency)) {
                $commission->currency = 'UGX';
            }
            if (is_null($commission->status)) {
                $commission->status = 'pending';
            }
            if (is_null($commission->calculation_date)) {
                $commission->calculation_date = now()->toDateString();
            }
            
            // Auto-calculate commission amount if not provided
            if (is_null($commission->commission_amount) && 
                $commission->base_amount && 
                $commission->commission_rate) {
                $commission->commission_amount = $commission->calculateCommissionAmount();
            }
        });

        // Recalculate commission amount when base amount or rate changes
        static::saving(function ($commission) {
            if ($commission->isDirty(['base_amount', 'commission_rate'])) {
                $commission->commission_amount = $commission->calculateCommissionAmount();
            }
        });
    }
}
