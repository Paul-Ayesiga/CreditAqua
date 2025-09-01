<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient',
        'message',
        'status',
        'provider',
        'external_id',
        'cost',
        'error_message',
        'sent_at',
        'delivered_at',
    ];

    protected $casts = [
        'cost' => 'decimal:4',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
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

    public function getDeliveryTime(): ?int
    {
        if (!$this->sent_at || !$this->delivered_at) {
            return null;
        }

        return $this->delivered_at->diffInSeconds($this->sent_at);
    }

    public function getFormattedCost(): string
    {
        if (!$this->cost) {
            return 'N/A';
        }

        return 'UGX ' . number_format($this->cost, 2);
    }

    public function getMessageLength(): int
    {
        return strlen($this->message);
    }

    public function getSmsCount(): int
    {
        // SMS is typically 160 characters, but can vary by provider
        return max(1, ceil($this->getMessageLength() / 160));
    }
}
