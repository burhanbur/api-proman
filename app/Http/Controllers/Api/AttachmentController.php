<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Exception;

class AttachmentController extends Controller
{
    use ApiResponse;

    public function index() {
        try {
            $attachments = Attachment::whereNull('deleted_at')->get();
            return $this->successResponse(AttachmentResource::collection($attachments));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show($id) {
        try {
            $attachment = Attachment::where('id', $id)->whereNull('deleted_at')->first();
            if (!$attachment) {
                return $this->errorResponse('Attachment not found or has been deleted.', 404);
            }
            return $this->successResponse(new AttachmentResource($attachment));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function store(Request $request) {
        try {
            $attachment = Attachment::create($request->all());
            return $this->successResponse(new AttachmentResource($attachment));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(Request $request, $id) {
        try {
            $attachment = Attachment::where('id', $id)->first();
            $attachment->update($request->all());
            return $this->successResponse(new AttachmentResource($attachment));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy($id) {
        try {
            $attachment = Attachment::where('id', $id)->whereNull('deleted_at')->first();
            if (!$attachment) {
                return $this->errorResponse('Attachment not found or already deleted.', 404);
            }
            $attachment->delete();
            return $this->successResponse(['message' => 'Attachment deleted successfully.']);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
