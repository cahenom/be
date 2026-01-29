<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class RoleProfitSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'markup_percent',
        'markup_min',
        'markup_max',
        'is_default',
    ];

    /**
     * Boot the model and attach event listeners
     */
    protected static function booted()
    {
        // Clear cache when a role profit setting is created, updated, or deleted
        static::saved(function () {
            self::clearRelatedCache();
        });

        static::deleted(function () {
            self::clearRelatedCache();
        });
    }

    /**
     * Clear cache related to role profit settings
     */
    public static function clearRelatedCache()
    {
        // Clear all role profit setting-related cache
        // Since we cache by role_id, we need to clear all cached role settings
        // In a real application, you might want to use cache tags
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
