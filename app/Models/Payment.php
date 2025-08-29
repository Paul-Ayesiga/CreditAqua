<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Payment extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "payment_reference",
        "lease_agreement_id",
        "payment_schedule_id",
        "client_id",
        "payment_type",
        "amount",
        "currency",
        "payment_method",
        "payment_channel",
        "transaction_reference",
        "external_reference",
        "payment_date",
        "due_date",
        "status",
        "processing_fee",
        "net_amount",
        "confirmation_code",
        "receipt_number",
        "notes",
        "processed_by",
        "processed_at",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            "amount" => "decimal:2",
            "processing_fee" => "decimal:2",
            "net_amount" => "decimal:2",
            "payment_date" => "date",
            "due_date" => "date",
            "processed_at" => "datetime",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                "payment_reference",
                "payment_type",
                "amount",
                "payment_method",
                "status",
                "processed_by",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Payment belongs to lease agreement
     */
    public function leaseAgreement()
    {
        return $this->belongsTo(LeaseAgreement::class);
    }

    /**
     * Relationship: Payment belongs to payment schedule
     */
    public function paymentSchedule()
    {
        return $this->belongsTo(PaymentSchedule::class);
    }

    /**
     * Relationship: Payment belongs to client
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relationship: Payment processed by user
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, "processed_by");
    }

    /**
     * Relationship: Payment has many commissions
     */
    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * Scope: Filter by payment type
     */
    public function scopeByType($query, $type)
    {
        return $query->where("payment_type", $type);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where("status", $status);
    }

    /**
     * Scope: Filter by payment method
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where("payment_method", $method);
    }

    /**
     * Scope: Completed payments only
     */
    public function scopeCompleted($query)
    {
        return $query->where("status", "completed");
    }

    /**
     * Scope: Pending payments only
     */
    public function scopePending($query)
    {
        return $query->where("status", "pending");
    }

    /**
     * Scope: Failed payments only
     */
    public function scopeFailed($query)
    {
        return $query->where("status", "failed");
    }

    /**
     * Scope: Installment payments only
     */
    public function scopeInstallments($query)
    {
        return $query->where("payment_type", "installment");
    }

    /**
     * Scope: Down payments only
     */
    public function scopeDownPayments($query)
    {
        return $query->where("payment_type", "down_payment");
    }

    /**
     * Scope: Mobile money payments
     */
    public function scopeMobileMoney($query)
    {
        return $query->where("payment_method", "mobile_money");
    }

    /**
     * Scope: Bank transfer payments
     */
    public function scopeBankTransfers($query)
    {
        return $query->where("payment_method", "bank_transfer");
    }

    /**
     * Scope: Cash payments
     */
    public function scopeCash($query)
    {
        return $query->where("payment_method", "cash");
    }

    /**
     * Scope: Search by reference or confirmation code
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where("payment_reference", "like", "%" . $term . "%")
                ->orWhere("transaction_reference", "like", "%" . $term . "%")
                ->orWhere("external_reference", "like", "%" . $term . "%")
                ->orWhere("confirmation_code", "like", "%" . $term . "%")
                ->orWhere("receipt_number", "like", "%" . $term . "%");
        });
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === "completed";
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === "pending";
    }

    /**
     * Check if payment is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === "processing";
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === "failed";
    }

    /**
     * Check if payment was cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === "cancelled";
    }

    /**
     * Check if payment was refunded
     */
    public function isRefunded(): bool
    {
        return $this->status === "refunded";
    }

    /**
     * Check if payment is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date &&
            $this->due_date->isPast() &&
            !$this->isCompleted();
    }

    /**
     * Get payment type badge color
     */
    public function getPaymentTypeBadgeColorAttribute(): string
    {
        return match ($this->payment_type) {
            "down_payment" => "blue",
            "installment" => "green",
            "late_fee" => "red",
            "security_deposit" => "purple",
            "early_termination" => "orange",
            default => "gray",
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            "completed" => "green",
            "processing" => "blue",
            "pending" => "yellow",
            "failed" => "red",
            "cancelled" => "gray",
            "refunded" => "purple",
            default => "gray",
        };
    }

    /**
     * Get payment method badge color
     */
    public function getPaymentMethodBadgeColorAttribute(): string
    {
        return match ($this->payment_method) {
            "mobile_money" => "green",
            "bank_transfer" => "blue",
            "card" => "purple",
            "cash" => "yellow",
            "cheque" => "gray",
            default => "gray",
        };
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . " " . $this->currency;
    }

    /**
     * Get days until due (negative if overdue)
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Generate unique payment reference
     */
    public static function generatePaymentReference(): string
    {
        do {
            $reference =
                "PAY" . str_pad(mt_rand(1, 9999999), 7, "0", STR_PAD_LEFT);
        } while (static::where("payment_reference", $reference)->exists());

        return $reference;
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($payment) {
            if (empty($payment->payment_reference)) {
                $payment->payment_reference = static::generatePaymentReference();
            }
            if (empty($payment->currency)) {
                $payment->currency = "UGX";
            }
            if (is_null($payment->status)) {
                $payment->status = "pending";
            }
            if (is_null($payment->processing_fee)) {
                $payment->processing_fee = 0.0;
            }
            // Calculate net amount if not provided
            if (is_null($payment->net_amount) && $payment->amount) {
                $payment->net_amount =
                    $payment->amount - ($payment->processing_fee ?? 0);
            }
        });

        // Update net amount when amount or processing fee changes
        static::saving(function ($payment) {
            if ($payment->isDirty(["amount", "processing_fee"])) {
                $payment->net_amount =
                    $payment->amount - ($payment->processing_fee ?? 0);
            }
        });
    }
}
