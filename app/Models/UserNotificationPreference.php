<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'notification_event_id',
        'is_enabled',
        'channel_email',
        'channel_push',
        'channel_in_app',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'channel_email' => 'boolean',
        'channel_push' => 'boolean',
        'channel_in_app' => 'boolean',
    ];

    /**
     * Get the user that owns this preference
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the event for this preference
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(NotificationEvent::class, 'notification_event_id');
    }

    /**
     * Check if user wants to receive notification via specific channel
     * 
     * @param string $channel (email, push, in_app)
     * @return bool
     */
    public function isChannelEnabled(string $channel): bool
    {
        $channelKey = 'channel_' . $channel;
        return $this->is_enabled && ($this->{$channelKey} ?? false);
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by event
     */
    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('notification_event_id', $eventId);
    }

    /**
     * Scope to get only enabled preferences
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
