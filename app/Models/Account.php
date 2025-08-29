<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Account extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "account_code",
        "account_name",
        "account_type",
        "parent_id",
        "balance",
        "is_active",
        "description",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            "balance" => "decimal:2",
            "is_active" => "boolean",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                "account_code",
                "account_name",
                "account_type",
                "balance",
                "is_active",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Account has parent account
     */
    public function parent()
    {
        return $this->belongsTo(Account::class, "parent_id");
    }

    /**
     * Relationship: Account has many child accounts
     */
    public function children()
    {
        return $this->hasMany(Account::class, "parent_id");
    }

    /**
     * Relationship: Account has many journal entry lines
     */
    public function journalEntryLines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Scope: Filter by account type
     */
    public function scopeByType($query, $type)
    {
        return $query->where("account_type", $type);
    }

    /**
     * Scope: Active accounts only
     */
    public function scopeActive($query)
    {
        return $query->where("is_active", true);
    }

    /**
     * Scope: Parent accounts only
     */
    public function scopeParents($query)
    {
        return $query->whereNull("parent_id");
    }

    /**
     * Scope: Child accounts only
     */
    public function scopeChildren($query)
    {
        return $query->whereNotNull("parent_id");
    }

    /**
     * Check if account is active
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Check if account is a parent account
     */
    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if account has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get account type badge color
     */
    public function getAccountTypeBadgeColorAttribute(): string
    {
        return match ($this->account_type) {
            "asset" => "green",
            "liability" => "red",
            "equity" => "blue",
            "income" => "purple",
            "expense" => "orange",
            default => "gray",
        };
    }

    /**
     * Get formatted balance with sign
     */
    public function getFormattedBalanceAttribute(): string
    {
        $sign = $this->balance >= 0 ? "" : "-";
        return $sign . "UGX " . number_format(abs($this->balance), 2);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($account) {
            if (is_null($account->balance)) {
                $account->balance = 0.00;
            }
            if (is_null($account->is_active)) {
                $account->is_active = true;
            }
        });
    }
}
