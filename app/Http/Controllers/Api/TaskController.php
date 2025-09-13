<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Task;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Traits\ApiResponse;
use App\Traits\HasAuditLog;
use App\Services\DocumentService;

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
    use ApiResponse, HasAuditLog;

    public function index() 
    {
        $user = auth()->user();

        try {
            $query = Task::with(['project.workspace', 'assignees', 'comments', 'attachments', 'status', 'activityLogs', 'priority']);

            // Search
            if ($search = request()->query('search')) {
                $query->where(function($q) use ($search) {
                    $q->orWhere('title', 'like', "%{$search}%");
                    $q->orWhere('description', 'like', "%{$search}%");
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
            if (!in_array(optional($user->systemRole)->code, ['admin'])) {
                // fetch project ids the user is a member of and restrict tasks by those projects
                $projectIds = Project::whereHas('projectUsers', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->pluck('id')->toArray();

                // if user has no projects this will correctly return an empty result set
                $query->whereIn('project_id', $projectIds);
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

    public function recent() 
    {
        $user = auth()->user();

        try {
            $query = Task::with(['project.workspace', 'assignees', 'comments', 'attachments', 'status', 'activityLogs', 'priority']);

            // Search
            if ($search = request()->query('search')) {
                $query->where(function($q) use ($search) {
                    $q->orWhere('title', 'like', "%{$search}%");
                    $q->orWhere('description', 'like', "%{$search}%");
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

            $data = $query->paginate((int) request()->query('limit', 2));

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
            $task = Task::with(['project.workspace', 'assignees', 'comments', 'attachments', 'status', 'activityLogs', 'priority'])
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
            $taskData['uuid'] = (string) Str::uuid();
            $taskData['created_by'] = $user->id;
            $taskData['updated_by'] = $user->id;

            $task = Task::create($taskData);

            // Log audit untuk task yang dibuat
            $this->auditCreated($task, "Task '{$task->title}' berhasil dibuat di project '{$project->name}'", $request);

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

            // Simpan data original untuk audit log
            $originalData = $task->toArray();

            $data['updated_by'] = $user->id;
            $task->update($data);

            // Log audit untuk task yang diupdate
            $this->auditUpdated($task, $originalData, "Task '{$task->title}' berhasil diperbarui", $request);

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

            // Log audit sebelum menghapus
            $this->auditDeleted($task, "Task '{$task->title}' berhasil dihapus", request());

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

    public function updateStatus(Request $request, $uuid)
    {
        $user = auth()->user();
        
        $validator = Validator::make($request->all(), [
            'status_id' => 'required|numeric|exists:project_status,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $task = Task::with(['status'])->where('uuid', $uuid)->first();
            if (!$task) {
                return $this->errorResponse('Tugas tidak ditemukan.', 404);
            }

            // Permission: only project members or admin can update
            if (!in_array($user->systemRole->code, ['admin'])) {
                $isMember = $task->project && $task->project->projectUsers()->where('user_id', $user->id)->exists();
                if (!$isMember) {
                    return $this->errorResponse('Tidak punya izin untuk memperbarui status tugas ini.', 403);
                }
            }

            // Simpan data original untuk audit log
            $originalData = $task->toArray();

            // Derive readable status names to avoid array/object to string conversion
            $oldStatusObj = $task->status; // relation was eager loaded
            $oldStatusName = null;
            if ($oldStatusObj) {
                $oldStatusName = $oldStatusObj->name ?? ($oldStatusObj->id ?? null);
            }

            $newStatusId = (int) $request->status_id;
            // Try to fetch new status name for better audit message
            $newStatusObj = ProjectStatus::find($newStatusId);
            $newStatusName = $newStatusObj ? ($newStatusObj->name ?? $newStatusObj->id) : $newStatusId;

            $task->update([
                'status_id' => $newStatusId,
                'updated_by' => $user->id,
            ]);

            // Log audit untuk perubahan status (use names / ids only)
            $message = "Status task '{$task->title}' diubah dari '" . ($oldStatusName ?? 'Unknown') . "' ke '" . ($newStatusName ?? 'Unknown') . "'";
            $this->auditUpdated($task, $originalData, $message, $request);

            DB::commit();
            return $this->successResponse(
                new TaskResource($task),
                'Status tugas berhasil diperbarui.'
            );
        } catch (Exception $e) {
            DB::rollBack();
            $errMessage = $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine();
            return $this->errorResponse($errMessage, $e->getCode());
        }
    }

    public function assignTask(Request $request, $uuid)
    {
        $user = auth()->user();
        
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        DB::beginTransaction();
        try {
            $task = Task::where('uuid', $uuid)->first();
            if (!$task) {
                return $this->errorResponse('Tugas tidak ditemukan.', 404);
            }

            // Permission: only project members or admin can assign tasks
            if (!in_array($user->systemRole->code, ['admin'])) {
                $isMember = $task->project && $task->project->projectUsers()->where('user_id', $user->id)->exists();
                if (!$isMember) {
                    return $this->errorResponse('Tidak punya izin untuk menugaskan tugas ini.', 403);
                }
            }

            // Check if user to be assigned is also a project member
            $assigneeId = $request->user_id;
            $isAssigneeMember = $task->project && $task->project->projectUsers()->where('user_id', $assigneeId)->exists();
            
            if (!$isAssigneeMember && !in_array($user->systemRole->code, ['admin'])) {
                return $this->errorResponse('Pengguna yang akan ditugaskan harus menjadi anggota proyek.', 403);
            }

            // Check if already assigned
            if ($task->assignees()->where('user_id', $assigneeId)->exists()) {
                return $this->errorResponse('Pengguna sudah ditugaskan ke tugas ini.', 400);
            }

            // Assign the task
            $task->assignees()->attach($assigneeId);

            // Get assignee user info for audit log
            $assigneeUser = \App\Models\User::find($assigneeId);
            
            // Log audit untuk penugasan
            $this->auditCustom($task, 'assigned', null, null, "Menugaskan '{$assigneeUser->name}' ke task '{$task->title}'", $request);

            DB::commit();
            return $this->successResponse(
                new TaskResource($task->load('assignees')),
                'Tugas berhasil ditugaskan.'
            );
        } catch (Exception $e) {
            DB::rollBack();
            $errMessage = $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine();
            return $this->errorResponse($errMessage, $e->getCode());
        }
    }

    public function unassignTask($uuid, $userId)
    {
        $user = auth()->user();

        DB::beginTransaction();
        try {
            $task = Task::where('uuid', $uuid)->first();
            if (!$task) {
                return $this->errorResponse('Tugas tidak ditemukan.', 404);
            }

            // Permission: only project members or admin can unassign tasks
            if (!in_array($user->systemRole->code, ['admin'])) {
                $isMember = $task->project && $task->project->projectUsers()->where('user_id', $user->id)->exists();
                if (!$isMember) {
                    return $this->errorResponse('Tidak punya izin untuk membatalkan penugasan tugas ini.', 403);
                }
            }

            // Check if user is currently assigned
            if (!$task->assignees()->where('user_id', $userId)->exists()) {
                return $this->errorResponse('Pengguna tidak ditugaskan ke tugas ini.', 400);
            }

            // Get assignee user info for audit log before detaching
            $assigneeUser = \App\Models\User::find($userId);
            
            if (!$assigneeUser) {
                return $this->errorResponse('Pengguna tidak ditemukan.', 404);
            }

            // Unassign the task
            $task->assignees()->detach($userId);

            // Log audit untuk pembatalan penugasan
            $this->auditCustom($task, 'unassigned', null, null, "Membatalkan penugasan '{$assigneeUser->name}' dari task '{$task->title}'", request());

            DB::commit();
            return $this->successResponse(
                new TaskResource($task->load('assignees')),
                'Penugasan tugas berhasil dibatalkan.'
            );
        } catch (Exception $e) {
            DB::rollBack();
            $errMessage = $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine();
            return $this->errorResponse($errMessage, $e->getCode());
        }
    }
}
