<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class JournalEntryLine extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "journal_entry_id",
        "account_id",
        "description",
        "debit_amount",
        "credit_amount",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            "debit_amount" => "decimal:2",
            "credit_amount" => "decimal:2",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                "account_id",
                "description",
                "debit_amount",
                "credit_amount",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Line belongs to journal entry
     */
    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Relationship: Line belongs to account
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Check if this is a debit entry
     */
    public function isDebit(): bool
    {
        return $this->debit_amount > 0;
    }

    /**
     * Check if this is a credit entry
     */
    public function isCredit(): bool
    {
        return $this->credit_amount > 0;
    }

    /**
     * Get the entry amount (debit or credit)
     */
    public function getAmountAttribute(): float
    {
        return $this->debit_amount > 0 ? $this->debit_amount : $this->credit_amount;
    }

    /**
     * Get the entry type (debit or credit)
     */
    public function getEntryTypeAttribute(): string
    {
        return $this->debit_amount > 0 ? 'debit' : 'credit';
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Update journal entry totals when line changes
        static::saved(function ($line) {
            $line->journalEntry->calculateTotals();
        });

        static::deleted(function ($line) {
            $line->journalEntry->calculateTotals();
        });
    }
}
