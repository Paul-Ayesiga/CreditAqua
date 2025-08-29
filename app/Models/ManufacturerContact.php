<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ManufacturerContact extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "manufacturer_id",
        "contact_type",
        "name",
        "title",
        "email",
        "phone",
        "is_primary",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            "is_primary" => "boolean",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["contact_type", "name", "email", "phone", "is_primary"])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Contact belongs to manufacturer
     */
    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * Scope: Filter by contact type
     */
    public function scopeByType($query, $type)
    {
        return $query->where("contact_type", $type);
    }

    /**
     * Scope: Primary contacts only
     */
    public function scopePrimary($query)
    {
        return $query->where("is_primary", true);
    }

    /**
     * Scope: Secondary contacts only
     */
    public function scopeSecondary($query)
    {
        return $query->where("is_primary", false);
    }

    /**
     * Check if contact is primary
     */
    public function isPrimary(): bool
    {
        return $this->is_primary === true;
    }

    /**
     * Get full contact information
     */
    public function getFullContactInfoAttribute(): string
    {
        $info = $this->name;

        if ($this->title) {
            $info .= " (" . $this->title . ")";
        }

        if ($this->email) {
            $info .= " - " . $this->email;
        }

        if ($this->phone) {
            $info .= " - " . $this->phone;
        }

        return $info;
    }

    /**
     * Get contact type badge color
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->contact_type) {
            "primary" => "blue",
            "secondary" => "gray",
            "finance" => "green",
            "technical" => "purple",
            "sales" => "orange",
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
        static::creating(function ($contact) {
            if (is_null($contact->is_primary)) {
                $contact->is_primary = false;
            }
        });

        // Ensure only one primary contact per manufacturer and type
        static::saving(function ($contact) {
            if ($contact->is_primary) {
                static::where("manufacturer_id", $contact->manufacturer_id)
                    ->where("contact_type", $contact->contact_type)
                    ->where("id", "!=", $contact->id)
                    ->update(["is_primary" => false]);
            }
        });
    }
}
