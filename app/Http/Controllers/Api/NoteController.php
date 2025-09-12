<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use Illuminate\Support\Str;
use App\Traits\ApiResponse;
use App\Traits\HasAuditLog;
use App\Models\Attachment;
use Exception;
use App\Services\DocumentService;
use Illuminate\Support\Facades\DB;

class NoteController extends Controller
{
    use ApiResponse, HasAuditLog;
    
    public function store(Request $request)
    {
        $request->validate([
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
            'content' => 'required|string',
            // optional attachments
            'attachments' => 'sometimes|array',
            'attachments.*' => 'file|max:51200', // max 50MB per file
        ]);

        $data = $request->only(['model_type', 'model_id', 'content']);
        $data['uuid'] = (string) Str::uuid();
        $data['created_by'] = $request->user()->id ?? null;
        $data['updated_by'] = $request->user()->id ?? null;
        // create note and attachments atomically
        DB::beginTransaction();
        try {
            $note = Note::create($data);

            // handle attachments if any using DocumentService
            if ($request->hasFile('attachments')) {
                $files = $request->file('attachments');
                $documentService = new DocumentService();
                // saveAttachments stores files and creates Attachment records
                $documentService->saveAttachments($files, 'note', $note->id, $data['created_by']);
            }

            DB::commit();

            // audit
            $this->auditCreated($note, "Note created", $request);

            // reload note with attachments for response
            $note->load(['attachments']);

            return $this->successResponse(new NoteResource($note), 'Catatan berhasil dibuat.', 201);
        } catch (Exception $ex) {
            DB::rollBack();
            return $this->errorResponse($ex->getMessage(), $ex->getCode() ?: 500);
        }
    }

    public function index(Request $request)
    {
        $modelType = $request->query('model_type');
        $modelId = $request->query('model_id');

        if (!$modelType || !$modelId) {
            return $this->errorResponse('Parameter model_type dan model_id diperlukan.', 400);
        }

        try {
            $query = Note::with(['attachments', 'createdBy', 'updatedBy']);

            $query->where('model_type', 'App\\Models\\' . $modelType);

            $query->where('model_id', (int) $modelId);

            $data = $query->orderBy('created_at', 'desc')->get();
            return $this->successResponse(NoteResource::collection($data), 'Daftar notes berhasil diambil.');
        } catch (Exception $ex) {
            return $this->errorResponse($ex->getMessage(), $ex->getCode() ?: 500);
        }
    }

    public function show($id)
    {
        try {
            $note = Note::with(['attachments', 'createdBy', 'updatedBy'])->where('id', $id)->first();
            if (!$note) throw new Exception('Note tidak ditemukan.', 404);
            return $this->successResponse(new NoteResource($note), 'Note berhasil diambil.');
        } catch (Exception $ex) {
            return $this->errorResponse($ex->getMessage(), $ex->getCode() ?: 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'content' => 'required|string',
            ]);

            $note = Note::where('id', $id)->first();
            if (!$note) throw new Exception('Note tidak ditemukan.', 404);

            $original = $note->toArray();
            $note->update([
                'content' => $request->input('content'),
                'updated_by' => $request->user()->id ?? $note->updated_by,
            ]);

            $this->auditUpdated($note, $original, 'Note updated', $request);

            return $this->successResponse(new NoteResource($note), 'Note berhasil diperbarui.');
        } catch (Exception $ex) {
            return $this->errorResponse($ex->getMessage(), $ex->getCode() ?: 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $note = Note::where('id', $id)->first();
            if (!$note) throw new Exception('Note tidak ditemukan.', 404);

            $this->auditDeleted($note, 'Note deleted', $request);
            // set deleted_by if soft deletes used; otherwise just delete
            if (method_exists($note, 'delete')) {
                $note->delete();
            } else {
                $note->forceDelete();
            }

            return $this->successResponse(['message' => 'Note berhasil dihapus.']);
        } catch (Exception $ex) {
            return $this->errorResponse($ex->getMessage(), $ex->getCode() ?: 500);
        }
    }
}
