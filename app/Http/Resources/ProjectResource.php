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
            'workspace' => $this->whenLoaded('workspace', function() {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                    'slug' => $this->workspace->slug,
                ];
            }),
            'members' => $this->whenLoaded('projectUsers') ? $this->projectUsers->map(function($pu) {
                return [
                    'id' => $pu->id,
                    'user_id' => $pu->user_id,
                    'user_name' => $pu->user->name,
                    'user_email' => $pu->user->email,
                    'project_role_id' => $pu->project_role_id,
                    'project_role' => $pu->projectRole->name,
                ];
            }) : [],
            'tasks' => $this->whenLoaded('tasks') ? $this->tasks->map(function($task) {
                return [
                    'id' => $task->id,
                    'name' => $task->name,
                    'description' => $task->description,
                    'status' => $task->status,
                ];
            }) : [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
