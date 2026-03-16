<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Subdomain extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'pteroca_subdomains';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'server_id',
        'user_id',
        'subdomain',
        'domain_id',
        'cloudflare_a_record_id',
        'cloudflare_srv_record_id',
        'status',
        'error_message',
        'last_changed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'server_id' => 'integer',
        'user_id' => 'integer',
        'domain_id' => 'integer',
        'last_changed_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the domain this subdomain belongs to.
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(SubdomainDomain::class, 'domain_id');
    }

    /**
     * Get the user who owns this subdomain.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get activity logs for this subdomain.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(SubdomainLog::class, 'subdomain_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope to filter only active subdomains.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter subdomains by server.
     */
    public function scopeForServer(Builder $query, int $serverId): Builder
    {
        return $query->where('server_id', $serverId);
    }

    /**
     * Scope to filter subdomains by user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Get the full address (subdomain.domain).
     */
    public function getFullAddressAttribute(): string
    {
        return $this->subdomain . '.' . $this->domain->domain;
    }

    // =========================================================================
    // Methods
    // =========================================================================

    /**
     * Check if the subdomain is currently on cooldown.
     */
    public function isOnCooldown(int $hours): bool
    {
        if ($hours <= 0 || $this->last_changed_at === null) {
            return false;
        }

        return $this->last_changed_at->addHours($hours)->isFuture();
    }

    /**
     * Get the remaining cooldown time as a human-readable string.
     */
    public function getCooldownRemaining(int $hours): ?string
    {
        if (!$this->isOnCooldown($hours)) {
            return null;
        }

        $endsAt = $this->last_changed_at->addHours($hours);
        $diffInMinutes = (int) now()->diffInMinutes($endsAt, false);

        if ($diffInMinutes >= 60) {
            $diffInHours = (int) ceil($diffInMinutes / 60);
            return __('subdomains::subdomains.cooldown_hours', ['hours' => $diffInHours]);
        }

        return __('subdomains::subdomains.cooldown_minutes', ['minutes' => $diffInMinutes]);
    }

    // =========================================================================
    // Static Helpers
    // =========================================================================

    /**
     * Get a setting value from the pteroca_subdomain_settings table.
     */
    public static function getSetting(string $key, mixed $default = null): mixed
    {
        $row = \Illuminate\Support\Facades\DB::table('pteroca_subdomain_settings')
            ->where('setting_key', $key)
            ->first();

        if ($row === null) {
            return $default;
        }

        // Decrypt the API token when reading
        if ($key === 'cloudflare_api_token' && $row->setting_value !== null) {
            try {
                return decrypt($row->setting_value);
            } catch (\Exception $e) {
                return $default;
            }
        }

        return $row->setting_value;
    }

    /**
     * Set a setting value in the pteroca_subdomain_settings table.
     */
    public static function setSetting(string $key, mixed $value): void
    {
        // Encrypt the API token when storing
        if ($key === 'cloudflare_api_token' && $value !== null) {
            $value = encrypt($value);
        }

        \Illuminate\Support\Facades\DB::table('pteroca_subdomain_settings')
            ->updateOrInsert(
                ['setting_key' => $key],
                ['setting_value' => $value, 'updated_at' => now()]
            );
    }
}
