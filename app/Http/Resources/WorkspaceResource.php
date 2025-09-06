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

                // If current user is member of workspace, return all projects.
                $isWorkspaceMember = $this->workspaceUsers->contains(function($wu) use ($currentUser) {
                    return $wu->user_id === $currentUser->id;
                });

                $projects = $this->projects;
                if (!$isWorkspaceMember) {
                    // Filter projects to only those where the user is a project member
                    $projects = $projects->filter(function($project) use ($currentUser) {
                        return $project->projectUsers->contains(function($pu) use ($currentUser) {
                            return $pu->user_id === $currentUser->id;
                        });
                    })->values();
                }

                return $projects->map(function($project) {
                    return [
                        'project_id' => $project->id,
                        'name' => $project->name,
                        'slug' => $project->slug,
                        'members' => $project->projectUsers->map(function($pu) {
                            return [
                                'user_id' => $pu->user->id,
                                'project_role_id' => $pu->projectRole->id,
                                'name' => $pu->user->name,
                                'email' => $pu->user->email,
                                'role' => $pu->projectRole->name,
                            ];
                        }),
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
