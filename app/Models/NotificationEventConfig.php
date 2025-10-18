<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationEventConfig extends Model
{
    protected $fillable = [
        'notification_event_id',
        'workspace_id',
        'project_id',
        'is_enabled',
        'notify_assignee',
        'notify_creator',
        'notify_project_members',
        'notify_workspace_members',
        'conditions',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'notify_assignee' => 'boolean',
        'notify_creator' => 'boolean',
        'notify_project_members' => 'boolean',
        'notify_workspace_members' => 'boolean',
        'conditions' => 'array',
    ];

    /**
     * Get the event for this config
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(NotificationEvent::class, 'notification_event_id');
    }

    /**
     * Get the workspace for this config
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the project for this config
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who created this config
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this config
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if config matches the given conditions
     * 
     * @param array $data
     * @return bool
     */
    public function matchesConditions(array $data): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $key => $value) {
            if (!isset($data[$key])) {
                return false;
            }

            // Handle array conditions (e.g., priority_level: ["high", "urgent"])
            if (is_array($value)) {
                if (!in_array($data[$key], $value)) {
                    return false;
                }
            } else {
                if ($data[$key] != $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Scope to filter by event
     */
    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('notification_event_id', $eventId);
    }

    /**
     * Scope to filter by workspace
     */
    public function scopeForWorkspace($query, ?int $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter by project
     */
    public function scopeForProject($query, ?int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope to get global configs (no workspace or project)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('workspace_id')->whereNull('project_id');
    }

    /**
     * Scope to get enabled configs
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
