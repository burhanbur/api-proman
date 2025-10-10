<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // Primary identifiers
            'id' => $this->id,
            'attachment_id' => $this->id,
            'uuid' => $this->uuid,

            // Model relation info (which entity this attachment belongs to)
            'model_type' => $this->model_type,
            'model_id' => $this->model_id,
            // Short friendly type: task, comment, note, project, workspace
            'model_type_short' => $this->when($this->model_type, function () {
                $map = [
                    'App\\Models\\Task' => 'task',
                    'App\\Models\\Comment' => 'comment',
                    'App\\Models\\Note' => 'note',
                    'App\\Models\\Project' => 'project',
                    'App\\Models\\Workspace' => 'workspace',
                ];
                return $map[$this->model_type] ?? null;
            }),

            // File info and URLs
            'file_path' => $this->file_path,
            'file_url' => $this->when($this->file_path, function () {
                return "/{$this->file_path}";
            }),
            'download_url' => $this->when($this->uuid, function () {
                // try to build download URL using short model type
                $short = null;
                $map = [
                    'App\\Models\\Task' => 'task',
                    'App\\Models\\Comment' => 'comment',
                    'App\\Models\\Note' => 'note',
                ];
                $short = $map[$this->model_type] ?? null;
                if ($short && $this->model_id) {
                    return url("/api/attachments/{$short}/{$this->model_id}/{$this->uuid}/download");
                }
                return null;
            }),

            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
