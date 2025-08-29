<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Guarantor extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "lease_application_id",
        "name",
        "id_type",
        "id_number",
        "phone",
        "email",
        "occupation",
        "employer",
        "monthly_income",
        "relationship_to_client",
        "guarantee_amount",
        "consent_given",
        "consent_date",
        "verification_status",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            "monthly_income" => "decimal:2",
            "guarantee_amount" => "decimal:2",
            "consent_given" => "boolean",
            "consent_date" => "datetime",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                "name",
                "verification_status",
                "consent_given",
                "guarantee_amount",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Guarantor belongs to lease application
     */
    public function leaseApplication()
    {
        return $this->belongsTo(LeaseApplication::class);
    }

    /**
     * Polymorphic relationship: Guarantor has many addresses
     */
    public function addresses()
    {
        return $this->morphMany(Address::class, "addressable");
    }

    /**
     * Polymorphic relationship: Guarantor has many documents
     */
    public function documents()
    {
        return $this->morphMany(Document::class, "documentable");
    }

    /**
     * Polymorphic relationship: Guarantor has many comments
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, "commentable");
    }

    /**
     * Polymorphic relationship: Guarantor has many attachments
     */
    public function attachments()
    {
        return $this->morphMany(Attachment::class, "attachable");
    }

    /**
     * Scope: Filter by verification status
     */
    public function scopeByVerificationStatus($query, $status)
    {
        return $query->where("verification_status", $status);
    }

    /**
     * Scope: Verified guarantors only
     */
    public function scopeVerified($query)
    {
        return $query->where("verification_status", "verified");
    }

    /**
     * Scope: Pending verification
     */
    public function scopePendingVerification($query)
    {
        return $query->where("verification_status", "pending");
    }

    /**
     * Scope: Rejected guarantors
     */
    public function scopeRejected($query)
    {
        return $query->where("verification_status", "rejected");
    }

    /**
     * Scope: Consented guarantors only
     */
    public function scopeConsented($query)
    {
        return $query->where("consent_given", true);
    }

    /**
     * Scope: Non-consented guarantors
     */
    public function scopeNotConsented($query)
    {
        return $query->where("consent_given", false);
    }

    /**
     * Scope: Filter by ID type
     */
    public function scopeByIdType($query, $type)
    {
        return $query->where("id_type", $type);
    }

    /**
     * Check if guarantor has given consent
     */
    public function hasGivenConsent(): bool
    {
        return $this->consent_given === true;
    }

    /**
     * Check if guarantor is verified
     */
    public function isVerified(): bool
    {
        return $this->verification_status === "verified";
    }

    /**
     * Check if verification is pending
     */
    public function isPendingVerification(): bool
    {
        return $this->verification_status === "pending";
    }

    /**
     * Check if guarantor is rejected
     */
    public function isRejected(): bool
    {
        return $this->verification_status === "rejected";
    }

    /**
     * Give consent
     */
    public function giveConsent(): bool
    {
        $this->consent_given = true;
        $this->consent_date = now();

        return $this->save();
    }

    /**
     * Withdraw consent
     */
    public function withdrawConsent(): bool
    {
        $this->consent_given = false;
        $this->consent_date = null;

        return $this->save();
    }

    /**
     * Verify guarantor
     */
    public function verify(): bool
    {
        $this->verification_status = "verified";

        return $this->save();
    }

    /**
     * Reject guarantor
     */
    public function reject(): bool
    {
        $this->verification_status = "rejected";

        return $this->save();
    }

    /**
     * Get primary address
     */
    public function getPrimaryAddress()
    {
        return $this->addresses()->where("is_default", true)->first();
    }

    /**
     * Get full contact information
     */
    public function getFullContactInfoAttribute(): string
    {
        $info = $this->name;

        if ($this->phone) {
            $info .= " - " . $this->phone;
        }

        if ($this->email) {
            $info .= " - " . $this->email;
        }

        return $info;
    }

    /**
     * Get verification status badge color
     */
    public function getVerificationBadgeColorAttribute(): string
    {
        return match ($this->verification_status) {
            "verified" => "green",
            "rejected" => "red",
            "pending" => "yellow",
            default => "gray",
        };
    }

    /**
     * Get consent status badge color
     */
    public function getConsentBadgeColorAttribute(): string
    {
        return $this->consent_given ? "green" : "red";
    }

    /**
     * Get ID type badge color
     */
    public function getIdTypeBadgeColorAttribute(): string
    {
        return match ($this->id_type) {
            "national_id" => "blue",
            "passport" => "green",
            "driving_license" => "orange",
            default => "gray",
        };
    }

    /**
     * Get formatted monthly income
     */
    public function getFormattedMonthlyIncomeAttribute(): string
    {
        if (!$this->monthly_income) {
            return "Not provided";
        }

        return "UGX " . number_format($this->monthly_income, 2);
    }

    /**
     * Get formatted guarantee amount
     */
    public function getFormattedGuaranteeAmountAttribute(): string
    {
        return "UGX " . number_format($this->guarantee_amount, 2);
    }

    /**
     * Calculate debt-to-income ratio for guarantee
     */
    public function getDebtToIncomeRatioAttribute(): ?float
    {
        if (!$this->monthly_income || $this->monthly_income <= 0) {
            return null;
        }

        // Assuming the guarantee amount represents monthly commitment
        $monthlyCommitment = $this->guarantee_amount / 12; // Convert annual to monthly
        return ($monthlyCommitment / $this->monthly_income) * 100;
    }

    /**
     * Check if guarantor can afford the guarantee
     */
    public function canAffordGuarantee(): bool
    {
        $ratio = $this->debt_to_income_ratio;

        if (!$ratio) {
            return false;
        }

        // Generally, debt-to-income ratio should be below 30%
        return $ratio <= 30;
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($guarantor) {
            if (is_null($guarantor->consent_given)) {
                $guarantor->consent_given = false;
            }
            if (is_null($guarantor->verification_status)) {
                $guarantor->verification_status = "pending";
            }
        });
    }
}
