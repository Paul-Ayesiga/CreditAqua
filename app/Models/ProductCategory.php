<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProductCategory extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "name",
        "slug",
        "description",
        "parent_id",
        "image_path",
        "is_active",
        "sort_order",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            "is_active" => "boolean",
            "sort_order" => "integer",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["name", "slug", "parent_id", "is_active", "sort_order"])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Category belongs to parent category
     */
    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, "parent_id");
    }

    /**
     * Relationship: Category has many child categories
     */
    public function children()
    {
        return $this->hasMany(ProductCategory::class, "parent_id");
    }

    /**
     * Relationship: Get all descendants recursively
     */
    public function descendants()
    {
        return $this->children()->with("descendants");
    }

    /**
     * Relationship: Category has many products
     */
    public function products()
    {
        return $this->hasMany(Product::class, "category_id");
    }

    /**
     * Scope: Active categories only
     */
    public function scopeActive($query)
    {
        return $query->where("is_active", true);
    }

    /**
     * Scope: Root categories only (no parent)
     */
    public function scopeRootCategories($query)
    {
        return $query->whereNull("parent_id");
    }

    /**
     * Scope: Child categories only (has parent)
     */
    public function scopeChildCategories($query)
    {
        return $query->whereNotNull("parent_id");
    }

    /**
     * Scope: Order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy("sort_order")->orderBy("name");
    }

    /**
     * Check if category is active
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Check if category is a root category
     */
    public function isRootCategory(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if category has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get depth level of category in hierarchy
     */
    public function getDepthLevel(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    /**
     * Get all ancestors (parents up the tree)
     */
    public function getAncestors()
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->prepend($parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    /**
     * Get category breadcrumb trail
     */
    public function getBreadcrumbAttribute(): string
    {
        $ancestors = $this->getAncestors();
        $ancestors->push($this);

        return $ancestors->pluck("name")->implode(" > ");
    }

    /**
     * Get total products count including children
     */
    public function getTotalProductsCount(): int
    {
        $count = $this->products()->count();

        foreach ($this->children as $child) {
            $count += $child->getTotalProductsCount();
        }

        return $count;
    }

    /**
     * Get active products count including children
     */
    public function getActiveProductsCount(): int
    {
        $count = $this->products()->where("status", "active")->count();

        foreach ($this->children as $child) {
            $count += $child->getActiveProductsCount();
        }

        return $count;
    }

    /**
     * Get category tree structure
     */
    public static function getTree()
    {
        return static::with("children.children.children")
            ->whereNull("parent_id")
            ->ordered()
            ->get();
    }

    /**
     * Get category options for select dropdown
     */
    public static function getSelectOptions()
    {
        $categories = static::with("parent")->active()->ordered()->get();

        return $categories->mapWithKeys(function ($category) {
            $prefix = str_repeat("-- ", $category->getDepthLevel());
            return [$category->id => $prefix . $category->name];
        });
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($category) {
            if (is_null($category->is_active)) {
                $category->is_active = true;
            }
            if (is_null($category->sort_order)) {
                $category->sort_order = 0;
            }
        });

        // When deleting a category, handle children appropriately
        static::deleting(function ($category) {
            // Move children to parent's parent or make them root categories
            $category
                ->children()
                ->update(["parent_id" => $category->parent_id]);
        });
    }
}
