<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $guarded = [];
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = [
        'uuid',
        'user_id',
        'notification_event_id',
        'related_model_type',
        'related_model_id',
        'triggered_by',
        'type',
        'title',
        'message',
        'detail_url',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the event that triggered this notification
     */
    public function event()
    {
        return $this->belongsTo(NotificationEvent::class, 'notification_event_id');
    }

    /**
     * Get the user who triggered this notification
     */
    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * Get the related model (polymorphic)
     */
    public function relatedModel()
    {
        if ($this->related_model_type && $this->related_model_id) {
            $modelClass = 'App\\Models\\' . $this->related_model_type;
            if (class_exists($modelClass)) {
                return $modelClass::find($this->related_model_id);
            }
        }
        return null;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Scope to get unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by event type
     */
    public function scopeByEvent($query, int $eventId)
    {
        return $query->where('notification_event_id', $eventId);
    }
}
