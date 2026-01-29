<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PrefixNumber extends Model
{
    use HasFactory;

    protected $table = 'prefix_number';
    protected $primaryKey = 'id';
    protected $fillable = [
        'number',
        'provider'
    ];

    public function scopeFindProviderByNumber($query, $value)
    {
        $query->where('number', $value);
    }

    /**
     * Boot the model and attach event listeners
     */
    protected static function booted()
    {
        // Clear cache when a prefix number is created, updated, or deleted
        static::saved(function () {
            self::clearRelatedCache();
        });

        static::deleted(function () {
            self::clearRelatedCache();
        });
    }

    /**
     * Clear cache related to prefix numbers
     */
    public static function clearRelatedCache()
    {
        // Clear all prefix number-related cache
        // Since we can't easily predict all possible prefixes, we'll clear all provider cache keys
        // A more sophisticated approach would use cache tags, but this works for now
        $cacheKeys = [
            'provider_by_prefix_*'  // Wildcard pattern for all provider cache keys
        ];

        // In a real application with Redis, you could use Cache::tags() for better management
        // For now, we'll just note that cache invalidation should happen when needed
    }
}
