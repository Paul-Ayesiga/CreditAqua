<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Address extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "addressable_type",
        "addressable_id",
        "address_type",
        "label",
        "address_line_1",
        "address_line_2",
        "city",
        "state",
        "postal_code",
        "country",
        "latitude",
        "longitude",
        "is_default",
        "is_verified",
        "verified_at",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            "latitude" => "decimal:8",
            "longitude" => "decimal:8",
            "is_default" => "boolean",
            "is_verified" => "boolean",
            "verified_at" => "datetime",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                "address_type",
                "address_line_1",
                "city",
                "state",
                "country",
                "is_default",
                "is_verified",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Polymorphic relationship: Address belongs to addressable
     */
    public function addressable()
    {
        return $this->morphTo();
    }

    /**
     * Scope: Filter by address type
     */
    public function scopeByType($query, $type)
    {
        return $query->where("address_type", $type);
    }

    /**
     * Scope: Default addresses only
     */
    public function scopeDefault($query)
    {
        return $query->where("is_default", true);
    }

    /**
     * Scope: Verified addresses only
     */
    public function scopeVerified($query)
    {
        return $query->where("is_verified", true);
    }

    /**
     * Scope: Filter by city
     */
    public function scopeByCity($query, $city)
    {
        return $query->where("city", $city);
    }

    /**
     * Scope: Filter by state
     */
    public function scopeByState($query, $state)
    {
        return $query->where("state", $state);
    }

    /**
     * Scope: Filter by country
     */
    public function scopeByCountry($query, $country)
    {
        return $query->where("country", $country);
    }

    /**
     * Get the full address as a single string
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(", ", $parts);
    }

    /**
     * Get formatted address for display
     */
    public function getFormattedAddressAttribute(): string
    {
        $formatted = $this->address_line_1;

        if ($this->address_line_2) {
            $formatted .= "\n" . $this->address_line_2;
        }

        $formatted .= "\n" . $this->city;

        if ($this->state) {
            $formatted .= ", " . $this->state;
        }

        if ($this->postal_code) {
            $formatted .= " " . $this->postal_code;
        }

        $formatted .= "\n" . $this->country;

        return $formatted;
    }

    /**
     * Check if address has coordinates
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Get Google Maps URL
     */
    public function getGoogleMapsUrlAttribute(): string
    {
        if ($this->hasCoordinates()) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }

        return "https://www.google.com/maps?q=" .
            urlencode($this->full_address);
    }

    /**
     * Calculate distance to another address (in kilometers)
     */
    public function distanceTo(Address $address): ?float
    {
        if (!$this->hasCoordinates() || !$address->hasCoordinates()) {
            return null;
        }

        $earthRadius = 6371; // Earth's radius in kilometers

        $lat1 = deg2rad($this->latitude);
        $lon1 = deg2rad($this->longitude);
        $lat2 = deg2rad($address->latitude);
        $lon2 = deg2rad($address->longitude);

        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        $a =
            sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1) * cos($lat2) * sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($address) {
            if (is_null($address->country)) {
                $address->country = "Uganda";
            }
            if (is_null($address->address_type)) {
                $address->address_type = "primary";
            }
            if (is_null($address->is_default)) {
                $address->is_default = false;
            }
            if (is_null($address->is_verified)) {
                $address->is_verified = false;
            }
        });

        // Ensure only one default address per addressable entity and type
        static::saving(function ($address) {
            if ($address->is_default) {
                static::where("addressable_type", $address->addressable_type)
                    ->where("addressable_id", $address->addressable_id)
                    ->where("address_type", $address->address_type)
                    ->where("id", "!=", $address->id)
                    ->update(["is_default" => false]);
            }
        });
    }
}
