<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Message extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "sender_id",
        "recipient_id",
        "subject",
        "body",
        "message_type",
        "priority",
        "status",
        "read_at",
        "parent_id",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            "read_at" => "datetime",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                "subject",
                "message_type",
                "priority",
                "status",
                "read_at",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: Message belongs to sender (user)
     */
    public function sender()
    {
        return $this->belongsTo(User::class, "sender_id");
    }

    /**
     * Relationship: Message belongs to recipient (user)
     */
    public function recipient()
    {
        return $this->belongsTo(User::class, "recipient_id");
    }

    /**
     * Relationship: Message belongs to parent message (for replies)
     */
    public function parent()
    {
        return $this->belongsTo(Message::class, "parent_id");
    }

    /**
     * Relationship: Message has many replies
     */
    public function replies()
    {
        return $this->hasMany(Message::class, "parent_id");
    }

    /**
     * Scope: Filter by message type
     */
    public function scopeByType($query, $type)
    {
        return $query->where("message_type", $type);
    }

    /**
     * Scope: Filter by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where("priority", $priority);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where("status", $status);
    }

    /**
     * Scope: Read messages only
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull("read_at");
    }

    /**
     * Scope: Unread messages only
     */
    public function scopeUnread($query)
    {
        return $query->whereNull("read_at");
    }

    /**
     * Scope: Sent messages
     */
    public function scopeSent($query)
    {
        return $query->where("status", "sent");
    }

    /**
     * Scope: Draft messages
     */
    public function scopeDraft($query)
    {
        return $query->where("status", "draft");
    }

    /**
     * Scope: Failed messages
     */
    public function scopeFailed($query)
    {
        return $query->where("status", "failed");
    }

    /**
     * Scope: High priority messages
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn("priority", ["high", "urgent"]);
    }

    /**
     * Scope: System messages
     */
    public function scopeSystem($query)
    {
        return $query->where("message_type", "system");
    }

    /**
     * Scope: User messages
     */
    public function scopeUser($query)
    {
        return $query->where("message_type", "user");
    }

    /**
     * Scope: Automated messages
     */
    public function scopeAutomated($query)
    {
        return $query->where("message_type", "automated");
    }

    /**
     * Scope: Root messages only (no parent)
     */
    public function scopeRootMessages($query)
    {
        return $query->whereNull("parent_id");
    }

    /**
     * Scope: Reply messages only (has parent)
     */
    public function scopeReplyMessages($query)
    {
        return $query->whereNotNull("parent_id");
    }

    /**
     * Scope: Recent messages (within last 7 days)
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where("created_at", ">=", now()->subDays($days));
    }

    /**
     * Check if message is read
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if message is unread
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Check if message is sent
     */
    public function isSent(): bool
    {
        return $this->status === "sent";
    }

    /**
     * Check if message is draft
     */
    public function isDraft(): bool
    {
        return $this->status === "draft";
    }

    /**
     * Check if message is failed
     */
    public function isFailed(): bool
    {
        return $this->status === "failed";
    }

    /**
     * Check if message is a reply
     */
    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Check if message has replies
     */
    public function hasReplies(): bool
    {
        return $this->replies()->exists();
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): bool
    {
        if ($this->isRead()) {
            return true;
        }

        $this->read_at = now();
        return $this->save();
    }

    /**
     * Mark message as unread
     */
    public function markAsUnread(): bool
    {
        if ($this->isUnread()) {
            return true;
        }

        $this->read_at = null;
        return $this->save();
    }

    /**
     * Send message
     */
    public function send(): bool
    {
        if ($this->status !== "draft") {
            return false;
        }

        $this->status = "sent";
        return $this->save();
    }

    /**
     * Get priority badge color
     */
    public function getPriorityBadgeColorAttribute(): string
    {
        return match ($this->priority) {
            "urgent" => "red",
            "high" => "orange",
            "normal" => "blue",
            "low" => "gray",
            default => "gray",
        };
    }

    /**
     * Get message type badge color
     */
    public function getTypeBadgeColorAttribute(): string
    {
        return match ($this->message_type) {
            "system" => "red",
            "user" => "blue",
            "automated" => "green",
            default => "gray",
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            "sent" => "green",
            "delivered" => "blue",
            "read" => "purple",
            "failed" => "red",
            "draft" => "gray",
            default => "gray",
        };
    }

    /**
     * Get message thread (parent and all replies)
     */
    public function getThread()
    {
        $rootMessage = $this->parent ?: $this;

        return collect([$rootMessage])
            ->merge($rootMessage->replies()->with("sender", "recipient")->get())
            ->sortBy("created_at");
    }

    /**
     * Get total replies count
     */
    public function getTotalRepliesCount(): int
    {
        return $this->replies()->count();
    }

    /**
     * Get shortened body for preview
     */
    public function getBodyPreviewAttribute(): string
    {
        return str_limit($this->body, 100);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($message) {
            if (is_null($message->message_type)) {
                $message->message_type = "user";
            }
            if (is_null($message->priority)) {
                $message->priority = "normal";
            }
            if (is_null($message->status)) {
                $message->status = "draft";
            }
        });
    }
}
