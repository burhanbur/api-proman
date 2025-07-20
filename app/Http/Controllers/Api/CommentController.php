<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Exception;

class CommentController extends Controller
{
    use ApiResponse;

    public function index() {
        try {
            $comments = Comment::all();
            return $this->successResponse(CommentResource::collection($comments));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show($id) {
        try {
            $comment = Comment::find($id);
            if (!$comment) {
                return $this->errorResponse('Komentar tidak ditemukan.', 404);
            }
            return $this->successResponse(new CommentResource($comment));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function store(Request $request) {
        try {
            $comment = Comment::create($request->all());
            return $this->successResponse(new CommentResource($comment));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(Request $request, $id) {
        try {
            $comment = Comment::where('id', $id)->first();
            $comment->update($request->all());
            return $this->successResponse(new CommentResource($comment));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy($id) {
        try {
            $comment = Comment::find($id);
            if (!$comment) {
                return $this->errorResponse('Komentar tidak ditemukan.', 404);
            }
            $comment->delete();
            return $this->successResponse(['message' => 'Komentar berhasil dihapus.']);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
