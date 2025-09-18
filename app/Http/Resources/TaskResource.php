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
                    'is_completed' => (bool) $this->status->is_completed ?? false,
                    'is_cancelled' => (bool) $this->status->is_canceled ?? false,
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
                        'file_path' => $att->file_path,
                        'original_filename' => $att->original_filename,
                        'mime_type' => $att->mime_type,
                        'file_size' => $att->file_size,
                        'created_by' => $att->createdBy->name ?? null,
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
            'related_from' => $this->whenLoaded('relatedFrom', function () {
                return $this->relatedFrom->map(function ($relation) {
                    return [
                        'task_id' => $relation->task->id,
                        'task_uuid' => $relation->task->uuid,
                        'title' => $relation->task->title,
                        'relation_type' => $relation->relationType->name ?? null,
                    ];
                });
            }, []),
            'related_to' => $this->whenLoaded('relatedTo', function () {
                return $this->relatedTo->map(function ($relation) {
                    return [
                        'task_id' => $relation->relatedTask->id,
                        'task_uuid' => $relation->relatedTask->uuid,
                        'title' => $relation->relatedTask->title,
                        'relation_type' => $relation->relationType->name ?? null,
                    ];
                });
            }, []),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
