<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubdomainBlacklist extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'pteroca_subdomain_blacklist';

    /**
     * Indicates that the model does not have an updated_at column.
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'word',
        'reason',
    ];

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * Check if a subdomain word is blacklisted.
     *
     * Checks for exact match or if the subdomain contains a blacklisted word.
     */
    public static function isBlacklisted(string $word): bool
    {
        $word = strtolower(trim($word));

        if (empty($word)) {
            return false;
        }

        // Check exact match first
        $exactMatch = static::where('word', $word)->exists();

        if ($exactMatch) {
            return true;
        }

        // Check if the subdomain contains any blacklisted word
        $blacklistedWords = static::pluck('word')->toArray();

        foreach ($blacklistedWords as $blocked) {
            if (str_contains($word, $blocked)) {
                return true;
            }
        }

        return false;
    }
}
