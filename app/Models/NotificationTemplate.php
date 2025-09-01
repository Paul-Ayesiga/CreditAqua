<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'event_trigger',
        'subject',
        'content',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByEvent(Builder $query, string $event): Builder
    {
        return $query->where('event_trigger', $event);
    }

    // Methods
    public function renderContent(array $data = []): string
    {
        $content = $this->content;
        
        foreach ($data as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
        }
        
        return $content;
    }

    public function renderSubject(array $data = []): ?string
    {
        if (!$this->subject) {
            return null;
        }

        $subject = $this->subject;
        
        foreach ($data as $key => $value) {
            $subject = str_replace("{{$key}}", $value, $subject);
        }
        
        return $subject;
    }

    public function getAvailableVariables(): array
    {
        return $this->variables ?? [];
    }

    public function isEmailTemplate(): bool
    {
        return $this->type === 'email';
    }

    public function isSmsTemplate(): bool
    {
        return $this->type === 'sms';
    }

    public function isPushTemplate(): bool
    {
        return $this->type === 'push';
    }

    public function isSystemTemplate(): bool
    {
        return $this->type === 'system';
    }
}
