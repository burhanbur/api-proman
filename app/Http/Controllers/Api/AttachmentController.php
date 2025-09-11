<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Services\DocumentService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Task;
use App\Models\Comment;
use App\Models\Note;
use App\Models\Workspace;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Exception;

class AttachmentController extends Controller
{
    use ApiResponse;

    public function index(Request $request) 
    {
        try {
            $query = Attachment::query();

            if ($modelType = $request->query('model_type')) {
                // allow short names
                $mt = strtolower($modelType);
                $map = [
                    'task' => Task::class,
                    'comment' => Comment::class,
                    'note' => Note::class,
                    'workspace' => Workspace::class,
                    'project' => Project::class,
                ];
                if (isset($map[$mt])) {
                    $query->where('model_type', $map[$mt]);
                } else {
                    // assume full class was passed
                    $query->where('model_type', $modelType);
                }
            }

            if ($modelId = $request->query('model_id')) {
                $query->where('model_id', (int) $modelId);
            }

            $attachments = $query->orderBy('created_at', 'desc')->get();
            return $this->successResponse(AttachmentResource::collection($attachments));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show($uuid) 
    {
        try {
            $attachment = Attachment::where('uuid', $uuid)->first();
            if (!$attachment) {
                return $this->errorResponse('Lampiran tidak ditemukan.', 404);
            }
            return $this->successResponse(new AttachmentResource($attachment));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function store(Request $request) 
    {
        try {
            // build validation rules to accept single file or multiple files
            $rules = [
                'model_type' => 'required|string',
                'model_id' => 'required|integer',
            ];

            $fileInput = $request->file('file');
            if (is_array($fileInput)) {
                $rules['file'] = 'required|array';
                $rules['file.*'] = 'file';
            } else {
                $rules['file'] = 'required|file';
            }

            $request->validate($rules);

            $modelType = $request->input('model_type');
            $modelId = (int) $request->input('model_id');
            $userId = $request->user()->id ?? null;

            $documentService = new DocumentService();
            $files = $request->file('file');
            
            $attachments = $documentService->saveAttachments($files, $modelType, $modelId, $userId);

            if (count($attachments) === 1) {
                return $this->successResponse(new AttachmentResource($attachments[0]), 'File berhasil diupload.', 201);
            }

            return $this->successResponse(AttachmentResource::collection(collect($attachments)), 'Files berhasil diupload.', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(Request $request, $uuid) {
        try {
            $attachment = Attachment::where('uuid', $uuid)->first();
            if (!$attachment) {
                return $this->errorResponse('Lampiran tidak ditemukan.', 404);
            }
            
            $userId = $request->user()->id ?? null;
            $attachment->updated_by = $userId;
            $attachment->update($request->all());
            
            return $this->successResponse(new AttachmentResource($attachment));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy($uuid) {
        try {
            $attachment = Attachment::where('uuid', $uuid)->first();
            if (!$attachment) {
                return $this->errorResponse('Lampiran tidak ditemukan atau sudah dihapus.', 404);
            }

            $userId = request()->user()->id ?? null;
            $documentService = new DocumentService();
            $documentService->deleteAttachment($attachment, $userId);

            return $this->successResponse(['message' => 'Lampiran berhasil dihapus.']);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Download an attachment by model type, model id and attachment uuid
     * e.g. GET /attachments/{model_type}/{model_id}/{uuid}/download
     */
    public function download($modelType, $modelId, $uuid)
    {
        try {
            $modelType = strtolower($modelType);
            $map = [
                'task' => Task::class,
                'comment' => Comment::class,
                'note' => Note::class,
            ];

            if (!isset($map[$modelType])) {
                return $this->errorResponse('Tipe model tidak didukung.', 422);
            }

            $modelClass = $map[$modelType];

            $attachment = Attachment::where('uuid', $uuid)
                ->where('model_type', $modelClass)
                ->where('model_id', (int) $modelId)
                ->first();

            if (!$attachment) {
                return $this->errorResponse('Lampiran tidak ditemukan.', 404);
            }

            $disk = Storage::disk('public');
            if (!$disk->exists($attachment->file_path)) {
                return $this->errorResponse('File tidak ditemukan di storage.', 404);
            }

            $fullPath = $disk->path($attachment->file_path);
            return response()->download($fullPath, $attachment->original_filename, ['Content-Type' => $attachment->mime_type]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}
