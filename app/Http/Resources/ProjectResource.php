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
            'workspace' => WorkspaceResource::collection($this->whenLoaded('workspace')),
            'status' => ProjectStatusResource::collection($this->whenLoaded('projectStatus')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'members' => ProjectUserResource::collection($this->whenLoaded('projectUsers')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
