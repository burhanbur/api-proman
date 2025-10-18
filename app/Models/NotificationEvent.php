<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationEvent extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the templates for this event
     */
    public function templates(): HasMany
    {
        return $this->hasMany(NotificationTemplate::class);
    }

    /**
     * Get the active template for this event
     */
    public function activeTemplate()
    {
        return $this->templates()->where('is_active', true)->first();
    }

    /**
     * Get all configs for this event
     */
    public function configs(): HasMany
    {
        return $this->hasMany(NotificationEventConfig::class);
    }

    /**
     * Get user preferences for this event
     */
    public function userPreferences(): HasMany
    {
        return $this->hasMany(UserNotificationPreference::class);
    }

    /**
     * Get all notifications triggered by this event
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Scope to get only active events
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
