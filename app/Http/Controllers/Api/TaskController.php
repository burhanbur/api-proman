<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Task;
use App\Models\Project;
use App\Services\MemberService;
use App\Traits\ApiResponse;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

use Exception;

class TaskController extends Controller
{
    use ApiResponse;

    public function index() 
    {
        $user = auth()->user();

        try {
            $query = Task::with(['project', 'assignees', 'comments', 'attachments', 'status', 'activityLogs', 'priority']);

            // Search
            if ($search = request()->query('search')) {
                $query->where(function($q) use ($search) {
                    $q->orWhere('title', 'ilike', "%{$search}%");
                    $q->orWhere('description', 'ilike', "%{$search}%");
                });
            }

            // Filter by project
            if ($projectId = request()->query('project_id')) {
                $query->where('project_id', $projectId);
            }

            // Filter by status / is_active
            if (null !== ($isActive = request()->query('is_active'))) {
                $query->where('is_active', (int) $isActive === 1 ? true : false);
            }

            // Non-admin users only see tasks in projects they belong to
            if (!in_array($user->systemRole->code, ['admin'])) {
                $query->whereHas('project.projectUsers', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            // Sorting
            $sortParams = request()->query('sort');
            if ($sortParams) {
                // expected format: field:direction,field2:direction2
                $pairs = explode(',', $sortParams);
                foreach ($pairs as $pair) {
                    [$field, $dir] = array_pad(explode(':', $pair), 2, 'asc');
                    $dir = strtolower($dir) === 'desc' ? 'desc' : 'asc';
                    $query->orderBy($field, $dir);
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // $data = $query->paginate((int) request()->query('limit', 10));
            $data = $query->get();

            return $this->successResponse(
                TaskResource::collection($data), 
                'Data tugas berhasil diambil.'
            );
        } catch (Exception $e) {
            $errMessage = $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine();
            return $this->errorResponse($errMessage, $e->getCode());
        }
    }

    public function show($uuid) 
    {
        $user = auth()->user();

        try {
            $task = Task::with(['project', 'assignees', 'comments', 'attachments', 'status', 'activityLogs', 'priority'])
            ->where('uuid', $uuid)->first();
            if (!$task) {
                return $this->errorResponse('Tugas tidak ditemukan.', 404);
            }

            // Non-admin users must belong to project
            if (!in_array($user->systemRole->code, ['admin'])) {
                $isMember = $task->project && $task->project->projectUsers()->where('user_id', $user->id)->exists();
                if (!$isMember) {
                    return $this->errorResponse('Tidak punya izin untuk melihat tugas ini.', 403);
                }
            }

            return $this->successResponse(
                new TaskResource($task),
                'Data tugas berhasil diambil.'
            );
        } catch (Exception $e) {
            $errMessage = $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine();
            return $this->errorResponse($errMessage, $e->getCode());
        }
    }

    public function store(StoreTaskRequest $request) 
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();
        try {
            // Basic permission: user must belong to project or be admin
            $project = Project::where('id', $data['project_id'])->first();
            if (!$project) {
                return $this->errorResponse('Proyek tidak ditemukan.', 404);
            }

            if (!in_array($user->systemRole->code, ['admin'])) {
                $isMember = $project->projectUsers()->where('user_id', $user->id)->exists();
                if (!$isMember) {
                    return $this->errorResponse('Tidak punya izin untuk membuat tugas di proyek ini.', 403);
                }
            }

            $taskData = $data;
            $taskData['created_by'] = $user->id;
            $taskData['updated_by'] = $user->id;

            $task = Task::create($taskData);

            DB::commit();
            return $this->successResponse(
                new TaskResource($task),
                'Tugas berhasil dibuat.'
            );
        } catch (Exception $e) {
            DB::rollBack();
            $errMessage = $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine();
            return $this->errorResponse($errMessage, $e->getCode());
        }
    }

    public function update(UpdateTaskRequest $request, $uuid) 
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $task = Task::where('uuid', $uuid)->first();
            if (!$task) {
                return $this->errorResponse('Tugas tidak ditemukan.', 404);
            }

            // Permission: only project members or admin can update
            if (!in_array($user->systemRole->code, ['admin'])) {
                $isMember = $task->project && $task->project->projectUsers()->where('user_id', $user->id)->exists();
                if (!$isMember) {
                    return $this->errorResponse('Tidak punya izin untuk memperbarui tugas ini.', 403);
                }
            }

            $data['updated_by'] = $user->id;
            $task->update($data);

            DB::commit();
            return $this->successResponse(
                new TaskResource($task),
                'Tugas berhasil diperbarui.'
            );
        } catch (Exception $e) {
            DB::rollBack();
            $errMessage = $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine();
            return $this->errorResponse($errMessage, $e->getCode());
        }
    }

    public function destroy($uuid) 
    {
        $user = auth()->user();
        DB::beginTransaction();
        try {
            $task = Task::where('uuid', $uuid)->first();
            if (!$task) {
                return $this->errorResponse('Tugas tidak ditemukan.', 404);
            }

            if (!in_array($user->systemRole->code, ['admin'])) {
                $isMember = $task->project && $task->project->projectUsers()->where('user_id', $user->id)->exists();
                if (!$isMember) {
                    return $this->errorResponse('Tidak punya izin untuk menghapus tugas ini.', 403);
                }
            }

            $task->deleted_by = $user->id;
            $task->save();
            $task->delete();

            DB::commit();
            return $this->successResponse(['message' => 'Tugas berhasil dihapus.']);
        } catch (Exception $e) {
            DB::rollBack();
            $errMessage = $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine();
            return $this->errorResponse($errMessage, $e->getCode());
        }
    }
}
