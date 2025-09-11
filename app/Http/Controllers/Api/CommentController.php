<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Task;
use App\Traits\ApiResponse;
use App\Traits\HasAuditLog;
use App\Services\DocumentService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class CommentController extends Controller
{
    use ApiResponse, HasAuditLog;

    public function index(Request $request) 
    {
        $user = auth()->user();

        try {
            $query = Comment::with(['task.project', 'createdBy']);

            // Search functionality
            if ($search = $request->query('search')) {
                $query->where('comment', 'like', "%{$search}%");
            }

            // Filter by task_id
            if ($taskId = $request->query('task_id')) {
                $query->where('task_id', $taskId);
            }

            // Permission: only show comments from projects user has access to
            if (!in_array($user->systemRole->code, ['admin'])) {
                $query->whereHas('task.project.projectUsers', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            // Sorting
            $sortParams = $request->query('sort');
            if ($sortParams) {
                $sorts = explode(';', $sortParams);
                $allowedSortFields = ['created_at', 'updated_at'];
    
                foreach ($sorts as $sort) {
                    [$field, $direction] = explode(',', $sort) + [null, 'desc'];
                    $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
    
                    if (in_array($field, $allowedSortFields)) {
                        $query->orderBy($field, $direction);
                    }
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $data = $query->get();

            return $this->successResponse(
                CommentResource::collection($data),
                'Data komentar berhasil diambil.'
            );
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function show($uuid) 
    {
        $user = auth()->user();

        try {
            $query = Comment::with(['task.project', 'createdBy']);

            // Permission: only show comments from projects user has access to
            if (!in_array($user->systemRole->code, ['admin'])) {
                $query->whereHas('task.project.projectUsers', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            $comment = $query->where('uuid', $uuid)->first();

            if (!$comment) {
                throw new Exception('Komentar tidak ditemukan.', 404);
            }

            return $this->successResponse(
                new CommentResource($comment),
                'Data komentar berhasil diambil.'
            );
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function store(StoreCommentRequest $request) 
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();
        try {
            // Basic permission: user must belong to project or be admin
            $task = Task::with('project')->where('id', $data['task_id'])->first();
            if (!$task) {
                return $this->errorResponse('Task tidak ditemukan.', 404);
            }

            if (!in_array($user->systemRole->code, ['admin'])) {
                $isMember = $task->project->projectUsers()->where('user_id', $user->id)->exists();
                if (!$isMember) {
                    return $this->errorResponse('Tidak punya izin untuk membuat komentar di task ini.', 403);
                }
            }

            $commentData = $data;
            $commentData['uuid'] = Str::uuid();
            $commentData['created_by'] = $user->id;
            $commentData['updated_by'] = $user->id;

            $comment = Comment::create($commentData);

            // Log audit untuk comment yang dibuat
            $this->auditCreated($comment, "Komentar berhasil ditambahkan ke task '{$task->title}'", $request);

            DB::commit();
            return $this->successResponse(
                new CommentResource($comment->load(['task.project', 'createdBy'])),
                'Komentar berhasil dibuat.'
            );
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function update(UpdateCommentRequest $request, $uuid) 
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $comment = Comment::with(['task.project'])->where('uuid', $uuid)->first();
            if (!$comment) {
                return $this->errorResponse('Komentar tidak ditemukan.', 404);
            }

            // Permission: only comment author or admin can update
            if (!in_array($user->systemRole->code, ['admin']) && $comment->created_by !== $user->id) {
                return $this->errorResponse('Tidak punya izin untuk memperbarui komentar ini.', 403);
            }

            // Additional permission: user must still belong to project
            if (!in_array($user->systemRole->code, ['admin'])) {
                $isMember = $comment->task->project->projectUsers()->where('user_id', $user->id)->exists();
                if (!$isMember) {
                    return $this->errorResponse('Tidak punya izin untuk memperbarui komentar di task ini.', 403);
                }
            }

            // Simpan data original untuk audit log
            $originalData = $comment->toArray();

            $data['updated_by'] = $user->id;
            $comment->update($data);

            // Log audit untuk comment yang diupdate
            $this->auditUpdated($comment, $originalData, "Komentar berhasil diperbarui di task '{$comment->task->title}'", $request);

            DB::commit();
            return $this->successResponse(
                new CommentResource($comment->load(['task.project', 'createdBy'])),
                'Komentar berhasil diperbarui.'
            );
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function destroy($uuid) 
    {
        $user = auth()->user();
        
        DB::beginTransaction();
        try {
            $comment = Comment::with(['task.project'])->where('uuid', $uuid)->first();
            if (!$comment) {
                return $this->errorResponse('Komentar tidak ditemukan.', 404);
            }

            // Permission: only comment author or admin can delete
            if (!in_array($user->systemRole->code, ['admin']) && $comment->created_by !== $user->id) {
                return $this->errorResponse('Tidak punya izin untuk menghapus komentar ini.', 403);
            }

            // Additional permission: user must still belong to project
            if (!in_array($user->systemRole->code, ['admin'])) {
                $isMember = $comment->task->project->projectUsers()->where('user_id', $user->id)->exists();
                if (!$isMember) {
                    return $this->errorResponse('Tidak punya izin untuk menghapus komentar di task ini.', 403);
                }
            }

            // Log audit sebelum menghapus
            $this->auditDeleted($comment, "Komentar berhasil dihapus dari task '{$comment->task->title}'", request());

            // Set deleted_by untuk soft delete
            $comment->deleted_by = $user->id;
            $comment->save();
            $comment->delete();

            DB::commit();
            return $this->successResponse(['message' => 'Komentar berhasil dihapus.']);
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }
}
