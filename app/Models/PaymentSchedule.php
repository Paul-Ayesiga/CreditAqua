<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PaymentSchedule extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "lease_agreement_id",
        "installment_number",
        "due_date",
        "principal_amount",
        "interest_amount",
        "total_amount",
        "status",
        "paid_amount",
        "paid_date",
        "late_fee",
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
            "installment_number" => "integer",
            "due_date" => "date",
            "principal_amount" => "decimal:2",
            "interest_amount" => "decimal:2",
            "total_amount" => "decimal:2",
            "paid_amount" => "decimal:2",
            "paid_date" => "date",
            "late_fee" => "decimal:2",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["status", "paid_amount", "paid_date", "late_fee"])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Schedule belongs to lease agreement
     */
    public function leaseAgreement()
    {
        return $this->belongsTo(LeaseAgreement::class);
    }

    /**
     * Relationship: Schedule has many payments
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Relationship: Schedule has many late payment notices
     */
    public function latePaymentNotices()
    {
        return $this->hasMany(LatePaymentNotice::class);
    }

    /**
     * Polymorphic relationship: Schedule has many comments
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, "commentable");
    }

    /**
     * Polymorphic relationship: Schedule has many attachments
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
     * Scope: Pending payments only
     */
    public function scopePending($query)
    {
        return $query->where("status", "pending");
    }

    /**
     * Scope: Paid payments only
     */
    public function scopePaid($query)
    {
        return $query->where("status", "paid");
    }

    /**
     * Scope: Partial payments only
     */
    public function scopePartial($query)
    {
        return $query->where("status", "partial");
    }

    /**
     * Scope: Overdue payments only
     */
    public function scopeOverdue($query)
    {
        return $query->where("status", "overdue");
    }

    /**
     * Scope: Waived payments only
     */
    public function scopeWaived($query)
    {
        return $query->where("status", "waived");
    }

    /**
     * Scope: Due today
     */
    public function scopeDueToday($query)
    {
        return $query->where("due_date", now()->toDateString());
    }

    /**
     * Scope: Due within specified days
     */
    public function scopeDueWithin($query, $days)
    {
        return $query->whereBetween("due_date", [
            now()->toDateString(),
            now()->addDays($days)->toDateString(),
        ]);
    }

    /**
     * Scope: Past due date
     */
    public function scopePastDue($query)
    {
        return $query
            ->where("due_date", "<", now()->toDateString())
            ->whereIn("status", ["pending", "partial"]);
    }

    /**
     * Scope: Current month payments
     */
    public function scopeCurrentMonth($query)
    {
        return $query
            ->whereMonth("due_date", now()->month)
            ->whereYear("due_date", now()->year);
    }

    /**
     * Scope: By lease agreement
     */
    public function scopeByLeaseAgreement($query, $agreementId)
    {
        return $query->where("lease_agreement_id", $agreementId);
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === "pending";
    }

    /**
     * Check if payment is paid
     */
    public function isPaid(): bool
    {
        return $this->status === "paid";
    }

    /**
     * Check if payment is partial
     */
    public function isPartial(): bool
    {
        return $this->status === "partial";
    }

    /**
     * Check if payment is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === "overdue";
    }

    /**
     * Check if payment is waived
     */
    public function isWaived(): bool
    {
        return $this->status === "waived";
    }

    /**
     * Check if payment is past due date
     */
    public function isPastDue(): bool
    {
        return $this->due_date->isPast() && !$this->isPaid();
    }

    /**
     * Check if payment is due today
     */
    public function isDueToday(): bool
    {
        return $this->due_date->isToday();
    }

    /**
     * Check if payment is due within specified days
     */
    public function isDueWithin(int $days): bool
    {
        return $this->due_date->isBetween(now(), now()->addDays($days));
    }

    /**
     * Get days until due (negative if overdue)
     */
    public function getDaysUntilDue(): int
    {
        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Get days overdue (0 if not overdue)
     */
    public function getDaysOverdue(): int
    {
        if (!$this->isPastDue()) {
            return 0;
        }

        return $this->due_date->diffInDays(now());
    }

    /**
     * Get remaining amount to be paid
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    /**
     * Get payment completion percentage
     */
    public function getPaymentPercentageAttribute(): float
    {
        if ($this->total_amount <= 0) {
            return 0;
        }

        return ($this->paid_amount / $this->total_amount) * 100;
    }

    /**
     * Get total amount with late fee
     */
    public function getTotalAmountWithLateFeeAttribute(): float
    {
        return $this->total_amount + $this->late_fee;
    }

    /**
     * Apply late fee
     */
    public function applyLateFee(float $amount): bool
    {
        $this->late_fee += $amount;

        return $this->save();
    }

    /**
     * Record payment
     */
    public function recordPayment(float $amount, $paymentDate = null): bool
    {
        $paymentDate = $paymentDate ?: now()->toDateString();

        $this->paid_amount += $amount;
        $this->paid_date = $paymentDate;

        // Update status based on payment amount
        if ($this->paid_amount >= $this->total_amount) {
            $this->status = "paid";
        } elseif ($this->paid_amount > 0) {
            $this->status = "partial";
        }

        return $this->save();
    }

    /**
     * Mark as overdue
     */
    public function markAsOverdue(): bool
    {
        if ($this->isPaid()) {
            return false;
        }

        $this->status = "overdue";

        return $this->save();
    }

    /**
     * Waive payment
     */
    public function waive(): bool
    {
        $this->status = "waived";
        $this->paid_amount = $this->total_amount;
        $this->paid_date = now()->toDateString();

        return $this->save();
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            "pending" => "yellow",
            "paid" => "green",
            "partial" => "blue",
            "overdue" => "red",
            "waived" => "gray",
            default => "gray",
        };
    }

    /**
     * Get urgency color based on due date
     */
    public function getUrgencyColorAttribute(): string
    {
        if ($this->isPaid()) {
            return "green";
        }

        $daysUntilDue = $this->getDaysUntilDue();

        return match (true) {
            $daysUntilDue < 0 => "red", // Overdue
            $daysUntilDue <= 3 => "orange", // Due very soon
            $daysUntilDue <= 7 => "yellow", // Due soon
            default => "green", // Not urgent
        };
    }

    /**
     * Get formatted total amount
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        $currency = $this->leaseAgreement->product->currency ?? "UGX";
        return $currency . " " . number_format($this->total_amount, 2);
    }

    /**
     * Get formatted paid amount
     */
    public function getFormattedPaidAmountAttribute(): string
    {
        $currency = $this->leaseAgreement->product->currency ?? "UGX";
        return $currency . " " . number_format($this->paid_amount, 2);
    }

    /**
     * Get formatted remaining amount
     */
    public function getFormattedRemainingAmountAttribute(): string
    {
        $currency = $this->leaseAgreement->product->currency ?? "UGX";
        return $currency . " " . number_format($this->remaining_amount, 2);
    }

    /**
     * Calculate late fee based on agreement terms
     */
    public function calculateLateFee(): float
    {
        if (!$this->isPastDue() || $this->isPaid()) {
            return 0;
        }

        $agreement = $this->leaseAgreement;
        $daysOverdue = $this->getDaysOverdue();

        // Only apply late fee if past grace period
        if ($daysOverdue <= $agreement->grace_period_days) {
            return 0;
        }

        // Calculate percentage-based late fee
        $lateFeeAmount =
            ($this->total_amount * $agreement->late_fee_percentage) / 100;

        return $lateFeeAmount;
    }

    /**
     * Auto-update overdue status
     */
    public function updateOverdueStatus(): bool
    {
        if ($this->isPastDue() && $this->isPending()) {
            return $this->markAsOverdue();
        }

        return true;
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($schedule) {
            if (is_null($schedule->status)) {
                $schedule->status = "pending";
            }
            if (is_null($schedule->paid_amount)) {
                $schedule->paid_amount = 0.0;
            }
            if (is_null($schedule->interest_amount)) {
                $schedule->interest_amount = 0.0;
            }
            if (is_null($schedule->late_fee)) {
                $schedule->late_fee = 0.0;
            }
        });

        // Calculate total amount when saving
        static::saving(function ($schedule) {
            if ($schedule->principal_amount && $schedule->interest_amount) {
                $schedule->total_amount =
                    $schedule->principal_amount + $schedule->interest_amount;
            }
        });
    }
}
