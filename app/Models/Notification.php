<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    // Relationships
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForModel(Builder $query, Model $model): Builder
    {
        return $query->where('notifiable_type', get_class($model))
                    ->where('notifiable_id', $model->id);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Methods
    public function markAsRead(): bool
    {
        if ($this->read_at) {
            return false;
        }

        return $this->update(['read_at' => now()]);
    }

    public function markAsUnread(): bool
    {
        if (!$this->read_at) {
            return false;
        }

        return $this->update(['read_at' => null]);
    }

    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    public function getTitle(): string
    {
        return $this->data['title'] ?? 'Notification';
    }

    public function getMessage(): string
    {
        return $this->data['message'] ?? '';
    }

    public function getUrl(): ?string
    {
        return $this->data['url'] ?? null;
    }

    public function getIcon(): string
    {
        return $this->data['icon'] ?? 'bell';
    }

    public function getPriority(): string
    {
        return $this->data['priority'] ?? 'normal';
    }

    public function isHighPriority(): bool
    {
        return in_array($this->getPriority(), ['high', 'urgent']);
    }

    public function isEmailNotification(): bool
    {
        return $this->type === 'email';
    }

    public function isSmsNotification(): bool
    {
        return $this->type === 'sms';
    }

    public function isPushNotification(): bool
    {
        return $this->type === 'push';
    }

    public function isSystemNotification(): bool
    {
        return $this->type === 'system';
    }
}
