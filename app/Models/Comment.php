<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Comment extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "commentable_type",
        "commentable_id",
        "user_id",
        "parent_id",
        "comment_type",
        "title",
        "content",
        "priority",
        "is_internal",
        "is_pinned",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            "is_internal" => "boolean",
            "is_pinned" => "boolean",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                "comment_type",
                "title",
                "content",
                "priority",
                "is_internal",
                "is_pinned",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Polymorphic relationship: Comment belongs to commentable
     */
    public function commentable()
    {
        return $this->morphTo();
    }

    /**
     * Relationship: Comment belongs to user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Comment belongs to parent comment
     */
    public function parent()
    {
        return $this->belongsTo(Comment::class, "parent_id");
    }

    /**
     * Relationship: Comment has many child comments
     */
    public function children()
    {
        return $this->hasMany(Comment::class, "parent_id");
    }

    /**
     * Relationship: Get all descendants recursively
     */
    public function descendants()
    {
        return $this->children()->with("descendants");
    }

    /**
     * Scope: Filter by comment type
     */
    public function scopeByType($query, $type)
    {
        return $query->where("comment_type", $type);
    }

    /**
     * Scope: Filter by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where("priority", $priority);
    }

    /**
     * Scope: Internal comments only
     */
    public function scopeInternal($query)
    {
        return $query->where("is_internal", true);
    }

    /**
     * Scope: Public comments only
     */
    public function scopePublic($query)
    {
        return $query->where("is_internal", false);
    }

    /**
     * Scope: Pinned comments only
     */
    public function scopePinned($query)
    {
        return $query->where("is_pinned", true);
    }

    /**
     * Scope: Root comments only (no parent)
     */
    public function scopeRootComments($query)
    {
        return $query->whereNull("parent_id");
    }

    /**
     * Scope: Child comments only (has parent)
     */
    public function scopeChildComments($query)
    {
        return $query->whereNotNull("parent_id");
    }

    /**
     * Scope: High priority comments
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn("priority", ["high", "urgent"]);
    }

    /**
     * Scope: Recent comments (within last 7 days)
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where("created_at", ">=", now()->subDays($days));
    }

    /**
     * Check if comment is internal
     */
    public function isInternal(): bool
    {
        return $this->is_internal === true;
    }

    /**
     * Check if comment is public
     */
    public function isPublic(): bool
    {
        return $this->is_internal === false;
    }

    /**
     * Check if comment is pinned
     */
    public function isPinned(): bool
    {
        return $this->is_pinned === true;
    }

    /**
     * Check if comment is a root comment
     */
    public function isRootComment(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if comment has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get depth level of comment in hierarchy
     */
    public function getDepthLevel(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    /**
     * Get total number of replies (children and descendants)
     */
    public function getTotalRepliesCount(): int
    {
        $count = $this->children()->count();

        foreach ($this->children as $child) {
            $count += $child->getTotalRepliesCount();
        }

        return $count;
    }

    /**
     * Get priority badge color
     */
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            "urgent" => "red",
            "high" => "orange",
            "medium" => "yellow",
            "low" => "green",
            default => "gray",
        };
    }

    /**
     * Get comment type badge color
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->comment_type) {
            "note" => "blue",
            "status_change" => "green",
            "reminder" => "yellow",
            "follow_up" => "purple",
            "issue" => "red",
            "resolution" => "emerald",
            default => "gray",
        };
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($comment) {
            if (is_null($comment->priority)) {
                $comment->priority = "medium";
            }
            if (is_null($comment->is_internal)) {
                $comment->is_internal = true;
            }
            if (is_null($comment->is_pinned)) {
                $comment->is_pinned = false;
            }
        });

        // When deleting a comment, handle children appropriately
        static::deleting(function ($comment) {
            // Option 1: Delete all children (cascade delete)
            $comment->children()->delete();

            // Option 2: Move children to parent's parent (uncomment if preferred)
            // $comment->children()->update(['parent_id' => $comment->parent_id]);
        });
    }
}
