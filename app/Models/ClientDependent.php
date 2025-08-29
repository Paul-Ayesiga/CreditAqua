<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ClientDependent extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "client_id",
        "name",
        "relationship",
        "age",
        "is_employed",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            "age" => "integer",
            "is_employed" => "boolean",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["name", "relationship", "age", "is_employed"])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Dependent belongs to client
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope: Filter by relationship
     */
    public function scopeByRelationship($query, $relationship)
    {
        return $query->where("relationship", $relationship);
    }

    /**
     * Scope: Employed dependents only
     */
    public function scopeEmployed($query)
    {
        return $query->where("is_employed", true);
    }

    /**
     * Scope: Unemployed dependents only
     */
    public function scopeUnemployed($query)
    {
        return $query->where("is_employed", false);
    }

    /**
     * Scope: Minor dependents (under 18)
     */
    public function scopeMinors($query)
    {
        return $query->where("age", "<", 18);
    }

    /**
     * Scope: Adult dependents (18 and over)
     */
    public function scopeAdults($query)
    {
        return $query->where("age", ">=", 18);
    }

    /**
     * Check if dependent is employed
     */
    public function isEmployed(): bool
    {
        return $this->is_employed === true;
    }

    /**
     * Check if dependent is a minor
     */
    public function isMinor(): bool
    {
        return $this->age < 18;
    }

    /**
     * Check if dependent is an adult
     */
    public function isAdult(): bool
    {
        return $this->age >= 18;
    }

    /**
     * Get age category
     */
    public function getAgeCategoryAttribute(): string
    {
        return match (true) {
            $this->age < 5 => "Toddler",
            $this->age < 13 => "Child",
            $this->age < 18 => "Teenager",
            $this->age < 65 => "Adult",
            default => "Senior",
        };
    }

    /**
     * Get employment status text
     */
    public function getEmploymentStatusAttribute(): string
    {
        return $this->is_employed ? "Employed" : "Unemployed";
    }

    /**
     * Get relationship badge color
     */
    public function getRelationshipBadgeColorAttribute(): string
    {
        return match ($this->relationship) {
            "child" => "blue",
            "spouse" => "green",
            "parent" => "purple",
            "sibling" => "orange",
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
        static::creating(function ($dependent) {
            if (is_null($dependent->is_employed)) {
                $dependent->is_employed = false;
            }
        });
    }
}
