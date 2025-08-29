<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        "name",
        "email",
        "password",
        "current_team_id",
        "profile_photo_path",
        "user_type",
        "status",
        "last_login_at",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = ["password", "remember_token"];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "last_login_at" => "datetime",
            "password" => "hashed",
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(" ")
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode("");
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                "name",
                "email",
                "user_type",
                "status",
                "current_team_id",
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship: User belongs to a current team (another user)
     */
    public function currentTeam()
    {
        return $this->belongsTo(User::class, "current_team_id");
    }

    /**
     * Relationship: User has many team members
     */
    public function teamMembers()
    {
        return $this->hasMany(User::class, "current_team_id");
    }

    /**
     * Relationship: User can be a manufacturer
     */
    public function manufacturer()
    {
        return $this->hasOne(Manufacturer::class);
    }

    /**
     * Relationship: User can be a client
     */
    public function client()
    {
        return $this->hasOne(Client::class);
    }

    /**
     * Relationship: User has many sent messages
     */
    public function sentMessages()
    {
        return $this->hasMany(Message::class, "sender_id");
    }

    /**
     * Relationship: User has many received messages
     */
    public function receivedMessages()
    {
        return $this->hasMany(Message::class, "recipient_id");
    }

    /**
     * Relationship: User has many uploaded documents
     */
    public function uploadedDocuments()
    {
        return $this->hasMany(Document::class, "uploaded_by");
    }

    /**
     * Relationship: User has many verified documents
     */
    public function verifiedDocuments()
    {
        return $this->hasMany(Document::class, "verified_by");
    }

    /**
     * Relationship: User has many comments
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Relationship: User has many uploaded attachments
     */
    public function attachments()
    {
        return $this->hasMany(Attachment::class, "uploaded_by");
    }

    /**
     * Relationship: User has many processed payments
     */
    public function processedPayments()
    {
        return $this->hasMany(Payment::class, "processed_by");
    }

    /**
     * Relationship: User has many reviewed lease applications
     */
    public function reviewedApplications()
    {
        return $this->hasMany(LeaseApplication::class, "reviewed_by");
    }

    /**
     * Relationship: User has many created journal entries
     */
    public function createdJournalEntries()
    {
        return $this->hasMany(JournalEntry::class, "created_by");
    }

    /**
     * Relationship: User has many posted journal entries
     */
    public function postedJournalEntries()
    {
        return $this->hasMany(JournalEntry::class, "posted_by");
    }

    /**
     * Relationship: User has many created reports
     */
    public function createdReports()
    {
        return $this->hasMany(Report::class, "created_by");
    }

    /**
     * Relationship: User has many executed reports
     */
    public function executedReports()
    {
        return $this->hasMany(ReportExecution::class, "executed_by");
    }

    /**
     * Relationship: User has many analytics events
     */
    public function analyticsEvents()
    {
        return $this->hasMany(AnalyticsEvent::class);
    }

    /**
     * Relationship: User has many audit trails
     */
    public function auditTrails()
    {
        return $this->hasMany(AuditTrail::class);
    }

    /**
     * Relationship: User has many sent late payment notices
     */
    public function sentLatePaymentNotices()
    {
        return $this->hasMany(LatePaymentNotice::class, "sent_by");
    }

    /**
     * Relationship: User has many assessed credit assessments
     */
    public function creditAssessments()
    {
        return $this->hasMany(CreditAssessment::class, "assessed_by");
    }

    /**
     * Relationship: User has many created marketing campaigns
     */
    public function marketingCampaigns()
    {
        return $this->hasMany(MarketingCampaign::class, "created_by");
    }

    /**
     * Scope: Filter by user type
     */
    public function scopeByType($query, $type)
    {
        return $query->where("user_type", $type);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where("status", $status);
    }

    /**
     * Scope: Active users only
     */
    public function scopeActive($query)
    {
        return $query->where("status", "active");
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->user_type === "admin";
    }

    /**
     * Check if user is manufacturer
     */
    public function isManufacturer(): bool
    {
        return $this->user_type === "manufacturer";
    }

    /**
     * Check if user is client
     */
    public function isClient(): bool
    {
        return $this->user_type === "client";
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === "active";
    }

    /**
     * Get user's full profile based on type
     */
    public function getProfile()
    {
        return match ($this->user_type) {
            "manufacturer" => $this->manufacturer,
            "client" => $this->client,
            default => null,
        };
    }
}
