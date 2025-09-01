<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient',
        'subject',
        'body',
        'status',
        'provider',
        'external_id',
        'error_message',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', 'delivered');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeBounced(Builder $query): Builder
    {
        return $query->where('status', 'bounced');
    }

    public function scopeOpened(Builder $query): Builder
    {
        return $query->whereNotNull('opened_at');
    }

    public function scopeClicked(Builder $query): Builder
    {
        return $query->whereNotNull('clicked_at');
    }

    public function scopeByProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    public function scopeByRecipient(Builder $query, string $recipient): Builder
    {
        return $query->where('recipient', $recipient);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    // Methods
    public function markAsSent(): bool
    {
        return $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsDelivered(): bool
    {
        return $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function markAsBounced(string $errorMessage = null): bool
    {
        return $this->update([
            'status' => 'bounced',
            'error_message' => $errorMessage,
        ]);
    }

    public function markAsOpened(): bool
    {
        if ($this->opened_at) {
            return false;
        }

        return $this->update(['opened_at' => now()]);
    }

    public function markAsClicked(): bool
    {
        if ($this->clicked_at) {
            return false;
        }

        return $this->update(['clicked_at' => now()]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isBounced(): bool
    {
        return $this->status === 'bounced';
    }

    public function isOpened(): bool
    {
        return !is_null($this->opened_at);
    }

    public function isClicked(): bool
    {
        return !is_null($this->clicked_at);
    }

    public function getDeliveryTime(): ?int
    {
        if (!$this->sent_at || !$this->delivered_at) {
            return null;
        }

        return $this->delivered_at->diffInSeconds($this->sent_at);
    }

    public function getOpenTime(): ?int
    {
        if (!$this->delivered_at || !$this->opened_at) {
            return null;
        }

        return $this->opened_at->diffInSeconds($this->delivered_at);
    }

    public function getClickTime(): ?int
    {
        if (!$this->opened_at || !$this->clicked_at) {
            return null;
        }

        return $this->clicked_at->diffInSeconds($this->opened_at);
    }

    public function getOpenRate(): float
    {
        if (!$this->isDelivered()) {
            return 0.0;
        }

        return $this->isOpened() ? 100.0 : 0.0;
    }

    public function getClickRate(): float
    {
        if (!$this->isOpened()) {
            return 0.0;
        }

        return $this->isClicked() ? 100.0 : 0.0;
    }

    public function getEngagementScore(): float
    {
        $score = 0;

        if ($this->isDelivered()) $score += 25;
        if ($this->isOpened()) $score += 50;
        if ($this->isClicked()) $score += 25;

        return $score;
    }
}
