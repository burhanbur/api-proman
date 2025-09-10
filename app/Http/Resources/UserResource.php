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
            'workspaces' => $this->whenLoaded('workspaceUsers', function () {
                return $this->workspaceUsers->map(fn ($workspaceUser) => $workspaceUser->workspace->name ? [
                    'id' => $workspaceUser->workspace->id ?? null,
                    'slug' => $workspaceUser->workspace->slug ?? null,
                    'name' => $workspaceUser->workspace->name ?? null,
                    'role' => $workspaceUser->workspaceRole->name ?? null,
                    'projects' => $workspaceUser->workspace->projects->map(fn ($project) => $project->name ? [
                        'id' => $project->id ?? null,
                        'slug' => $project->slug ?? null,
                        'name' => $project->name ?? null,
                        'role' => $workspaceUser->workspaceRole->name ?? null,
                    ] : []),
                ] : []);
            }),
            'projects' => $this->whenLoaded('projectUsers', function () {
                return $this->projectUsers->map(fn ($projectUser) => $projectUser->project->name ? [
                    'id' => $projectUser->project->id ?? null,
                    'slug' => $projectUser->project->slug ?? null,
                    'name' => $projectUser->project->name ?? null,
                    'role' => $projectUser->projectRole->name ?? null,
                ] : []);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
