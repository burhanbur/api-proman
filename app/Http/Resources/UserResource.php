<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'username' => $this->username,
            'name' => $this->name,
            'email' => $this->email,
            'system_role' => $this->whenLoaded('systemRole', function () {
                return [
                    'id' => $this->systemRole->id ?? null,
                    'code' => $this->systemRole->code ?? null,
                    'name' => $this->systemRole->name ?? null,
                ];
            }),
            'workspaces' => WorkspaceResource::collection($this->whenLoaded('workspaces')),
            'workspace_users' => $this->whenLoaded('workspaceUsers'),
            'created_workspaces' => $this->whenLoaded('createdWorkspaces'),
            'updated_workspaces' => $this->whenLoaded('updatedWorkspaces'),
            'deleted_workspaces' => $this->whenLoaded('deletedWorkspaces'),
            'created_projects' => $this->whenLoaded('createdProjects'),
            'updated_projects' => $this->whenLoaded('updatedProjects'),
            'deleted_projects' => $this->whenLoaded('deletedProjects'),
            'created_tasks' => $this->whenLoaded('createdTasks'),
            'updated_tasks' => $this->whenLoaded('updatedTasks'),
            'deleted_tasks' => $this->whenLoaded('deletedTasks'),
            'comments' => $this->whenLoaded('comments'),
            'updated_comments' => $this->whenLoaded('updatedComments'),
            'deleted_comments' => $this->whenLoaded('deletedComments'),
            'attachments' => $this->whenLoaded('attachments'),
            'updated_attachments' => $this->whenLoaded('updatedAttachments'),
            'deleted_attachments' => $this->whenLoaded('deletedAttachments'),
            'audit_logs' => $this->whenLoaded('auditLogs'),
            'assigned_tasks' => $this->whenLoaded('assignedTasks'),
            'assigned_by_tasks' => $this->whenLoaded('assignedByTasks'),
            'task_activity_logs' => $this->whenLoaded('taskActivityLogs'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
