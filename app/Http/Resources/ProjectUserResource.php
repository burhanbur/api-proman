<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'user' => new UserResource($this->whenLoaded('user')),
            'project_role' => new ProjectRoleResource($this->whenLoaded('projectRole')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
