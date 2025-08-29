<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ClientReference extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "client_id",
        "reference_type",
        "name",
        "relationship",
        "phone",
        "email",
        "address",
        "occupation",
        "years_known",
        "is_contacted",
        "contact_notes",
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
            "years_known" => "integer",
            "is_contacted" => "boolean",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                "reference_type",
                "name",
                "verification_status",
                "is_contacted",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Reference belongs to client
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope: Filter by reference type
     */
    public function scopeByType($query, $type)
    {
        return $query->where("reference_type", $type);
    }

    /**
     * Scope: Filter by verification status
     */
    public function scopeByVerificationStatus($query, $status)
    {
        return $query->where("verification_status", $status);
    }

    /**
     * Scope: Contacted references only
     */
    public function scopeContacted($query)
    {
        return $query->where("is_contacted", true);
    }

    /**
     * Scope: Not contacted references
     */
    public function scopeNotContacted($query)
    {
        return $query->where("is_contacted", false);
    }

    /**
     * Scope: Verified references only
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
     * Check if reference is contacted
     */
    public function isContacted(): bool
    {
        return $this->is_contacted === true;
    }

    /**
     * Check if reference is verified
     */
    public function isVerified(): bool
    {
        return $this->verification_status === "verified";
    }

    /**
     * Check if reference verification failed
     */
    public function isVerificationFailed(): bool
    {
        return $this->verification_status === "failed";
    }

    /**
     * Check if verification is pending
     */
    public function isPendingVerification(): bool
    {
        return $this->verification_status === "pending";
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
            "failed" => "red",
            "pending" => "yellow",
            default => "gray",
        };
    }

    /**
     * Get reference type badge color
     */
    public function getTypeBadgeColorAttribute(): string
    {
        return match ($this->reference_type) {
            "personal" => "blue",
            "professional" => "green",
            "family" => "purple",
            "neighbor" => "orange",
            "business" => "teal",
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
        static::creating(function ($reference) {
            if (is_null($reference->is_contacted)) {
                $reference->is_contacted = false;
            }
            if (is_null($reference->verification_status)) {
                $reference->verification_status = "pending";
            }
        });
    }
}
