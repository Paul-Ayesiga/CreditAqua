<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PaymentMethod extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'method_type',
        'provider',
        'account_number',
        'account_name',
        'is_default',
        'is_verified',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'method_type',
                'provider',
                'is_default',
                'is_verified',
                'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Payment method belongs to client
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope: Filter by method type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('method_type', $type);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Active payment methods only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Default payment methods only
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: Verified payment methods only
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope: Bank account methods
     */
    public function scopeBankAccounts($query)
    {
        return $query->where('method_type', 'bank_account');
    }

    /**
     * Scope: Mobile money methods
     */
    public function scopeMobileMoney($query)
    {
        return $query->where('method_type', 'mobile_money');
    }

    /**
     * Scope: Card methods
     */
    public function scopeCards($query)
    {
        return $query->where('method_type', 'card');
    }

    /**
     * Check if payment method is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if payment method is verified
     */
    public function isVerified(): bool
    {
        return $this->is_verified === true;
    }

    /**
     * Check if payment method is default
     */
    public function isDefault(): bool
    {
        return $this->is_default === true;
    }

    /**
     * Check if payment method is bank account
     */
    public function isBankAccount(): bool
    {
        return $this->method_type === 'bank_account';
    }

    /**
     * Check if payment method is mobile money
     */
    public function isMobileMoney(): bool
    {
        return $this->method_type === 'mobile_money';
    }

    /**
     * Check if payment method is card
     */
    public function isCard(): bool
    {
        return $this->method_type === 'card';
    }

    /**
     * Get formatted account number (masked for security)
     */
    public function getFormattedAccountNumberAttribute(): string
    {
        if (strlen($this->account_number) <= 4) {
            return $this->account_number;
        }

        return '****' . substr($this->account_number, -4);
    }

    /**
     * Get display name for payment method
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->provider . ' - ' . $this->getFormattedAccountNumberAttribute();
    }

    /**
     * Get method type badge color
     */
    public function getMethodTypeBadgeColorAttribute(): string
    {
        return match ($this->method_type) {
            'bank_account' => 'blue',
            'mobile_money' => 'green',
            'card' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'green',
            'inactive' => 'gray',
            'blocked' => 'red',
            default => 'gray',
        };
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($paymentMethod) {
            if (is_null($paymentMethod->is_default)) {
                $paymentMethod->is_default = false;
            }
            if (is_null($paymentMethod->is_verified)) {
                $paymentMethod->is_verified = false;
            }
            if (is_null($paymentMethod->status)) {
                $paymentMethod->status = 'active';
            }
        });

        // Ensure only one default payment method per client
        static::saving(function ($paymentMethod) {
            if ($paymentMethod->is_default) {
                static::where('client_id', $paymentMethod->client_id)
                    ->where('id', '!=', $paymentMethod->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
