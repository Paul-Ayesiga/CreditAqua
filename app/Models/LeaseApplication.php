<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LeaseApplication extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "application_number",
        "client_id",
        "product_id",
        "quantity",
        "lease_duration_months",
        "monthly_payment",
        "total_lease_amount",
        "down_payment",
        "security_deposit",
        "application_fee",
        "delivery_address",
        "preferred_delivery_date",
        "purpose_of_lease",
        "collateral_description",
        "guarantor_required",
        "employment_verification",
        "income_verification",
        "status",
        "risk_assessment_score",
        "assessment_notes",
        "submitted_at",
        "reviewed_at",
        "reviewed_by",
        "approval_notes",
        "rejection_reason",
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
            "lease_duration_months" => "integer",
            "monthly_payment" => "decimal:2",
            "total_lease_amount" => "decimal:2",
            "down_payment" => "decimal:2",
            "security_deposit" => "decimal:2",
            "application_fee" => "decimal:2",
            "preferred_delivery_date" => "date",
            "guarantor_required" => "boolean",
            "employment_verification" => "boolean",
            "income_verification" => "boolean",
            "risk_assessment_score" => "decimal:2",
            "submitted_at" => "datetime",
            "reviewed_at" => "datetime",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                "application_number",
                "status",
                "monthly_payment",
                "total_lease_amount",
                "risk_assessment_score",
                "reviewed_by",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Application belongs to client
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relationship: Application belongs to product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship: Application reviewed by user
     */
    public function reviewedBy()
    {
        return $this->belongsTo(User::class, "reviewed_by");
    }

    /**
     * Relationship: Application has many guarantors
     */
    public function guarantors()
    {
        return $this->hasMany(Guarantor::class);
    }

    /**
     * Relationship: Application has one lease agreement
     */
    public function leaseAgreement()
    {
        return $this->hasOne(LeaseAgreement::class);
    }

    /**
     * Relationship: Application has many credit assessments
     */
    public function creditAssessments()
    {
        return $this->hasMany(CreditAssessment::class);
    }

    /**
     * Polymorphic relationship: Application has many addresses
     */
    public function addresses()
    {
        return $this->morphMany(Address::class, "addressable");
    }

    /**
     * Polymorphic relationship: Application has many documents
     */
    public function documents()
    {
        return $this->morphMany(Document::class, "documentable");
    }

    /**
     * Polymorphic relationship: Application has many comments
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, "commentable");
    }

    /**
     * Polymorphic relationship: Application has many attachments
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
     * Scope: Draft applications only
     */
    public function scopeDraft($query)
    {
        return $query->where("status", "draft");
    }

    /**
     * Scope: Submitted applications only
     */
    public function scopeSubmitted($query)
    {
        return $query->whereIn("status", [
            "submitted",
            "under_review",
            "approved",
            "rejected",
        ]);
    }

    /**
     * Scope: Under review applications
     */
    public function scopeUnderReview($query)
    {
        return $query->where("status", "under_review");
    }

    /**
     * Scope: Approved applications only
     */
    public function scopeApproved($query)
    {
        return $query->where("status", "approved");
    }

    /**
     * Scope: Rejected applications only
     */
    public function scopeRejected($query)
    {
        return $query->where("status", "rejected");
    }

    /**
     * Scope: Pending review (submitted but not yet reviewed)
     */
    public function scopePendingReview($query)
    {
        return $query->where("status", "submitted")->whereNull("reviewed_at");
    }

    /**
     * Scope: Filter by client
     */
    public function scopeByClient($query, $clientId)
    {
        return $query->where("client_id", $clientId);
    }

    /**
     * Scope: Filter by product
     */
    public function scopeByProduct($query, $productId)
    {
        return $query->where("product_id", $productId);
    }

    /**
     * Scope: Recent applications (within last 30 days)
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where("created_at", ">=", now()->subDays($days));
    }

    /**
     * Scope: Applications requiring guarantor
     */
    public function scopeRequiringGuarantor($query)
    {
        return $query->where("guarantor_required", true);
    }

    /**
     * Check if application is draft
     */
    public function isDraft(): bool
    {
        return $this->status === "draft";
    }

    /**
     * Check if application is submitted
     */
    public function isSubmitted(): bool
    {
        return in_array($this->status, [
            "submitted",
            "under_review",
            "approved",
            "rejected",
        ]);
    }

    /**
     * Check if application is under review
     */
    public function isUnderReview(): bool
    {
        return $this->status === "under_review";
    }

    /**
     * Check if application is approved
     */
    public function isApproved(): bool
    {
        return $this->status === "approved";
    }

    /**
     * Check if application is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === "rejected";
    }

    /**
     * Check if application is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === "cancelled";
    }

    /**
     * Check if application requires guarantor
     */
    public function requiresGuarantor(): bool
    {
        return $this->guarantor_required === true;
    }

    /**
     * Check if application requires employment verification
     */
    public function requiresEmploymentVerification(): bool
    {
        return $this->employment_verification === true;
    }

    /**
     * Check if application requires income verification
     */
    public function requiresIncomeVerification(): bool
    {
        return $this->income_verification === true;
    }

    /**
     * Check if application has been reviewed
     */
    public function isReviewed(): bool
    {
        return !is_null($this->reviewed_at);
    }

    /**
     * Check if application has lease agreement
     */
    public function hasLeaseAgreement(): bool
    {
        return $this->leaseAgreement()->exists();
    }

    /**
     * Get total application cost
     */
    public function getTotalApplicationCostAttribute(): float
    {
        return $this->down_payment +
            $this->security_deposit +
            $this->application_fee;
    }

    /**
     * Get monthly payment with currency
     */
    public function getFormattedMonthlyPaymentAttribute(): string
    {
        $currency = $this->product->currency ?? "UGX";
        return $currency . " " . number_format($this->monthly_payment, 2);
    }

    /**
     * Get total lease amount with currency
     */
    public function getFormattedTotalLeaseAmountAttribute(): string
    {
        $currency = $this->product->currency ?? "UGX";
        return $currency . " " . number_format($this->total_lease_amount, 2);
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            "draft" => "gray",
            "submitted" => "blue",
            "under_review" => "yellow",
            "approved" => "green",
            "rejected" => "red",
            "cancelled" => "orange",
            default => "gray",
        };
    }

    /**
     * Get risk assessment color
     */
    public function getRiskAssessmentColorAttribute(): string
    {
        if (!$this->risk_assessment_score) {
            return "gray";
        }

        return match (true) {
            $this->risk_assessment_score >= 80 => "green",
            $this->risk_assessment_score >= 60 => "yellow",
            $this->risk_assessment_score >= 40 => "orange",
            default => "red",
        };
    }

    /**
     * Get processing time in days
     */
    public function getProcessingTimeDays(): ?int
    {
        if (!$this->submitted_at || !$this->reviewed_at) {
            return null;
        }

        return $this->submitted_at->diffInDays($this->reviewed_at);
    }

    /**
     * Calculate estimated monthly payment
     */
    public function calculateEstimatedMonthlyPayment(): float
    {
        if (!$this->product) {
            return 0;
        }

        return $this->product->lease_price * $this->quantity;
    }

    /**
     * Calculate total lease amount
     */
    public function calculateTotalLeaseAmount(): float
    {
        return $this->monthly_payment * $this->lease_duration_months;
    }

    /**
     * Submit application
     */
    public function submit(): bool
    {
        if ($this->status !== "draft") {
            return false;
        }

        $this->status = "submitted";
        $this->submitted_at = now();

        return $this->save();
    }

    /**
     * Start review process
     */
    public function startReview(): bool
    {
        if ($this->status !== "submitted") {
            return false;
        }

        $this->status = "under_review";

        return $this->save();
    }

    /**
     * Approve application
     */
    public function approve($reviewerId, $notes = null): bool
    {
        if (!in_array($this->status, ["submitted", "under_review"])) {
            return false;
        }

        $this->status = "approved";
        $this->reviewed_by = $reviewerId;
        $this->reviewed_at = now();
        $this->approval_notes = $notes;

        return $this->save();
    }

    /**
     * Reject application
     */
    public function reject($reviewerId, $reason): bool
    {
        if (!in_array($this->status, ["submitted", "under_review"])) {
            return false;
        }

        $this->status = "rejected";
        $this->reviewed_by = $reviewerId;
        $this->reviewed_at = now();
        $this->rejection_reason = $reason;

        return $this->save();
    }

    /**
     * Cancel application
     */
    public function cancel(): bool
    {
        if (in_array($this->status, ["approved", "rejected"])) {
            return false;
        }

        $this->status = "cancelled";

        return $this->save();
    }

    /**
     * Generate unique application number
     */
    public static function generateApplicationNumber(): string
    {
        do {
            $number =
                "LA" .
                date("Y") .
                str_pad(mt_rand(1, 999999), 6, "0", STR_PAD_LEFT);
        } while (static::where("application_number", $number)->exists());

        return $number;
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($application) {
            if (empty($application->application_number)) {
                $application->application_number = static::generateApplicationNumber();
            }
            if (is_null($application->status)) {
                $application->status = "draft";
            }
            if (is_null($application->quantity)) {
                $application->quantity = 1;
            }
            if (is_null($application->down_payment)) {
                $application->down_payment = 0.0;
            }
            if (is_null($application->security_deposit)) {
                $application->security_deposit = 0.0;
            }
            if (is_null($application->application_fee)) {
                $application->application_fee = 0.0;
            }
            if (is_null($application->guarantor_required)) {
                $application->guarantor_required = false;
            }
            if (is_null($application->employment_verification)) {
                $application->employment_verification = false;
            }
            if (is_null($application->income_verification)) {
                $application->income_verification = false;
            }
        });

        // Calculate payments when product and duration are set
        static::saving(function ($application) {
            if (
                $application->product &&
                $application->lease_duration_months &&
                !$application->monthly_payment
            ) {
                $application->monthly_payment = $application->calculateEstimatedMonthlyPayment();
            }

            if (
                $application->monthly_payment &&
                $application->lease_duration_months &&
                !$application->total_lease_amount
            ) {
                $application->total_lease_amount = $application->calculateTotalLeaseAmount();
            }
        });
    }
}
