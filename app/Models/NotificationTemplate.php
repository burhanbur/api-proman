<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'notification_event_id',
        'title_template',
        'message_template',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the event that owns this template
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(NotificationEvent::class, 'notification_event_id');
    }

    /**
     * Render title with data
     * 
     * @param array $data
     * @return string
     */
    public function renderTitle(array $data): string
    {
        return $this->renderTemplate($this->title_template, $data);
    }

    /**
     * Render message with data
     * 
     * @param array $data
     * @return string
     */
    public function renderMessage(array $data): string
    {
        return $this->renderTemplate($this->message_template, $data);
    }

    /**
     * Render template by replacing placeholders with actual data
     * 
     * @param string $template
     * @param array $data
     * @return string
     */
    private function renderTemplate(string $template, array $data): string
    {
        $result = $template;
        
        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            
            // Convert value to string safely
            if (is_array($value)) {
                // Skip arrays, don't replace
                continue;
            } elseif (is_null($value)) {
                $stringValue = '';
            } elseif (is_bool($value)) {
                $stringValue = $value ? 'true' : 'false';
            } elseif (is_object($value)) {
                // Skip objects, don't replace
                continue;
            } else {
                $stringValue = (string) $value;
            }
            
            $result = str_replace($placeholder, $stringValue, $result);
        }
        
        return $result;
    }

    /**
     * Scope to get only active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
