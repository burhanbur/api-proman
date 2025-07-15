<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'workspace_role' => new WorkspaceRoleResource($this->whenLoaded('workspaceRole')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
