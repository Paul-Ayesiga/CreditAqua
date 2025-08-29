<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Attachment extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "attachable_type",
        "attachable_id",
        "file_type",
        "title",
        "description",
        "file_path",
        "file_name",
        "original_name",
        "file_size",
        "mime_type",
        "uploaded_by",
        "is_public",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            "file_size" => "integer",
            "is_public" => "boolean",
        ];
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                "file_type",
                "title",
                "file_name",
                "is_public",
                "uploaded_by",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Polymorphic relationship: Attachment belongs to attachable
     */
    public function attachable()
    {
        return $this->morphTo();
    }

    /**
     * Relationship: Attachment uploaded by user
     */
    public function uploadedBy()
    {
        return $this->belongsTo(User::class, "uploaded_by");
    }

    /**
     * Scope: Filter by file type
     */
    public function scopeByType($query, $type)
    {
        return $query->where("file_type", $type);
    }

    /**
     * Scope: Public attachments only
     */
    public function scopePublic($query)
    {
        return $query->where("is_public", true);
    }

    /**
     * Scope: Private attachments only
     */
    public function scopePrivate($query)
    {
        return $query->where("is_public", false);
    }

    /**
     * Scope: Images only
     */
    public function scopeImages($query)
    {
        return $query->where("file_type", "image");
    }

    /**
     * Scope: Documents only
     */
    public function scopeDocuments($query)
    {
        return $query->where("file_type", "document");
    }

    /**
     * Scope: Videos only
     */
    public function scopeVideos($query)
    {
        return $query->where("file_type", "video");
    }

    /**
     * Scope: Audio only
     */
    public function scopeAudio($query)
    {
        return $query->where("file_type", "audio");
    }

    /**
     * Check if attachment is public
     */
    public function isPublic(): bool
    {
        return $this->is_public === true;
    }

    /**
     * Check if attachment is private
     */
    public function isPrivate(): bool
    {
        return $this->is_public === false;
    }

    /**
     * Check if attachment is an image
     */
    public function isImage(): bool
    {
        return $this->file_type === "image";
    }

    /**
     * Check if attachment is a document
     */
    public function isDocument(): bool
    {
        return $this->file_type === "document";
    }

    /**
     * Check if attachment is a video
     */
    public function isVideo(): bool
    {
        return $this->file_type === "video";
    }

    /**
     * Check if attachment is audio
     */
    public function isAudio(): bool
    {
        return $this->file_type === "audio";
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
     * Get file URL for public files
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->is_public) {
            return null;
        }

        return asset("storage/" . $this->file_path);
    }

    /**
     * Get file extension from mime type or filename
     */
    public function getFileExtensionAttribute(): string
    {
        // Try to get extension from original filename first
        if ($this->original_name) {
            $extension = pathinfo($this->original_name, PATHINFO_EXTENSION);
            if ($extension) {
                return strtolower($extension);
            }
        }

        // Fallback to mime type mapping
        $mimeToExtension = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/gif" => "gif",
            "image/webp" => "webp",
            "application/pdf" => "pdf",
            "application/msword" => "doc",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document" =>
                "docx",
            "application/vnd.ms-excel" => "xls",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" =>
                "xlsx",
            "text/plain" => "txt",
            "text/csv" => "csv",
            "video/mp4" => "mp4",
            "video/avi" => "avi",
            "audio/mpeg" => "mp3",
            "audio/wav" => "wav",
        ];

        return $mimeToExtension[$this->mime_type] ?? "unknown";
    }

    /**
     * Get icon class for file type
     */
    public function getIconClassAttribute(): string
    {
        return match ($this->file_type) {
            "image" => "fas fa-image",
            "document" => "fas fa-file-alt",
            "video" => "fas fa-video",
            "audio" => "fas fa-music",
            default => "fas fa-file",
        };
    }

    /**
     * Get color class for file type
     */
    public function getColorClassAttribute(): string
    {
        return match ($this->file_type) {
            "image" => "text-green-500",
            "document" => "text-blue-500",
            "video" => "text-purple-500",
            "audio" => "text-red-500",
            default => "text-gray-500",
        };
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($attachment) {
            if (is_null($attachment->is_public)) {
                $attachment->is_public = false;
            }

            // Auto-detect file type from mime type if not set
            if (!$attachment->file_type && $attachment->mime_type) {
                if (str_starts_with($attachment->mime_type, "image/")) {
                    $attachment->file_type = "image";
                } elseif (str_starts_with($attachment->mime_type, "video/")) {
                    $attachment->file_type = "video";
                } elseif (str_starts_with($attachment->mime_type, "audio/")) {
                    $attachment->file_type = "audio";
                } elseif (
                    in_array($attachment->mime_type, [
                        "application/pdf",
                        "application/msword",
                        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                        "application/vnd.ms-excel",
                        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                        "text/plain",
                        "text/csv",
                    ])
                ) {
                    $attachment->file_type = "document";
                } else {
                    $attachment->file_type = "other";
                }
            }
        });

        // Delete physical file when attachment is deleted
        static::deleting(function ($attachment) {
            if (
                $attachment->file_path &&
                file_exists($attachment->full_file_path)
            ) {
                unlink($attachment->full_file_path);
            }
        });
    }
}
