<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LeaseAgreement extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "agreement_number",
        "lease_application_id",
        "client_id",
        "product_id",
        "manufacturer_id",
        "quantity",
        "lease_start_date",
        "lease_end_date",
        "lease_duration_months",
        "monthly_payment",
        "total_lease_amount",
        "down_payment",
        "security_deposit",
        "late_fee_percentage",
        "grace_period_days",
        "early_termination_fee",
        "maintenance_responsibility",
        "insurance_required",
        "insurance_provider",
        "terms_and_conditions",
        "special_terms",
        "status",
        "signed_by_client",
        "signed_by_manufacturer",
        "client_signature_date",
        "manufacturer_signature_date",
        "witness_name",
        "witness_signature_date",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            "quantity" => "integer",
            "lease_start_date" => "date",
            "lease_end_date" => "date",
            "lease_duration_months" => "integer",
            "monthly_payment" => "decimal:2",
            "total_lease_amount" => "decimal:2",
            "down_payment" => "decimal:2",
            "security_deposit" => "decimal:2",
            "late_fee_percentage" => "decimal:2",
            "grace_period_days" => "integer",
            "early_termination_fee" => "decimal:2",
            "insurance_required" => "boolean",
            "signed_by_client" => "boolean",
            "signed_by_manufacturer" => "boolean",
            "client_signature_date" => "datetime",
            "manufacturer_signature_date" => "datetime",
            "witness_signature_date" => "datetime",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                "agreement_number",
                "status",
                "monthly_payment",
                "signed_by_client",
                "signed_by_manufacturer",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Agreement belongs to lease application
     */
    public function leaseApplication()
    {
        return $this->belongsTo(LeaseApplication::class);
    }

    /**
     * Relationship: Agreement belongs to client
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relationship: Agreement belongs to product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship: Agreement belongs to manufacturer
     */
    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * Relationship: Agreement has many payment schedules
     */
    public function paymentSchedules()
    {
        return $this->hasMany(PaymentSchedule::class);
    }

    /**
     * Relationship: Agreement has many payments
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Relationship: Agreement has many commissions
     */
    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * Relationship: Agreement has many maintenance schedules
     */
    public function maintenanceSchedules()
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    /**
     * Relationship: Agreement has many insurance policies
     */
    public function insurancePolicies()
    {
        return $this->hasMany(InsurancePolicy::class);
    }

    /**
     * Relationship: Agreement has many late payment notices
     */
    public function latePaymentNotices()
    {
        return $this->hasMany(LatePaymentNotice::class);
    }

    /**
     * Polymorphic relationship: Agreement has many addresses
     */
    public function addresses()
    {
        return $this->morphMany(Address::class, "addressable");
    }

    /**
     * Polymorphic relationship: Agreement has many documents
     */
    public function documents()
    {
        return $this->morphMany(Document::class, "documentable");
    }

    /**
     * Polymorphic relationship: Agreement has many comments
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, "commentable");
    }

    /**
     * Polymorphic relationship: Agreement has many attachments
     */
    public function attachments()
    {
        return $this->morphMany(Attachment::class, "attachable");
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where("status", $status);
    }

    /**
     * Scope: Active agreements only
     */
    public function scopeActive($query)
    {
        return $query->where("status", "active");
    }

    /**
     * Scope: Completed agreements only
     */
    public function scopeCompleted($query)
    {
        return $query->where("status", "completed");
    }

    /**
     * Scope: Terminated agreements only
     */
    public function scopeTerminated($query)
    {
        return $query->where("status", "terminated");
    }

    /**
     * Scope: Breached agreements only
     */
    public function scopeBreached($query)
    {
        return $query->where("status", "breached");
    }

    /**
     * Scope: Fully signed agreements
     */
    public function scopeFullySigned($query)
    {
        return $query
            ->where("signed_by_client", true)
            ->where("signed_by_manufacturer", true);
    }

    /**
     * Scope: Pending client signature
     */
    public function scopePendingClientSignature($query)
    {
        return $query->where("signed_by_client", false);
    }

    /**
     * Scope: Pending manufacturer signature
     */
    public function scopePendingManufacturerSignature($query)
    {
        return $query->where("signed_by_manufacturer", false);
    }

    /**
     * Scope: Expiring soon (within 30 days)
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereBetween("lease_end_date", [
            now(),
            now()->addDays($days),
        ]);
    }

    /**
     * Scope: Expired agreements
     */
    public function scopeExpired($query)
    {
        return $query->where("lease_end_date", "<", now());
    }

    /**
     * Scope: Filter by client
     */
    public function scopeByClient($query, $clientId)
    {
        return $query->where("client_id", $clientId);
    }

    /**
     * Scope: Filter by manufacturer
     */
    public function scopeByManufacturer($query, $manufacturerId)
    {
        return $query->where("manufacturer_id", $manufacturerId);
    }

    /**
     * Check if agreement is active
     */
    public function isActive(): bool
    {
        return $this->status === "active";
    }

    /**
     * Check if agreement is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === "completed";
    }

    /**
     * Check if agreement is terminated
     */
    public function isTerminated(): bool
    {
        return $this->status === "terminated";
    }

    /**
     * Check if agreement is breached
     */
    public function isBreached(): bool
    {
        return $this->status === "breached";
    }

    /**
     * Check if agreement is fully signed
     */
    public function isFullySigned(): bool
    {
        return $this->signed_by_client && $this->signed_by_manufacturer;
    }

    /**
     * Check if client has signed
     */
    public function isSignedByClient(): bool
    {
        return $this->signed_by_client === true;
    }

    /**
     * Check if manufacturer has signed
     */
    public function isSignedByManufacturer(): bool
    {
        return $this->signed_by_manufacturer === true;
    }

    /**
     * Check if agreement is expired
     */
    public function isExpired(): bool
    {
        return $this->lease_end_date && $this->lease_end_date->isPast();
    }

    /**
     * Check if agreement is expiring soon
     */
    public function isExpiringSoon($days = 30): bool
    {
        return $this->lease_end_date &&
            $this->lease_end_date->isBetween(now(), now()->addDays($days));
    }

    /**
     * Check if insurance is required
     */
    public function requiresInsurance(): bool
    {
        return $this->insurance_required === true;
    }

    /**
     * Check if client is responsible for maintenance
     */
    public function isClientResponsibleForMaintenance(): bool
    {
        return $this->maintenance_responsibility === "client";
    }

    /**
     * Check if manufacturer is responsible for maintenance
     */
    public function isManufacturerResponsibleForMaintenance(): bool
    {
        return $this->maintenance_responsibility === "manufacturer";
    }

    /**
     * Check if maintenance is shared responsibility
     */
    public function isSharedMaintenanceResponsibility(): bool
    {
        return $this->maintenance_responsibility === "shared";
    }

    /**
     * Get remaining lease duration in days
     */
    public function getRemainingDays(): int
    {
        if (!$this->lease_end_date) {
            return 0;
        }

        return max(0, now()->diffInDays($this->lease_end_date, false));
    }

    /**
     * Get remaining lease duration in months
     */
    public function getRemainingMonths(): int
    {
        if (!$this->lease_end_date) {
            return 0;
        }

        return max(0, now()->diffInMonths($this->lease_end_date, false));
    }

    /**
     * Get lease progress percentage
     */
    public function getLeaseProgressPercentage(): float
    {
        if (!$this->lease_start_date || !$this->lease_end_date) {
            return 0;
        }

        $totalDays = $this->lease_start_date->diffInDays($this->lease_end_date);
        $elapsedDays = $this->lease_start_date->diffInDays(now());

        if ($totalDays <= 0) {
            return 100;
        }

        return min(100, max(0, ($elapsedDays / $totalDays) * 100));
    }

    /**
     * Get total amount paid
     */
    public function getTotalAmountPaid(): float
    {
        return $this->payments()->where("status", "completed")->sum("amount");
    }

    /**
     * Get outstanding amount
     */
    public function getOutstandingAmount(): float
    {
        return max(0, $this->total_lease_amount - $this->getTotalAmountPaid());
    }

    /**
     * Get payment completion percentage
     */
    public function getPaymentCompletionPercentage(): float
    {
        if ($this->total_lease_amount <= 0) {
            return 100;
        }

        return ($this->getTotalAmountPaid() / $this->total_lease_amount) * 100;
    }

    /**
     * Get next payment due date
     */
    public function getNextPaymentDueDate(): ?string
    {
        $nextPayment = $this->paymentSchedules()
            ->where("status", "pending")
            ->orderBy("due_date")
            ->first();

        return $nextPayment?->due_date;
    }

    /**
     * Get overdue payments count
     */
    public function getOverduePaymentsCount(): int
    {
        return $this->paymentSchedules()->where("status", "overdue")->count();
    }

    /**
     * Check if agreement has overdue payments
     */
    public function hasOverduePayments(): bool
    {
        return $this->getOverduePaymentsCount() > 0;
    }

    /**
     * Sign by client
     */
    public function signByClient(): bool
    {
        if ($this->signed_by_client) {
            return true;
        }

        $this->signed_by_client = true;
        $this->client_signature_date = now();

        return $this->save();
    }

    /**
     * Sign by manufacturer
     */
    public function signByManufacturer(): bool
    {
        if ($this->signed_by_manufacturer) {
            return true;
        }

        $this->signed_by_manufacturer = true;
        $this->manufacturer_signature_date = now();

        return $this->save();
    }

    /**
     * Activate agreement
     */
    public function activate(): bool
    {
        if (!$this->isFullySigned()) {
            return false;
        }

        $this->status = "active";
        return $this->save();
    }

    /**
     * Complete agreement
     */
    public function complete(): bool
    {
        if ($this->status !== "active") {
            return false;
        }

        $this->status = "completed";
        return $this->save();
    }

    /**
     * Terminate agreement
     */
    public function terminate(): bool
    {
        if (!in_array($this->status, ["active", "breached"])) {
            return false;
        }

        $this->status = "terminated";
        return $this->save();
    }

    /**
     * Mark as breached
     */
    public function markAsBreached(): bool
    {
        if ($this->status !== "active") {
            return false;
        }

        $this->status = "breached";
        return $this->save();
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            "draft" => "gray",
            "active" => "green",
            "completed" => "blue",
            "terminated" => "orange",
            "breached" => "red",
            default => "gray",
        };
    }

    /**
     * Get maintenance responsibility badge color
     */
    public function getMaintenanceResponsibilityColorAttribute(): string
    {
        return match ($this->maintenance_responsibility) {
            "client" => "blue",
            "manufacturer" => "green",
            "shared" => "yellow",
            default => "gray",
        };
    }

    /**
     * Get formatted monthly payment
     */
    public function getFormattedMonthlyPaymentAttribute(): string
    {
        $currency = $this->product->currency ?? "UGX";
        return $currency . " " . number_format($this->monthly_payment, 2);
    }

    /**
     * Get formatted total lease amount
     */
    public function getFormattedTotalLeaseAmountAttribute(): string
    {
        $currency = $this->product->currency ?? "UGX";
        return $currency . " " . number_format($this->total_lease_amount, 2);
    }

    /**
     * Generate unique agreement number
     */
    public static function generateAgreementNumber(): string
    {
        do {
            $number =
                "AGR" .
                date("Y") .
                str_pad(mt_rand(1, 999999), 6, "0", STR_PAD_LEFT);
        } while (static::where("agreement_number", $number)->exists());

        return $number;
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($agreement) {
            if (empty($agreement->agreement_number)) {
                $agreement->agreement_number = static::generateAgreementNumber();
            }
            if (is_null($agreement->status)) {
                $agreement->status = "draft";
            }
            if (is_null($agreement->late_fee_percentage)) {
                $agreement->late_fee_percentage = 5.0;
            }
            if (is_null($agreement->grace_period_days)) {
                $agreement->grace_period_days = 5;
            }
            if (is_null($agreement->maintenance_responsibility)) {
                $agreement->maintenance_responsibility = "client";
            }
            if (is_null($agreement->insurance_required)) {
                $agreement->insurance_required = false;
            }
            if (is_null($agreement->signed_by_client)) {
                $agreement->signed_by_client = false;
            }
            if (is_null($agreement->signed_by_manufacturer)) {
                $agreement->signed_by_manufacturer = false;
            }
        });

        // Calculate lease end date when start date and duration are set
        static::saving(function ($agreement) {
            if (
                $agreement->lease_start_date &&
                $agreement->lease_duration_months &&
                !$agreement->lease_end_date
            ) {
                $agreement->lease_end_date = $agreement->lease_start_date->addMonths(
                    $agreement->lease_duration_months,
                );
            }
        });
    }
}
