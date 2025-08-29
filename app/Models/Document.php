<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Document extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "documentable_type",
        "documentable_id",
        "document_type",
        "title",
        "description",
        "file_path",
        "file_name",
        "file_size",
        "mime_type",
        "document_number",
        "issue_date",
        "expiry_date",
        "status",
        "uploaded_by",
        "verified_by",
        "verified_at",
        "remarks",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            "issue_date" => "date",
            "expiry_date" => "date",
            "verified_at" => "datetime",
            "file_size" => "integer",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                "document_type",
                "title",
                "status",
                "uploaded_by",
                "verified_by",
                "verified_at",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Polymorphic relationship: Document belongs to documentable
     */
    public function documentable()
    {
        return $this->morphTo();
    }

    /**
     * Relationship: Document uploaded by user
     */
    public function uploadedBy()
    {
        return $this->belongsTo(User::class, "uploaded_by");
    }

    /**
     * Relationship: Document verified by user
     */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, "verified_by");
    }

    /**
     * Scope: Filter by document type
     */
    public function scopeByType($query, $type)
    {
        return $query->where("document_type", $type);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where("status", $status);
    }

    /**
     * Scope: Approved documents only
     */
    public function scopeApproved($query)
    {
        return $query->where("status", "approved");
    }

    /**
     * Scope: Pending documents only
     */
    public function scopePending($query)
    {
        return $query->where("status", "pending");
    }

    /**
     * Scope: Expired documents
     */
    public function scopeExpired($query)
    {
        return $query
            ->where("expiry_date", "<", now())
            ->whereNotNull("expiry_date");
    }

    /**
     * Scope: Expiring soon (within 30 days)
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query
            ->whereBetween("expiry_date", [now(), now()->addDays($days)])
            ->whereNotNull("expiry_date");
    }

    /**
     * Check if document is approved
     */
    public function isApproved(): bool
    {
        return $this->status === "approved";
    }

    /**
     * Check if document is pending
     */
    public function isPending(): bool
    {
        return $this->status === "pending";
    }

    /**
     * Check if document is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === "rejected";
    }

    /**
     * Check if document is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if document is expiring soon
     */
    public function isExpiringSoon($days = 30): bool
    {
        return $this->expiry_date &&
            $this->expiry_date->isBetween(now(), now()->addDays($days));
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ["B", "KB", "MB", "GB"];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . " " . $units[$i];
    }

    /**
     * Get full file path
     */
    public function getFullFilePathAttribute(): string
    {
        return storage_path("app/" . $this->file_path);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default status when creating
        static::creating(function ($document) {
            if (empty($document->status)) {
                $document->status = "pending";
            }
        });
    }
}
