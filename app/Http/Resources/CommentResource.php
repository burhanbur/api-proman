<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'comment' => $this->comment,
            'task' => $this->whenLoaded('task') ? [
                'id' => $this->task->id,
                'title' => $this->task->title,
                'uuid' => $this->task->uuid,
            ] : null,
            'user' => $this->whenLoaded('createdBy') ? [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
                'email' => $this->createdBy->email,
            ] : null,
            'attachments_count' => $this->whenLoaded('attachments', function () {
                return $this->attachments->count();
            }, 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
