<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\WorkspaceUserResource;

class WorkspaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'projects' => $this->whenLoaded('projects', function() {
                $currentUser = auth()->user();

                if (!$currentUser) {
                    return collect();
                }

                // Only return projects where the authenticated user is a member
                $projects = $this->projects->filter(function($project) use ($currentUser) {
                    return $project->projectUsers->contains(function($pu) use ($currentUser) {
                        return $pu->user_id === $currentUser->id;
                    });
                })->values();

                return $projects->map(function($project) use ($currentUser) {
                    return [
                        'project_id' => $project->id,
                        'name' => $project->name,
                        'slug' => $project->slug,
                        'description' => $project->description,
                        // Include all project members
                        'members' => $project->projectUsers->map(function($pu) {
                            return [
                                'user_id' => $pu->user->id,
                                'project_role_id' => $pu->projectRole->id,
                                'name' => $pu->user->name,
                                'email' => $pu->user->email,
                                'role' => $pu->projectRole->name,
                            ];
                        })->values(),
                        'tasks' => $project->relationLoaded('tasks') ? $project->tasks->map(function($task) {
                            return [
                                'id' => $task->id,
                                'uuid' => $task->uuid,
                                'title' => $task->title,
                                'description' => $task->description,
                                'due_date' => $task->due_date,
                                'point' => $task->point,
                                'created_by' => $task->createdBy->name ?? null,
                                'status' => $task->relationLoaded('status') && $task->status ? [
                                    'id' => $task->status->id,
                                    'name' => $task->status->name,
                                    'color' => $task->status->color,
                                    'is_completed' => (bool) ($task->status->is_completed ?? false),
                                    'is_cancelled' => (bool) ($task->status->is_cancelled ?? false),
                                ] : null,
                                'priority' => $task->relationLoaded('priority') && $task->priority ? [
                                    'id' => $task->priority->id,
                                    'name' => $task->priority->name,
                                    'color' => $task->priority->color,
                                ] : null,
                                'assignees' => $task->relationLoaded('assignees') ? $task->assignees->map(function($assignee) {
                                    return [
                                        'id' => $assignee->id,
                                        'name' => $assignee->name,
                                        'email' => $assignee->email,
                                        'assigned_by' => $assignee->assigned_by->name ?? null,
                                    ];
                                })->values() : [],
                                'attachments_count' => $task->relationLoaded('attachments') ? $task->attachments->count() : 0,
                                'comments_count' => $task->relationLoaded('comments') ? $task->comments->count() : 0,
                            ];
                        })->values() : collect(),
                        'tasks_count' => $project->relationLoaded('tasks') ? $project->tasks->count() : 0,
                        'tasks_completed_count' => $project->relationLoaded('tasks') ? $project->tasks->filter(function($t) {
                            return $t->relationLoaded('status') && $t->status && ($t->status->is_completed ?? false);
                        })->count() : 0,
                        'tasks_incomplete_count' => $project->relationLoaded('tasks') ? $project->tasks->filter(function($t) {
                            // consider a task incomplete when status is loaded and is_completed is falsy
                            return $t->relationLoaded('status') && $t->status && !($t->status->is_completed ?? false);
                        })->count() : 0,
                        'tasks_cancelled_count' => $project->relationLoaded('tasks') ? $project->tasks->filter(function($t) {
                            return $t->relationLoaded('status') && $t->status && ($t->status->is_cancelled ?? false);
                        })->count() : 0,
                    ];
                });
            }),
            'members' => $this->whenLoaded('workspaceUsers', function() {
                return $this->workspaceUsers->map(function($wu) {
                    return [
                        'user_id' => $wu->user->id,
                        'workspace_role_id' => $wu->workspaceRole->id,
                        'name' => $wu->user->name,
                        'email' => $wu->user->email,
                        'role' => $wu->workspaceRole->name,
                    ];
                });
            }),
            'member_count' => $this->whenLoaded('workspaceUsers', function() {
                return $this->workspaceUsers->count();
            }, 0),
            'project_count' => $this->whenLoaded('projects', function() {
                return $this->projects->count();
            }, 0),
            'task_count' => $this->whenLoaded('projects', function() {
                return $this->projects->sum(function($project) {
                    return $project->tasks->count();
                });
            }, 0),
            'attachments_count' => $this->whenLoaded('attachments', function () {
                return $this->attachments->count();
            }, 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
