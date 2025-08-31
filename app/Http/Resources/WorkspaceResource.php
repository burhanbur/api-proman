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
            'projects' => $this->whenLoaded('projects', function() {
                return $this->projects->map(function($project) {
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
