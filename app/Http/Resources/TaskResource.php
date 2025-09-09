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
            'title' => $this->title,
            'description' => $this->description,
            'due_date' => $this->due_date,
            'point' => $this->point,
            'is_completed' => $this->is_completed,
            'is_cancelled' => $this->is_cancelled,
            'created_by' => $this->createdBy->name ?? null,
            'project' => $this->whenLoaded('project', function () {
                return [
                    'project_id' => $this->project->id,
                    'name' => $this->project->name,
                    'slug' => $this->project->slug ?? null,
                    // include workspace only when project->workspace is loaded or present
                    'workspace' => $this->when(
                        isset($this->project) && (
                            (method_exists($this->project, 'relationLoaded') && $this->project->relationLoaded('workspace'))
                            || isset($this->project->workspace)
                        ),
                        function () {
                            return [
                                'workspace_id' => $this->project->workspace->id,
                                'name' => $this->project->workspace->name,
                                'slug' => $this->project->workspace->slug ?? null,
                            ];
                        }
                    ),
                ];
            }),
            'priority' => $this->whenLoaded('priority', function () {
                return [
                    'priority_id' => $this->priority->id,
                    'name' => $this->priority->name,
                    'level' => $this->priority->level ?? null,
                    'color' => $this->priority->color ?? null,
                ];
            }),
            'status' => $this->whenLoaded('status', function () {
                return [
                    'status_id' => $this->status->id,
                    'name' => $this->status->name,
                    'color' => $this->status->color ?? null,
                ];
            }),
            'assignees' => $this->whenLoaded('assignees', function () {
                return $this->assignees->map(function ($a) {
                    return [
                        'user_id' => $a->id,
                        'name' => $a->name ?? null,
                        'email' => $a->email ?? null,
                        'assigned_at' => $a->pivot->created_at ?? $a->created_at,
                    ];
                });
            }, []),
            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(function ($att) {
                    return [
                        'attachment_id' => $att->id,
                        'uuid' => $att->uuid,
                        'original_name' => $att->original_name,
                        'file_name' => $att->file_name,
                        'file_path' => $att->file_path,
                        'mime' => $att->mime,
                        'size' => $att->size,
                        'created_at' => $att->created_at,
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
