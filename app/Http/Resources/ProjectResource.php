<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'workspace' => $this->whenLoaded('workspace', function() {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                    'slug' => $this->workspace->slug,
                ];
            }),
            'members' => $this->whenLoaded('projectUsers') ? $this->projectUsers->map(function($pu) {
                return [
                    'user_id' => $pu->user_id,
                    'user_name' => $pu->user->name,
                    'user_email' => $pu->user->email,
                    'project_role_id' => $pu->project_role_id,
                    'project_role' => $pu->projectRole->name,
                ];
            }) : [],
            'tasks' => $this->whenLoaded('tasks') ? $this->tasks->map(function($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'due_date' => $task->due_date,
                    'status' => $this->whenLoaded('status') ? [
                        'id' => $task->status->id,
                        'name' => $task->status->name,
                        'color' => $task->status->color,
                    ] : null,
                    'priority' => $this->whenLoaded('priority') ? [
                        'id' => $task->priority->id,
                        'name' => $task->priority->name,
                        'color' => $task->priority->color,
                    ] : null,
                    'assignees' => $this->whenLoaded('assignees') ? $task->assignees->map(function($assignee) {
                        return [
                            'id' => $assignee->id,
                            'name' => $assignee->name,
                            'email' => $assignee->email,
                            'assigned_by' => $assignee->assigned_by->name ?? null,
                        ];
                    }) : [],
                ];
            }) : [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
