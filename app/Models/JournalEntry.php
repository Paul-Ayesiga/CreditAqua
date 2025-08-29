<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class JournalEntry extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'entry_number',
        'transaction_date',
        'reference_type',
        'reference_id',
        'description',
        'total_debit',
        'total_credit',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'entry_number',
                'transaction_date',
                'description',
                'total_debit',
                'total_credit',
                'status',
                'posted_by',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Journal entry created by user
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: Journal entry posted by user
     */
    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Relationship: Journal entry has many lines
     */
    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Relationship: Polymorphic relationship to referenced model
     */
    public function referenceable()
    {
        return $this->morphTo('reference');
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Draft entries only
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope: Posted entries only
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    /**
     * Scope: Reversed entries only
     */
    public function scopeReversed($query)
    {
        return $query->where('status', 'reversed');
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter by reference type
     */
    public function scopeByReferenceType($query, $type)
    {
        return $query->where('reference_type', $type);
    }

    /**
     * Scope: Search journal entries
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('entry_number', 'like', '%' . $term . '%')
                ->orWhere('description', 'like', '%' . $term . '%');
        });
    }

    /**
     * Check if journal entry is draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if journal entry is posted
     */
    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    /**
     * Check if journal entry is reversed
     */
    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    /**
     * Check if entry is balanced (debits = credits)
     */
    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01; // Allow for rounding differences
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'yellow',
            'posted' => 'green',
            'reversed' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get formatted total debit
     */
    public function getFormattedTotalDebitAttribute(): string
    {
        return number_format($this->total_debit, 2);
    }

    /**
     * Get formatted total credit
     */
    public function getFormattedTotalCreditAttribute(): string
    {
        return number_format($this->total_credit, 2);
    }

    /**
     * Get difference between debits and credits
     */
    public function getDifferenceAttribute(): float
    {
        return $this->total_debit - $this->total_credit;
    }

    /**
     * Calculate totals from lines
     */
    public function calculateTotals(): void
    {
        $this->total_debit = $this->lines()->sum('debit_amount');
        $this->total_credit = $this->lines()->sum('credit_amount');
    }

    /**
     * Post the journal entry
     */
    public function post(User $user): bool
    {
        if (!$this->isDraft()) {
            return false;
        }

        if (!$this->isBalanced()) {
            throw new \Exception('Journal entry must be balanced before posting.');
        }

        if ($this->lines()->count() === 0) {
            throw new \Exception('Journal entry must have at least one line before posting.');
        }

        $this->status = 'posted';
        $this->posted_by = $user->id;
        $this->posted_at = now();
        $this->save();

        // Update account balances
        $this->updateAccountBalances();

        return true;
    }

    /**
     * Reverse the journal entry
     */
    public function reverse(User $user, string $reason = null): JournalEntry
    {
        if (!$this->isPosted()) {
            throw new \Exception('Only posted journal entries can be reversed.');
        }

        // Create reversal entry
        $reversalEntry = static::create([
            'entry_number' => static::generateEntryNumber(),
            'transaction_date' => now()->toDateString(),
            'description' => 'REVERSAL: ' . $this->description . ($reason ? ' - ' . $reason : ''),
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        // Create reversal lines (flip debits and credits)
        foreach ($this->lines as $line) {
            $reversalEntry->lines()->create([
                'account_id' => $line->account_id,
                'description' => 'REVERSAL: ' . $line->description,
                'debit_amount' => $line->credit_amount,
                'credit_amount' => $line->debit_amount,
            ]);
        }

        $reversalEntry->calculateTotals();
        $reversalEntry->save();
        $reversalEntry->post($user);

        // Mark original entry as reversed
        $this->status = 'reversed';
        $this->save();

        return $reversalEntry;
    }

    /**
     * Update account balances for all lines
     */
    protected function updateAccountBalances(): void
    {
        $accountIds = $this->lines()->pluck('account_id')->unique();
        
        foreach ($accountIds as $accountId) {
            $account = Account::find($accountId);
            if ($account) {
                $account->updateBalanceFromJournalEntries();
            }
        }
    }

    /**
     * Generate unique entry number
     */
    public static function generateEntryNumber(): string
    {
        $year = now()->year;
        $month = str_pad(now()->month, 2, '0', STR_PAD_LEFT);
        
        $lastEntry = static::where('entry_number', 'like', "JE{$year}{$month}%")
            ->orderBy('entry_number', 'desc')
            ->first();

        if (!$lastEntry) {
            $sequence = '001';
        } else {
            $lastSequence = intval(substr($lastEntry->entry_number, -3));
            $sequence = str_pad($lastSequence + 1, 3, '0', STR_PAD_LEFT);
        }

        return "JE{$year}{$month}{$sequence}";
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($journalEntry) {
            if (empty($journalEntry->entry_number)) {
                $journalEntry->entry_number = static::generateEntryNumber();
            }
            if (is_null($journalEntry->status)) {
                $journalEntry->status = 'draft';
            }
            if (is_null($journalEntry->transaction_date)) {
                $journalEntry->transaction_date = now()->toDateString();
            }
            if (is_null($journalEntry->total_debit)) {
                $journalEntry->total_debit = 0.00;
            }
            if (is_null($journalEntry->total_credit)) {
                $journalEntry->total_credit = 0.00;
            }
        });

        // Prevent deletion of posted or reversed entries
        static::deleting(function ($journalEntry) {
            if ($journalEntry->isPosted() || $journalEntry->isReversed()) {
                throw new \Exception('Cannot delete posted or reversed journal entries.');
            }
        });
    }
}
