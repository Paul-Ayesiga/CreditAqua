<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Manufacturer extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "user_id",
        "company_name",
        "company_registration_number",
        "business_type",
        "industry",
        "tax_identification_number",
        "website",
        "established_year",
        "employee_count_range",
        "description",
        "logo_path",
        "status",
        "verification_status",
        "verified_at",
        "verified_by",
        "commission_rate",
        "credit_limit",
        "payment_terms",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            "verified_at" => "datetime",
            "commission_rate" => "decimal:2",
            "credit_limit" => "decimal:2",
            "payment_terms" => "integer",
            "established_year" => "integer",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                "company_name",
                "status",
                "verification_status",
                "commission_rate",
                "credit_limit",
                "payment_terms",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Manufacturer belongs to user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Manufacturer verified by user
     */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, "verified_by");
    }

    /**
     * Relationship: Manufacturer has many contacts
     */
    public function contacts()
    {
        return $this->hasMany(ManufacturerContact::class);
    }

    /**
     * Relationship: Manufacturer has many products
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Relationship: Manufacturer has many lease agreements
     */
    public function leaseAgreements()
    {
        return $this->hasMany(LeaseAgreement::class);
    }

    /**
     * Relationship: Manufacturer has many commissions
     */
    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * Polymorphic relationship: Manufacturer has many addresses
     */
    public function addresses()
    {
        return $this->morphMany(Address::class, "addressable");
    }

    /**
     * Polymorphic relationship: Manufacturer has many documents
     */
    public function documents()
    {
        return $this->morphMany(Document::class, "documentable");
    }

    /**
     * Polymorphic relationship: Manufacturer has many comments
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, "commentable");
    }

    /**
     * Polymorphic relationship: Manufacturer has many attachments
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
     * Scope: Filter by verification status
     */
    public function scopeByVerificationStatus($query, $status)
    {
        return $query->where("verification_status", $status);
    }

    /**
     * Scope: Active manufacturers only
     */
    public function scopeActive($query)
    {
        return $query->where("status", "active");
    }

    /**
     * Scope: Verified manufacturers only
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
        return $query->whereIn("verification_status", [
            "unverified",
            "under_review",
        ]);
    }

    /**
     * Scope: Filter by business type
     */
    public function scopeByBusinessType($query, $type)
    {
        return $query->where("business_type", $type);
    }

    /**
     * Scope: Filter by industry
     */
    public function scopeByIndustry($query, $industry)
    {
        return $query->where("industry", $industry);
    }

    /**
     * Get primary contact
     */
    public function getPrimaryContact()
    {
        return $this->contacts()->where("is_primary", true)->first();
    }

    /**
     * Get primary address
     */
    public function getPrimaryAddress()
    {
        return $this->addresses()->where("is_default", true)->first();
    }

    /**
     * Check if manufacturer is active
     */
    public function isActive(): bool
    {
        return $this->status === "active";
    }

    /**
     * Check if manufacturer is verified
     */
    public function isVerified(): bool
    {
        return $this->verification_status === "verified";
    }

    /**
     * Check if manufacturer is pending verification
     */
    public function isPendingVerification(): bool
    {
        return in_array($this->verification_status, [
            "unverified",
            "under_review",
        ]);
    }

    /**
     * Check if manufacturer is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === "suspended";
    }

    /**
     * Get total products count
     */
    public function getTotalProductsCount(): int
    {
        return $this->products()->count();
    }

    /**
     * Get active products count
     */
    public function getActiveProductsCount(): int
    {
        return $this->products()->where("status", "active")->count();
    }

    /**
     * Get total lease agreements count
     */
    public function getTotalLeaseAgreementsCount(): int
    {
        return $this->leaseAgreements()->count();
    }

    /**
     * Get active lease agreements count
     */
    public function getActiveLeaseAgreementsCount(): int
    {
        return $this->leaseAgreements()->where("status", "active")->count();
    }

    /**
     * Get total commissions earned
     */
    public function getTotalCommissionsEarned(): float
    {
        return $this->commissions()
            ->where("status", "paid")
            ->sum("commission_amount");
    }

    /**
     * Get pending commissions
     */
    public function getPendingCommissions(): float
    {
        return $this->commissions()
            ->whereIn("status", ["pending", "approved"])
            ->sum("commission_amount");
    }

    /**
     * Get years in business
     */
    public function getYearsInBusiness(): ?int
    {
        return $this->established_year
            ? now()->year - $this->established_year
            : null;
    }

    /**
     * Get company age category
     */
    public function getCompanyAgeCategoryAttribute(): string
    {
        $years = $this->getYearsInBusiness();

        if (!$years) {
            return "Unknown";
        }

        return match (true) {
            $years < 2 => "Startup",
            $years < 5 => "Young",
            $years < 10 => "Established",
            $years < 20 => "Mature",
            default => "Veteran",
        };
    }

    /**
     * Get verification badge color
     */
    public function getVerificationBadgeColorAttribute(): string
    {
        return match ($this->verification_status) {
            "verified" => "green",
            "under_review" => "yellow",
            "rejected" => "red",
            "unverified" => "gray",
            default => "gray",
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            "active" => "green",
            "pending" => "yellow",
            "suspended" => "red",
            "inactive" => "gray",
            default => "gray",
        };
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($manufacturer) {
            if (is_null($manufacturer->status)) {
                $manufacturer->status = "pending";
            }
            if (is_null($manufacturer->verification_status)) {
                $manufacturer->verification_status = "unverified";
            }
            if (is_null($manufacturer->commission_rate)) {
                $manufacturer->commission_rate = 0.0;
            }
            if (is_null($manufacturer->credit_limit)) {
                $manufacturer->credit_limit = 0.0;
            }
            if (is_null($manufacturer->payment_terms)) {
                $manufacturer->payment_terms = 30;
            }
        });
    }
}
