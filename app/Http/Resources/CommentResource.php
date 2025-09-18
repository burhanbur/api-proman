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
            'attachments_count' => $this->whenLoaded('attachments', function () {
                return $this->attachments->count();
            }, 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
