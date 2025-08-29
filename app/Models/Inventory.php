<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Inventory extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "product_id",
        "location",
        "quantity_available",
        "quantity_reserved",
        "quantity_leased",
        "reorder_level",
        "last_restocked_at",
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
            "quantity_available" => "integer",
            "quantity_reserved" => "integer",
            "quantity_leased" => "integer",
            "reorder_level" => "integer",
            "last_restocked_at" => "datetime",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                "quantity_available",
                "quantity_reserved",
                "quantity_leased",
                "location",
                "reorder_level",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Inventory belongs to product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope: Low stock items (below reorder level)
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw("quantity_available <= reorder_level");
    }

    /**
     * Scope: Out of stock items
     */
    public function scopeOutOfStock($query)
    {
        return $query->where("quantity_available", 0);
    }

    /**
     * Scope: Available items (has stock)
     */
    public function scopeAvailable($query)
    {
        return $query->where("quantity_available", ">", 0);
    }

    /**
     * Scope: Filter by location
     */
    public function scopeByLocation($query, $location)
    {
        return $query->where("location", $location);
    }

    /**
     * Get total quantity (all types)
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->quantity_available +
            $this->quantity_reserved +
            $this->quantity_leased;
    }

    /**
     * Get available quantity for lease
     */
    public function getAvailableForLeaseAttribute(): int
    {
        return max(0, $this->quantity_available - $this->quantity_reserved);
    }

    /**
     * Check if stock is low
     */
    public function isLowStock(): bool
    {
        return $this->quantity_available <= $this->reorder_level;
    }

    /**
     * Check if out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity_available === 0;
    }

    /**
     * Check if item is available for lease
     */
    public function isAvailableForLease(int $quantity = 1): bool
    {
        return $this->available_for_lease >= $quantity;
    }

    /**
     * Reserve quantity for a lease application
     */
    public function reserveQuantity(int $quantity): bool
    {
        if (!$this->isAvailableForLease($quantity)) {
            return false;
        }

        $this->quantity_available -= $quantity;
        $this->quantity_reserved += $quantity;

        return $this->save();
    }

    /**
     * Release reserved quantity (cancel reservation)
     */
    public function releaseReservedQuantity(int $quantity): bool
    {
        if ($this->quantity_reserved < $quantity) {
            return false;
        }

        $this->quantity_reserved -= $quantity;
        $this->quantity_available += $quantity;

        return $this->save();
    }

    /**
     * Move reserved quantity to leased (when lease is approved)
     */
    public function moveReservedToLeased(int $quantity): bool
    {
        if ($this->quantity_reserved < $quantity) {
            return false;
        }

        $this->quantity_reserved -= $quantity;
        $this->quantity_leased += $quantity;

        return $this->save();
    }

    /**
     * Return leased quantity to available (when lease ends)
     */
    public function returnLeasedQuantity(int $quantity): bool
    {
        if ($this->quantity_leased < $quantity) {
            return false;
        }

        $this->quantity_leased -= $quantity;
        $this->quantity_available += $quantity;

        return $this->save();
    }

    /**
     * Add stock (restock)
     */
    public function addStock(int $quantity): bool
    {
        $this->quantity_available += $quantity;
        $this->last_restocked_at = now();

        return $this->save();
    }

    /**
     * Remove stock (damage, loss, etc.)
     */
    public function removeStock(int $quantity): bool
    {
        if ($this->quantity_available < $quantity) {
            return false;
        }

        $this->quantity_available -= $quantity;

        return $this->save();
    }

    /**
     * Get stock status color for UI
     */
    public function getStockStatusColorAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return "red";
        } elseif ($this->isLowStock()) {
            return "yellow";
        } else {
            return "green";
        }
    }

    /**
     * Get stock status text
     */
    public function getStockStatusTextAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return "Out of Stock";
        } elseif ($this->isLowStock()) {
            return "Low Stock";
        } else {
            return "In Stock";
        }
    }

    /**
     * Get stock level percentage (based on reorder level as minimum)
     */
    public function getStockLevelPercentageAttribute(): float
    {
        if ($this->reorder_level <= 0) {
            return 100.0;
        }

        $maxLevel = $this->reorder_level * 3; // Assume 3x reorder level as "full"
        return min(100, ($this->quantity_available / $maxLevel) * 100);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($inventory) {
            if (is_null($inventory->quantity_available)) {
                $inventory->quantity_available = 0;
            }
            if (is_null($inventory->quantity_reserved)) {
                $inventory->quantity_reserved = 0;
            }
            if (is_null($inventory->quantity_leased)) {
                $inventory->quantity_leased = 0;
            }
            if (is_null($inventory->reorder_level)) {
                $inventory->reorder_level = 0;
            }
        });
    }
}
