<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubdomainDomain extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'pteroca_subdomain_domains';

    /**
     * Indicates that the model does not have an updated_at column.
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'domain',
        'cloudflare_zone_id',
        'is_default',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get all subdomains under this domain.
     */
    public function subdomains(): HasMany
    {
        return $this->hasMany(Subdomain::class, 'domain_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope to filter only active domains.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter only default domain(s).
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    // =========================================================================
    // Methods
    // =========================================================================

    /**
     * Check if this domain has any active subdomains.
     */
    public function hasActiveSubdomains(): bool
    {
        return $this->subdomains()->where('status', '!=', 'error')->exists();
    }
}
