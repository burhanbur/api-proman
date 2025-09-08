<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'model_type' => $this->model_type,
            'model_id' => $this->model_id,
            'content' => $this->content,
            
            'created_by' => $this->whenLoaded('createdBy') ? [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
                'email' => $this->createdBy->email,
            ] : null,
            
            'updated_by' => $this->whenLoaded('updatedBy') ? [
                'id' => $this->updatedBy->id,
                'name' => $this->updatedBy->name,
                'email' => $this->updatedBy->email,
            ] : null,
            
            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(function ($att) {
                    return [
                        'attachment_id' => $att->id,
                        'uuid' => $att->uuid,
                        'original_filename' => $att->original_filename,
                        'file_path' => $att->file_path,
                        'mime_type' => $att->mime_type,
                        'file_size' => $att->file_size,
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
