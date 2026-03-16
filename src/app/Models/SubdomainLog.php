<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubdomainLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'pteroca_subdomain_logs';

    /**
     * Indicates that the model does not have an updated_at column.
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'subdomain_id',
        'user_id',
        'action',
        'details',
        'ip_address',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'subdomain_id' => 'integer',
        'user_id' => 'integer',
        'details' => 'array',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the subdomain this log entry belongs to.
     */
    public function subdomain(): BelongsTo
    {
        return $this->belongsTo(Subdomain::class, 'subdomain_id');
    }

    /**
     * Get the user who performed this action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope to filter logs by action type.
     */
    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter logs by user.
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // =========================================================================
    // Static Helpers
    // =========================================================================

    /**
     * Create a new log entry.
     */
    public static function log(
        string $action,
        ?int $subdomainId = null,
        ?int $userId = null,
        ?array $details = null,
        ?string $ip = null
    ): self {
        return static::create([
            'action' => $action,
            'subdomain_id' => $subdomainId,
            'user_id' => $userId,
            'details' => $details,
            'ip_address' => $ip,
        ]);
    }

    /**
     * Get the translated action label.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'create' => __('subdomains::subdomains.log_action_create'),
            'update' => __('subdomains::subdomains.log_action_update'),
            'delete' => __('subdomains::subdomains.log_action_delete'),
            'suspend' => __('subdomains::subdomains.log_action_suspend'),
            'unsuspend' => __('subdomains::subdomains.log_action_unsuspend'),
            'error' => __('subdomains::subdomains.log_action_error'),
            default => ucfirst($this->action),
        };
    }

    /**
     * Get the CSS class for the action badge.
     */
    public function getActionBadgeClassAttribute(): string
    {
        return match ($this->action) {
            'create' => 'badge-success',
            'update' => 'badge-info',
            'delete' => 'badge-danger',
            'suspend' => 'badge-warning',
            'unsuspend' => 'badge-primary',
            'error' => 'badge-danger',
            default => 'badge-secondary',
        };
    }
}
