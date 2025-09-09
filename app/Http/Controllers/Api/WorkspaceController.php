<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceResource;
use App\Http\Requests\Workspace\DeleteWorkspaceUserRequest;
use App\Http\Requests\Workspace\StoreWorkspaceRequest;
use App\Http\Requests\Workspace\StoreWorkspaceUserRequest;
use App\Http\Requests\Workspace\UpdateWorkspaceRequest;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Services\MemberService;
use App\Traits\ApiResponse;
use App\Traits\HasAuditLog;

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

class WorkspaceController extends Controller
{
    use ApiResponse, HasAuditLog;

    // workspace
    public function index(Request $request) 
    {
        $user = auth()->user();

        try {
            $query = Workspace::with([
                'projects.projectUsers.user', 
                'projects.tasks.status', 
                'projects.tasks.priority', 
                'projects.tasks.assignees', 
                'projects.tasks.attachments', 
                'projects.tasks.comments', 
                'workspaceUsers.user',
                'attachments',
            ]);

            // Search functionality
            if ($search = $request->query('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                    $q->orWhere('slug', 'like', "%{$search}%");
                });
            }

            // Filter by status
            if ($status = $request->query('is_active') == 1 ? true : false) {
                $query->where('is_active', $status);
            }

            // Filter by visibility
            if ($status = $request->query('is_public') == 1 ? true : false) {
                $query->where('is_public', $status);
            }

            if (!in_array($user->systemRole->code, ['admin'])) {
                // Show workspaces where the user is either a workspace member
                // OR a member of at least one project inside the workspace
                $query->where(function($q) use ($user) {
                    $q->whereHas('workspaceUsers', function($q2) use ($user) {
                        $q2->where('user_id', $user->id);
                    })->orWhereHas('projects.projectUsers', function($q3) use ($user) {
                        $q3->where('user_id', $user->id);
                    });
                });
            }

            $sortParams = $request->query('sort');
            if ($sortParams) {
                $sorts = explode(';', $sortParams);
                $allowedSortFields = ['created_at', 'name', 'slug', 'is_active', 'is_public'];
    
                foreach ($sorts as $sort) {
                    [$field, $direction] = explode(',', $sort) + [null, 'asc'];
                    $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
    
                    if (in_array($field, $allowedSortFields)) {
                        $query->orderBy($field, $direction);
                    } else {
                        $query->orderBy('name');
                    }
                }
            } else {
                $query->orderBy('name');
            }

            // $data = $query->paginate((int) $request->query('limit', 10));
            $data = $query->get();

            return $this->successResponse(
                WorkspaceResource::collection($data), 
                'Data workspace berhasil diambil.'
            );
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function show($slug) 
    {
        $user = auth()->user();

        try {
            $query = Workspace::with([
                'projects.projectUsers.user',
                'projects.tasks.status', 
                'projects.tasks.priority', 
                'projects.tasks.assignees', 
                'projects.tasks.attachments', 
                'projects.tasks.comments', 
                'workspaceUsers.user',
                'attachments',
            ]);

            if (!in_array($user->systemRole->code, ['admin'])) {
                // Allow access if user is workspace member OR member of any project in the workspace
                $query->where(function($q) use ($user) {
                    $q->whereHas('workspaceUsers', function($q2) use ($user) {
                        $q2->where('user_id', $user->id);
                    })->orWhereHas('projects.projectUsers', function($q3) use ($user) {
                        $q3->where('user_id', $user->id);
                    });
                });
            }

            $data = $query->where('slug', $slug)->first();

            if (!$data) {
                throw new Exception('Workspace tidak ditemukan.', 404);
            }

            return $this->successResponse(
                new WorkspaceResource($data),
                'Data workspace berhasil diambil.'
            );
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function store(StoreWorkspaceRequest $request) 
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $slug = Str::slug($request->name);

            while (Workspace::where('slug', $slug)->exists()) {
                $slug = $slug . '-' . Str::random(3);
            }

            $workspaceData = [];
            $workspaceData['name'] = $data['name'];
            $workspaceData['is_active'] = $data['is_active'];
            $workspaceData['is_public'] = $data['is_public'];
            $workspaceData['slug'] = $slug;
            $workspaceData['created_by'] = $user->id;
            $workspaceData['updated_by'] = $user->id;

            $workspace = Workspace::create($workspaceData);

            // Log audit untuk workspace yang dibuat
            $this->auditCreated($workspace, "Workspace '{$workspace->name}' berhasil dibuat", $request);

            foreach($data['members'] ?? [] as $key => $value) {
                $workspaceUser = WorkspaceUser::create([
                    'workspace_id' => $workspace->id,
                    'user_id' => $value['user_id'],
                    'workspace_role_id' => $value['workspace_role_id'],
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                // Log audit untuk setiap member yang ditambahkan
                $this->auditCreated($workspaceUser, "Menambahkan anggota ke workspace '{$workspace->name}'", $request);
            }

            // Sync project users untuk workspace baru
            if (count($data['members'] ?? []) > 0) {
                $memberService = MemberService::getInstance();
                $memberService->syncProjectUsersFromWorkspace($workspace->id);
            }

            DB::commit();

            return $this->successResponse(
                new WorkspaceResource($workspace), 
                'Workspace berhasil dibuat.'
            );
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function update(UpdateWorkspaceRequest $request, $slug) 
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $workspaceData = [];
            $workspaceData['name'] = $data['name'];
            $workspaceData['is_active'] = $data['is_active'];
            $workspaceData['is_public'] = $data['is_public'];
            $workspace = Workspace::where('slug', $slug)->first();

            if (!$workspace) {
                throw new Exception('Workspace tidak ditemukan.', 404);
            }

            // Simpan data original untuk audit log
            $originalData = $workspace->toArray();

            $workspace->update($workspaceData);

            // Log audit untuk workspace yang diupdate
            $this->auditUpdated($workspace, $originalData, "Workspace '{$workspace->name}' berhasil diperbarui", $request);

            foreach($data['members'] ?? [] as $key => $value) {
                $workspaceUser = WorkspaceUser::where('workspace_id', $workspace->id)
                    ->where('user_id', $value['user_id'])
                    ->first();

                if ($workspaceUser) {
                    $originalUserData = $workspaceUser->toArray();
                    $workspaceUser->update([
                        'workspace_role_id' => $value['workspace_role_id'],
                        'updated_by' => $user->id,
                    ]);
                    
                    // Log audit untuk member yang diupdate
                    $this->auditUpdated($workspaceUser, $originalUserData, "Memperbarui peran anggota di workspace '{$workspace->name}'", $request);
                } else {
                    $workspaceUser = WorkspaceUser::create([
                        'workspace_id' => $workspace->id,
                        'user_id' => $value['user_id'],
                        'workspace_role_id' => $value['workspace_role_id'],
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);

                    // Log audit untuk member baru yang ditambahkan
                    $this->auditCreated($workspaceUser, "Menambahkan anggota baru ke workspace '{$workspace->name}'", $request);
                }
            }

            // Sync project users setelah update workspace members
            $memberService = MemberService::getInstance();
            $memberService->syncProjectUsersFromWorkspace($workspace->id);

            DB::commit();
            return $this->successResponse(
                new WorkspaceResource($workspace), 
                'Workspace berhasil diperbarui.'
            );
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function destroy($slug) 
    {
        $user = auth()->user();
        DB::beginTransaction();

        try {
            $workspace = Workspace::where('slug', $slug)->first();
            if (!$workspace) {
                throw new Exception('Workspace tidak ditemukan atau sudah dihapus.', 404);
            }

            // Log audit sebelum menghapus
            $this->auditDeleted($workspace, "Workspace '{$workspace->name}' berhasil dihapus", request());

            $workspace->deleted_by = $user->id;
            $workspace->save();
            $workspace->delete();

            DB::commit();
            return $this->successResponse(['message' => 'Workspace berhasil dihapus.']);
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    // workspace user
    public function storeUser(StoreWorkspaceUserRequest $request, $slug)
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $workspace = Workspace::where('slug', $slug)->first();

            if (!$workspace) {
                throw new Exception('Workspace tidak ditemukan.', 404);
            }

            WorkspaceUser::create([
                'workspace_id' => $workspace->id,
                'user_id' => $data['user_id'],
                'workspace_role_id' => $data['workspace_role_id'],
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Log audit untuk user yang ditambahkan
            $workspaceUser = WorkspaceUser::where('workspace_id', $workspace->id)
                ->where('user_id', $data['user_id'])
                ->first();
            $this->auditCreated($workspaceUser, "Menambahkan pengguna ke workspace '{$workspace->name}'", $request);

            // Sync user ke semua project di workspace
            $memberService = MemberService::getInstance();
            $memberService->syncUserAddedToWorkspace($workspace->id, $data['user_id'], $data['workspace_role_id']);

            DB::commit();
            return $this->successResponse(['message' => 'Pengguna berhasil ditambahkan ke workspace.']);
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function updateUser(StoreWorkspaceUserRequest $request, $slug)
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $workspace = Workspace::where('slug', $slug)->first();

            if (!$workspace) {
                throw new Exception('Workspace tidak ditemukan.', 404);
            }

            $workspaceUser = WorkspaceUser::where('workspace_id', $workspace->id)
                ->where('user_id', $data['user_id'])
                ->first();

            if (!$workspaceUser) {
                throw new Exception('Pengguna tidak ditemukan di workspace.', 404);
            }

            // Simpan data original untuk audit log
            $originalUserData = $workspaceUser->toArray();

            $workspaceUser->update([
                'workspace_role_id' => $data['workspace_role_id'],
                'updated_by' => $user->id,
            ]);

            // Log audit untuk user yang diupdate
            $this->auditUpdated($workspaceUser, $originalUserData, "Memperbarui peran pengguna di workspace '{$workspace->name}'", $request);

            // Sync perubahan role ke project users
            $memberService = MemberService::getInstance();
            $memberService->syncProjectUsersFromWorkspace($workspace->id);

            DB::commit();
            return $this->successResponse(['message' => 'Peran pengguna di workspace berhasil diperbarui.']);
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function destroyUser(DeleteWorkspaceUserRequest $request, $slug)
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $workspace = Workspace::where('slug', $slug)->first();

            if (!$workspace) {
                throw new Exception('Workspace tidak ditemukan.', 404);
            }

            $workspaceUser = WorkspaceUser::where('workspace_id', $workspace->id)
                ->where('user_id', $data['user_id'])
                ->first();

            if (!$workspaceUser) {
                throw new Exception('Pengguna tidak ditemukan di workspace.', 404);
            }

            // Log audit sebelum menghapus
            $this->auditDeleted($workspaceUser, "Menghapus pengguna dari workspace '{$workspace->name}'", $request);

            $workspaceUser->delete();

            // Hapus user dari semua project di workspace
            $memberService = MemberService::getInstance();
            $memberService->syncUserRemovedFromWorkspace($workspace->id, $data['user_id']);

            DB::commit();
            return $this->successResponse(['message' => 'Pengguna berhasil dihapus dari workspace.']);
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function getActivities($slug)
    {
        $user = auth()->user();

        try {
            $workspace = Workspace::where('slug', $slug)->first();

            if (!$workspace) {
                throw new Exception('Workspace tidak ditemukan.', 404);
            }

            // Check user access to workspace
            if (!in_array($user->systemRole->code, ['admin'])) {
                $hasAccess = $workspace->workspaceUsers()
                    ->where('user_id', $user->id)
                    ->exists() || 
                    $workspace->projects()
                    ->whereHas('projectUsers', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->exists();

                if (!$hasAccess) {
                    throw new Exception('Anda tidak memiliki akses ke workspace ini.', 403);
                }
            }

            // Gabungkan berbagai aktivitas dari workspace
            $activities = collect();

            // Determine project scope: admins see all, others see only projects they belong to
            $isAdmin = in_array($user->systemRole->code, ['admin']);
            if ($isAdmin) {
                $projectIds = $workspace->projects()->pluck('id')->toArray();
            } else {
                $projectIds = $workspace->projects()
                    ->whereHas('projectUsers', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->pluck('id')->toArray();

                // If user does not belong to any project in this workspace, return empty activities
                if (empty($projectIds)) {
                    return $this->successResponse(collect(), 'Aktivitas workspace berhasil diambil.');
                }
            }

            // 1. Aktivitas dari audit_logs untuk entitas dalam workspace
            // For non-admins, only include audit logs for projects/tasks in $projectIds
            $taskIds = DB::table('tasks')->whereIn('project_id', $projectIds)->pluck('id')->toArray();

            $auditLogsQuery = DB::table('audit_logs')
                ->join('users', 'audit_logs.user_id', '=', 'users.id')
                ->select([
                    'audit_logs.*',
                    'users.name as user_name',
                    'users.email as user_email'
                ]);

            if ($isAdmin) {
                // admin: keep original workspace-scoped logic
                $auditLogsQuery->where(function($q) use ($workspace) {
                    $q->where(function($subQ) use ($workspace) {
                        $subQ->where('audit_logs.model_type', 'Project')
                             ->whereIn('audit_logs.model_id', $workspace->projects()->pluck('id'));
                    })->orWhere(function($subQ) use ($workspace) {
                        $subQ->where('audit_logs.model_type', 'Task')
                             ->whereIn('audit_logs.model_id', DB::table('tasks')->whereIn('project_id', $workspace->projects()->pluck('id'))->pluck('id'));
                    })->orWhere(function($subQ) use ($workspace) {
                        $subQ->where('audit_logs.model_type', 'Workspace')
                             ->where('audit_logs.model_id', $workspace->id);
                    });
                });
            } else {
                // non-admin: restrict to project/task logs only
                $auditLogsQuery->where(function($q) use ($projectIds, $taskIds) {
                    $q->where(function($subQ) use ($projectIds) {
                        $subQ->where('audit_logs.model_type', 'Project')
                             ->whereIn('audit_logs.model_id', $projectIds);
                    })->orWhere(function($subQ) use ($taskIds) {
                        $subQ->where('audit_logs.model_type', 'Task')
                             ->whereIn('audit_logs.model_id', $taskIds);
                    });
                });
            }

            $auditLogs = $auditLogsQuery->orderBy('audit_logs.created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function($log) {
                    return [
                        'type' => 'audit',
                        'id' => $log->id,
                        'user' => [
                            'id' => $log->user_id,
                            'name' => $log->user_name,
                            'email' => $log->user_email
                        ],
                        'action' => $log->action,
                        'model_type' => $log->model_type,
                        'model_id' => $log->model_id,
                        'message' => $log->message,
                        'before' => $log->before ? json_decode($log->before, true) : null,
                        'after' => $log->after ? json_decode($log->after, true) : null,
                        'created_at' => $log->created_at,
                        'updated_at' => $log->updated_at
                    ];
                });

            $activities = $activities->merge($auditLogs);

            // 2. Aktivitas dari task_activity_logs
            $taskActivities = DB::table('task_activity_logs')
                ->join('tasks', 'task_activity_logs.task_id', '=', 'tasks.id')
                ->join('projects', 'tasks.project_id', '=', 'projects.id')
                ->join('users', 'task_activity_logs.user_id', '=', 'users.id')
                ->whereIn('projects.id', $projectIds)
                ->select([
                    'task_activity_logs.*',
                    'users.name as user_name',
                    'users.email as user_email',
                    'tasks.title as task_title',
                    'projects.name as project_name'
                ])
                ->orderBy('task_activity_logs.created_at', 'desc')
                ->limit(30)
                ->get()
                ->map(function($activity) {
                    return [
                        'type' => 'task_activity',
                        'id' => $activity->id,
                        'user' => [
                            'id' => $activity->user_id,
                            'name' => $activity->user_name,
                            'email' => $activity->user_email
                        ],
                        'task' => [
                            'id' => $activity->task_id,
                            'title' => $activity->task_title
                        ],
                        'project' => [
                            'name' => $activity->project_name
                        ],
                        'action_text' => $activity->action_text,
                        'created_at' => $activity->created_at,
                        'updated_at' => $activity->updated_at
                    ];
                });

            $activities = $activities->merge($taskActivities);

            // 3. Aktivitas komentar terbaru
            $commentActivities = DB::table('comments')
                ->join('tasks', 'comments.task_id', '=', 'tasks.id')
                ->join('projects', 'tasks.project_id', '=', 'projects.id')
                ->join('users', 'comments.created_by', '=', 'users.id')
                ->whereIn('projects.id', $projectIds)
                ->whereNull('comments.deleted_at')
                ->select([
                    'comments.*',
                    'users.name as user_name',
                    'users.email as user_email',
                    'tasks.title as task_title',
                    'projects.name as project_name'
                ])
                ->orderBy('comments.created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function($comment) {
                    return [
                        'type' => 'comment',
                        'id' => $comment->id,
                        'user' => [
                            'id' => $comment->created_by,
                            'name' => $comment->user_name,
                            'email' => $comment->user_email
                        ],
                        'task' => [
                            'id' => $comment->task_id,
                            'title' => $comment->task_title
                        ],
                        'project' => [
                            'name' => $comment->project_name
                        ],
                        'comment' => Str::limit($comment->comment, 100),
                        'created_at' => $comment->created_at,
                        'updated_at' => $comment->updated_at
                    ];
                });

            $activities = $activities->merge($commentActivities);

            // Sort semua aktivitas berdasarkan created_at terbaru
            $sortedActivities = $activities->sortByDesc('created_at')->take(50)->values();

            return $this->successResponse($sortedActivities, 'Aktivitas workspace berhasil diambil.');

        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }
}