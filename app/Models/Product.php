<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Product extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "manufacturer_id",
        "category_id",
        "name",
        "slug",
        "sku",
        "description",
        "specifications",
        "unit_price",
        "lease_price",
        "currency",
        "minimum_lease_duration",
        "maximum_lease_duration",
        "warranty_period",
        "maintenance_required",
        "installation_required",
        "weight_kg",
        "dimensions_cm",
        "color",
        "material",
        "capacity_liters",
        "images",
        "status",
        "is_featured",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            "specifications" => "array",
            "unit_price" => "decimal:2",
            "lease_price" => "decimal:2",
            "minimum_lease_duration" => "integer",
            "maximum_lease_duration" => "integer",
            "warranty_period" => "integer",
            "maintenance_required" => "boolean",
            "installation_required" => "boolean",
            "weight_kg" => "decimal:2",
            "capacity_liters" => "integer",
            "images" => "array",
            "is_featured" => "boolean",
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
                "sku",
                "unit_price",
                "lease_price",
                "status",
                "is_featured",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Product belongs to manufacturer
     */
    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * Relationship: Product belongs to category
     */
    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * Relationship: Product has one inventory record
     */
    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    /**
     * Relationship: Product has many lease applications
     */
    public function leaseApplications()
    {
        return $this->hasMany(LeaseApplication::class);
    }

    /**
     * Relationship: Product has many lease agreements
     */
    public function leaseAgreements()
    {
        return $this->hasMany(LeaseAgreement::class);
    }

    /**
     * Polymorphic relationship: Product has many addresses
     */
    public function addresses()
    {
        return $this->morphMany(Address::class, "addressable");
    }

    /**
     * Polymorphic relationship: Product has many documents
     */
    public function documents()
    {
        return $this->morphMany(Document::class, "documentable");
    }

    /**
     * Polymorphic relationship: Product has many comments
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, "commentable");
    }

    /**
     * Polymorphic relationship: Product has many attachments
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
     * Scope: Active products only
     */
    public function scopeActive($query)
    {
        return $query->where("status", "active");
    }

    /**
     * Scope: Featured products only
     */
    public function scopeFeatured($query)
    {
        return $query->where("is_featured", true);
    }

    /**
     * Scope: Filter by manufacturer
     */
    public function scopeByManufacturer($query, $manufacturerId)
    {
        return $query->where("manufacturer_id", $manufacturerId);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where("category_id", $categoryId);
    }

    /**
     * Scope: Filter by price range
     */
    public function scopeByPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween("lease_price", [$minPrice, $maxPrice]);
    }

    /**
     * Scope: Available for lease (has inventory)
     */
    public function scopeAvailable($query)
    {
        return $query->whereHas("inventory", function ($q) {
            $q->where("quantity_available", ">", 0);
        });
    }

    /**
     * Scope: Search by name or description
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where("name", "like", "%" . $term . "%")
                ->orWhere("description", "like", "%" . $term . "%")
                ->orWhere("sku", "like", "%" . $term . "%");
        });
    }

    /**
     * Check if product is active
     */
    public function isActive(): bool
    {
        return $this->status === "active";
    }

    /**
     * Check if product is featured
     */
    public function isFeatured(): bool
    {
        return $this->is_featured === true;
    }

    /**
     * Check if product is available for lease
     */
    public function isAvailable(): bool
    {
        return $this->inventory && $this->inventory->quantity_available > 0;
    }

    /**
     * Check if maintenance is required
     */
    public function requiresMaintenance(): bool
    {
        return $this->maintenance_required === true;
    }

    /**
     * Check if installation is required
     */
    public function requiresInstallation(): bool
    {
        return $this->installation_required === true;
    }

    /**
     * Get available quantity
     */
    public function getAvailableQuantity(): int
    {
        return $this->inventory ? $this->inventory->quantity_available : 0;
    }

    /**
     * Get reserved quantity
     */
    public function getReservedQuantity(): int
    {
        return $this->inventory ? $this->inventory->quantity_reserved : 0;
    }

    /**
     * Get leased quantity
     */
    public function getLeasedQuantity(): int
    {
        return $this->inventory ? $this->inventory->quantity_leased : 0;
    }

    /**
     * Get primary image
     */
    public function getPrimaryImageAttribute(): ?string
    {
        if (is_array($this->images) && count($this->images) > 0) {
            return $this->images[0];
        }
        return null;
    }

    /**
     * Get formatted unit price
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return $this->currency . " " . number_format($this->unit_price, 2);
    }

    /**
     * Get formatted lease price
     */
    public function getFormattedLeasePriceAttribute(): string
    {
        return $this->currency . " " . number_format($this->lease_price, 2);
    }

    /**
     * Get lease duration range text
     */
    public function getLeaseDurationRangeAttribute(): string
    {
        if ($this->minimum_lease_duration === $this->maximum_lease_duration) {
            return $this->minimum_lease_duration . " months";
        }
        return $this->minimum_lease_duration .
            "-" .
            $this->maximum_lease_duration .
            " months";
    }

    /**
     * Get total lease cost for given duration
     */
    public function getTotalLeaseCost(int $months): float
    {
        return $this->lease_price * $months;
    }

    /**
     * Get discount percentage compared to unit price
     */
    public function getLeaseDiscountPercentage(int $months): float
    {
        $totalLeaseCost = $this->getTotalLeaseCost($months);
        if ($this->unit_price > 0) {
            return (($this->unit_price - $totalLeaseCost) / $this->unit_price) *
                100;
        }
        return 0;
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            "active" => "green",
            "draft" => "yellow",
            "inactive" => "gray",
            "discontinued" => "red",
            default => "gray",
        };
    }

    /**
     * Get specifications as formatted string
     */
    public function getFormattedSpecificationsAttribute(): string
    {
        if (!is_array($this->specifications)) {
            return "";
        }

        $formatted = [];
        foreach ($this->specifications as $key => $value) {
            $formatted[] = ucfirst($key) . ": " . $value;
        }

        return implode(", ", $formatted);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($product) {
            if (is_null($product->status)) {
                $product->status = "draft";
            }
            if (is_null($product->currency)) {
                $product->currency = "UGX";
            }
            if (is_null($product->minimum_lease_duration)) {
                $product->minimum_lease_duration = 12;
            }
            if (is_null($product->maximum_lease_duration)) {
                $product->maximum_lease_duration = 60;
            }
            if (is_null($product->warranty_period)) {
                $product->warranty_period = 12;
            }
            if (is_null($product->maintenance_required)) {
                $product->maintenance_required = false;
            }
            if (is_null($product->installation_required)) {
                $product->installation_required = true;
            }
            if (is_null($product->is_featured)) {
                $product->is_featured = false;
            }
        });
    }
}
