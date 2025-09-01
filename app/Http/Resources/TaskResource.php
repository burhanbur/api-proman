<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'project' => $this->whenLoaded('project', function () {
                return [
                    'project_id' => $this->project->id,
                    'name' => $this->project->name,
                    'slug' => $this->project->slug ?? null,
                ];
            }),
            'title' => $this->title,
            'description' => $this->description,
            'due_date' => $this->due_date,
            'priority' => $this->whenLoaded('priority', function () {
                return [
                    'priority_id' => $this->priority->id,
                    'name' => $this->priority->name,
                ];
            }),
            'status' => $this->whenLoaded('status', function () {
                return [
                    'status_id' => $this->status->id,
                    'name' => $this->status->name,
                ];
            }),
            'assignees' => $this->whenLoaded('assignees', function () {
                return $this->assignees->map(function ($a) {
                    return [
                        'assign_id' => $a->id,
                        'user_id' => $a->user_id,
                        'name' => $a->user->name ?? null,
                        'email' => $a->user->email ?? null,
                        'assigned_at' => $a->created_at,
                    ];
                });
            }, []),
            'comments_count' => $this->whenLoaded('comments', function () {
                return $this->comments->count();
            }, 0),
            'attachments_count' => $this->whenLoaded('attachments', function () {
                return $this->attachments->count();
            }, 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
