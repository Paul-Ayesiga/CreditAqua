<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Client extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "user_id",
        "client_code",
        "client_type",
        "title",
        "first_name",
        "middle_name",
        "last_name",
        "business_name",
        "date_of_birth",
        "gender",
        "marital_status",
        "id_type",
        "id_number",
        "phone_primary",
        "phone_secondary",
        "email_secondary",
        "occupation",
        "employer",
        "monthly_income",
        "bank_name",
        "bank_account_number",
        "mobile_money_provider",
        "mobile_money_number",
        "preferred_payment_method",
        "kyc_status",
        "kyc_completed_at",
        "credit_score",
        "risk_rating",
        "status",
        "registration_source",
        "referred_by",
        "notes",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            "date_of_birth" => "date",
            "kyc_completed_at" => "datetime",
            "monthly_income" => "decimal:2",
            "credit_score" => "integer",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                "client_code",
                "client_type",
                "first_name",
                "last_name",
                "kyc_status",
                "status",
                "credit_score",
                "risk_rating",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Client belongs to user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Client referred by another client
     */
    public function referredBy()
    {
        return $this->belongsTo(Client::class, "referred_by");
    }

    /**
     * Relationship: Client has many referrals
     */
    public function referrals()
    {
        return $this->hasMany(Client::class, "referred_by");
    }

    /**
     * Relationship: Client has many references
     */
    public function references()
    {
        return $this->hasMany(ClientReference::class);
    }

    /**
     * Relationship: Client has many dependents
     */
    public function dependents()
    {
        return $this->hasMany(ClientDependent::class);
    }

    /**
     * Relationship: Client has many lease applications
     */
    public function leaseApplications()
    {
        return $this->hasMany(LeaseApplication::class);
    }

    /**
     * Relationship: Client has many lease agreements
     */
    public function leaseAgreements()
    {
        return $this->hasMany(LeaseAgreement::class);
    }

    /**
     * Relationship: Client has many payments
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Relationship: Client has many payment methods
     */
    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    /**
     * Relationship: Client has many credit assessments
     */
    public function creditAssessments()
    {
        return $this->hasMany(CreditAssessment::class);
    }

    /**
     * Relationship: Client has many late payment notices
     */
    public function latePaymentNotices()
    {
        return $this->hasMany(LatePaymentNotice::class);
    }

    /**
     * Relationship: Client has many campaign responses
     */
    public function campaignResponses()
    {
        return $this->hasMany(CampaignResponse::class);
    }

    /**
     * Polymorphic relationship: Client has many addresses
     */
    public function addresses()
    {
        return $this->morphMany(Address::class, "addressable");
    }

    /**
     * Polymorphic relationship: Client has many documents
     */
    public function documents()
    {
        return $this->morphMany(Document::class, "documentable");
    }

    /**
     * Polymorphic relationship: Client has many comments
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, "commentable");
    }

    /**
     * Polymorphic relationship: Client has many attachments
     */
    public function attachments()
    {
        return $this->morphMany(Attachment::class, "attachable");
    }

    /**
     * Scope: Filter by client type
     */
    public function scopeByType($query, $type)
    {
        return $query->where("client_type", $type);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where("status", $status);
    }

    /**
     * Scope: Filter by KYC status
     */
    public function scopeByKycStatus($query, $status)
    {
        return $query->where("kyc_status", $status);
    }

    /**
     * Scope: Filter by risk rating
     */
    public function scopeByRiskRating($query, $rating)
    {
        return $query->where("risk_rating", $rating);
    }

    /**
     * Scope: Active clients only
     */
    public function scopeActive($query)
    {
        return $query->where("status", "active");
    }

    /**
     * Scope: KYC approved clients only
     */
    public function scopeKycApproved($query)
    {
        return $query->where("kyc_status", "approved");
    }

    /**
     * Scope: Individual clients only
     */
    public function scopeIndividuals($query)
    {
        return $query->where("client_type", "individual");
    }

    /**
     * Scope: Business clients only
     */
    public function scopeBusinesses($query)
    {
        return $query->where("client_type", "business");
    }

    /**
     * Scope: Low risk clients
     */
    public function scopeLowRisk($query)
    {
        return $query->where("risk_rating", "low");
    }

    /**
     * Scope: High risk clients
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn("risk_rating", ["high"]);
    }

    /**
     * Scope: Search by name, email, or phone
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where("first_name", "like", "%" . $term . "%")
                ->orWhere("last_name", "like", "%" . $term . "%")
                ->orWhere("business_name", "like", "%" . $term . "%")
                ->orWhere("client_code", "like", "%" . $term . "%")
                ->orWhere("phone_primary", "like", "%" . $term . "%")
                ->orWhere("id_number", "like", "%" . $term . "%");
        });
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        if ($this->client_type === "business") {
            return $this->business_name ?: "Unnamed Business";
        }

        $name = trim(
            implode(
                " ",
                array_filter([
                    $this->title,
                    $this->first_name,
                    $this->middle_name,
                    $this->last_name,
                ]),
            ),
        );

        return $name ?: "Unnamed Client";
    }

    /**
     * Get display name (name + client code)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name . " (" . $this->client_code . ")";
    }

    /**
     * Get primary address
     */
    public function getPrimaryAddress()
    {
        return $this->addresses()->where("is_default", true)->first();
    }

    /**
     * Get primary payment method
     */
    public function getPrimaryPaymentMethod()
    {
        return $this->paymentMethods()->where("is_default", true)->first();
    }

    /**
     * Check if client is active
     */
    public function isActive(): bool
    {
        return $this->status === "active";
    }

    /**
     * Check if client is blacklisted
     */
    public function isBlacklisted(): bool
    {
        return $this->status === "blacklisted";
    }

    /**
     * Check if client is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === "suspended";
    }

    /**
     * Check if KYC is approved
     */
    public function isKycApproved(): bool
    {
        return $this->kyc_status === "approved";
    }

    /**
     * Check if KYC is pending
     */
    public function isKycPending(): bool
    {
        return $this->kyc_status === "pending";
    }

    /**
     * Check if KYC is incomplete
     */
    public function isKycIncomplete(): bool
    {
        return $this->kyc_status === "incomplete";
    }

    /**
     * Check if client is individual
     */
    public function isIndividual(): bool
    {
        return $this->client_type === "individual";
    }

    /**
     * Check if client is business
     */
    public function isBusiness(): bool
    {
        return $this->client_type === "business";
    }

    /**
     * Get age (for individual clients)
     */
    public function getAge(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }

        return $this->date_of_birth->diffInYears(now());
    }

    /**
     * Get active lease agreements count
     */
    public function getActiveLeaseAgreementsCount(): int
    {
        return $this->leaseAgreements()->where("status", "active")->count();
    }

    /**
     * Get total lease applications count
     */
    public function getTotalLeaseApplicationsCount(): int
    {
        return $this->leaseApplications()->count();
    }

    /**
     * Get total payments made
     */
    public function getTotalPaymentsMade(): float
    {
        return $this->payments()->where("status", "completed")->sum("amount");
    }

    /**
     * Get outstanding payments
     */
    public function getOutstandingPayments(): float
    {
        return $this->payments()
            ->whereIn("status", ["pending", "overdue"])
            ->sum("amount");
    }

    /**
     * Get credit score badge color
     */
    public function getCreditScoreBadgeColorAttribute(): string
    {
        if (!$this->credit_score) {
            return "gray";
        }

        return match (true) {
            $this->credit_score >= 750 => "green",
            $this->credit_score >= 650 => "blue",
            $this->credit_score >= 550 => "yellow",
            default => "red",
        };
    }

    /**
     * Get risk rating badge color
     */
    public function getRiskRatingBadgeColorAttribute(): string
    {
        return match ($this->risk_rating) {
            "low" => "green",
            "medium" => "yellow",
            "high" => "red",
            default => "gray",
        };
    }

    /**
     * Get KYC status badge color
     */
    public function getKycStatusBadgeColorAttribute(): string
    {
        return match ($this->kyc_status) {
            "approved" => "green",
            "pending" => "yellow",
            "rejected" => "red",
            "incomplete" => "gray",
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
            "inactive" => "gray",
            "blacklisted" => "red",
            "suspended" => "orange",
            default => "gray",
        };
    }

    /**
     * Generate unique client code
     */
    public static function generateClientCode(): string
    {
        do {
            $code = "CL" . str_pad(mt_rand(1, 999999), 6, "0", STR_PAD_LEFT);
        } while (static::where("client_code", $code)->exists());

        return $code;
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($client) {
            if (empty($client->client_code)) {
                $client->client_code = static::generateClientCode();
            }
            if (is_null($client->status)) {
                $client->status = "active";
            }
            if (is_null($client->kyc_status)) {
                $client->kyc_status = "incomplete";
            }
            if (is_null($client->preferred_payment_method)) {
                $client->preferred_payment_method = "mobile_money";
            }
            if (is_null($client->registration_source)) {
                $client->registration_source = "online";
            }
        });
    }
}
