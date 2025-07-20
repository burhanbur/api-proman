<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Services\DocumentService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Exception;

class AttachmentController extends Controller
{
    use ApiResponse;

    public function index(Request $request) 
    {
        try {
            $attachments = Attachment::all();
            return $this->successResponse(AttachmentResource::collection($attachments));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show($id) 
    {
        try {
            $attachment = Attachment::find($id);
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
            $attachment = Attachment::create($request->all());
            return $this->successResponse(new AttachmentResource($attachment));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(Request $request, $id) {
        try {
            $attachment = Attachment::find($id);
            $attachment->update($request->all());
            return $this->successResponse(new AttachmentResource($attachment));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy($id) {
        try {
            $attachment = Attachment::find($id);
            if (!$attachment) {
                return $this->errorResponse('Lampiran tidak ditemukan atau sudah dihapus.', 404);
            }
            $attachment->delete();
            return $this->successResponse(['message' => 'Lampiran berhasil dihapus.']);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
