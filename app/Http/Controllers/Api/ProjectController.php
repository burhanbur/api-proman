<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Http\Requests\Project\DeleteProjectUserRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\StoreProjectUserRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Project;
use App\Models\ProjectUser;
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

class ProjectController extends Controller
{
    use ApiResponse, HasAuditLog;

    public function index(Request $request) 
    {
        $user = auth()->user();

        try {
            $query = Project::with([
                'workspace',
                'projectUsers.user',
                'tasks.status',
                'tasks.priority',
                'tasks.assignees',
                'tasks.attachments',
                'tasks.comments',
                'projectStatuses',
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
                $query->whereHas('projectUsers', function($q) use ($user) {
                    $q->where('user_id', $user->id);
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
                ProjectResource::collection($data),
                'Data project berhasil diambil.'
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
            $query = Project::with([
                'workspace',
                'projectUsers.user',
                'tasks.status',
                'tasks.priority',
                'tasks.assignees',
                'tasks.attachments',
                'tasks.comments',
                'projectStatuses',
                'attachments',
            ]);

            if (!in_array($user->systemRole->code, ['admin'])) {
                $query->whereHas('projectUsers', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            $data = $query->where('slug', $slug)->first();

            if (!$data) {
                throw new Exception('Proyek tidak ditemukan.', 404);
            }

            return $this->successResponse(
                new ProjectResource($data), 
                'Data proyek berhasil diambil.'
            );
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function store(StoreProjectRequest $request) 
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $slug = Str::slug($request->name);

            while (Project::withTrashed()->where('slug', $slug)->exists()) {
                $slug = $slug . '-' . Str::random(3);
            }

            $projectData = [];
            $projectData['slug'] = $slug;
            $projectData['workspace_id'] = $data['workspace_id'];
            $projectData['name'] = $data['name'];
            $projectData['description'] = $data['description'];
            $projectData['is_active'] = $data['is_active'];
            $projectData['is_public'] = $data['is_public'];
            $projectData['created_by'] = $user->id;
            $projectData['updated_by'] = $user->id;

            $project = Project::create($projectData);

            // Log audit untuk project yang dibuat
            $this->auditCreated($project, "Project '{$project->name}' berhasil dibuat", $request);

            // Auto-add workspace members ke project dengan role mapping
            $memberService = MemberService::getInstance();
            $memberService->autoAddWorkspaceMembersToProject($project);

            // Tambah/update members yang dikirim explicitly (akan override auto-add jika ada)
            foreach($data['members'] ?? [] as $key => $value) {
                $projectUser = ProjectUser::where('project_id', $project->id)
                    ->where('user_id', $value['user_id'])
                    ->first();

                if ($projectUser) {
                    $originalUserData = $projectUser->toArray();
                    $projectUser->update([
                        'project_role_id' => $value['project_role_id'],
                        'updated_by' => $user->id,
                    ]);
                    
                    // Log audit untuk member yang diupdate
                    $this->auditUpdated($projectUser, $originalUserData, "Memperbarui peran anggota di project '{$project->name}'", $request);
                } else {
                    $projectUser = ProjectUser::create([
                        'project_id' => $project->id,
                        'user_id' => $value['user_id'],
                        'project_role_id' => $value['project_role_id'],
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);

                    // Log audit untuk member baru yang ditambahkan
                    $this->auditCreated($projectUser, "Menambahkan anggota ke project '{$project->name}'", $request);
                }
            }

            DB::commit();

            return $this->successResponse(
                new ProjectResource($project), 
                'Proyek berhasil dibuat.'
            );
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function update(UpdateProjectRequest $request, $slug) 
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $projectData = [];
            $projectData['name'] = $data['name'];
            $projectData['description'] = $data['description'];
            $projectData['is_active'] = $data['is_active'];
            $projectData['is_public'] = $data['is_public'];
            $project = Project::where('slug', $slug)->first();

            if (!$project) {
                throw new Exception('Proyek tidak ditemukan.', 404);
            }

            // Simpan data original untuk audit log
            $originalData = $project->toArray();

            $project->update($projectData);

            // Log audit untuk project yang diupdate
            $this->auditUpdated($project, $originalData, "Project '{$project->name}' berhasil diperbarui", $request);

            foreach($data['members'] ?? [] as $key => $value) {
                $projectUser = ProjectUser::where('project_id', $project->id)
                    ->where('user_id', $value['user_id'])
                    ->first();

                if ($projectUser) {
                    $originalUserData = $projectUser->toArray();
                    $projectUser->update([
                        'project_role_id' => $value['project_role_id'],
                        'updated_by' => $user->id,
                    ]);
                    
                    // Log audit untuk member yang diupdate
                    $this->auditUpdated($projectUser, $originalUserData, "Memperbarui peran anggota di project '{$project->name}'", $request);
                } else {
                    $projectUser = ProjectUser::create([
                        'project_id' => $project->id,
                        'user_id' => $value['user_id'],
                        'project_role_id' => $value['project_role_id'],
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);

                    // Log audit untuk member baru yang ditambahkan
                    $this->auditCreated($projectUser, "Menambahkan anggota baru ke project '{$project->name}'", $request);
                }
            }

            DB::commit();
            return $this->successResponse(
                new ProjectResource($project), 
                'Proyek berhasil diperbarui.'
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
            $project = Project::where('slug', $slug)->first();
            if (!$project) {
                throw new Exception('Proyek tidak ditemukan atau sudah dihapus.', 404);
            }

            // Log audit sebelum menghapus
            $this->auditDeleted($project, "Project '{$project->name}' berhasil dihapus", request());

            $project->deleted_by = $user->id;
            $project->save();
            $project->delete();

            DB::commit();
            return $this->successResponse(['message' => 'Proyek berhasil dihapus.']);
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    // project user
    public function storeUser(StoreProjectUserRequest $request, $slug)
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $project = Project::where('slug', $slug)->first();

            if (!$project) {
                throw new Exception('Proyek tidak ditemukan.', 404);
            }

            // Cek permission
            $memberService = MemberService::getInstance();
            if (!$memberService->canManageProjectUser($project->id, $data['user_id'])) {
                throw new Exception('Anda tidak memiliki permission untuk mengelola anggota proyek ini.', 403);
            }

            $existingProjectUser = ProjectUser::where('project_id', $project->id)
                ->where('user_id', $data['user_id'])
                ->first();

            if ($existingProjectUser) {
                throw new Exception('Pengguna sudah ada di proyek.', 400);
            }

            ProjectUser::create([
                'project_id' => $project->id,
                'user_id' => $data['user_id'],
                'project_role_id' => $data['project_role_id'],
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Log audit untuk user yang ditambahkan
            $projectUser = ProjectUser::where('project_id', $project->id)
                ->where('user_id', $data['user_id'])
                ->first();
            $this->auditCreated($projectUser, "Menambahkan pengguna ke project '{$project->name}'", $request);

            DB::commit();
            return $this->successResponse(['message' => 'Pengguna berhasil ditambahkan ke proyek.']);
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function updateUser(StoreProjectUserRequest $request, $slug)
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $project = Project::where('slug', $slug)->first();

            if (!$project) {
                throw new Exception('Proyek tidak ditemukan.', 404);
            }

            // Cek permission
            $memberService = MemberService::getInstance();
            if (!$memberService->canManageProjectUser($project->id, $data['user_id'])) {
                throw new Exception('Anda tidak memiliki permission untuk mengelola anggota proyek ini.', 403);
            }

            $projectUser = ProjectUser::where('project_id', $project->id)
                ->where('user_id', $data['user_id'])
                ->first();

            if (!$projectUser) {
                throw new Exception('Pengguna tidak ditemukan di proyek.', 404);
            }

            // Simpan data original untuk audit log
            $originalUserData = $projectUser->toArray();

            $projectUser->update([
                'project_role_id' => $data['project_role_id'],
                'updated_by' => $user->id,
            ]);

            // Log audit untuk user yang diupdate
            $this->auditUpdated($projectUser, $originalUserData, "Memperbarui peran pengguna di project '{$project->name}'", $request);

            DB::commit();
            return $this->successResponse(['message' => 'Peran pengguna di proyek berhasil diperbarui.']);
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function destroyUser(DeleteProjectUserRequest $request, $slug)
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $project = Project::where('slug', $slug)->first();

            if (!$project) {
                throw new Exception('Proyek tidak ditemukan.', 404);
            }

            // Cek permission
            $memberService = MemberService::getInstance();
            if (!$memberService->canManageProjectUser($project->id, $data['user_id'])) {
                throw new Exception('Anda tidak memiliki permission untuk mengelola anggota proyek ini.', 403);
            }

            $projectUser = ProjectUser::where('project_id', $project->id)
                ->where('user_id', $data['user_id'])
                ->first();

            if (!$projectUser) {
                throw new Exception('Pengguna tidak ditemukan di proyek.', 404);
            }

            // Log audit sebelum menghapus
            $this->auditDeleted($projectUser, "Menghapus pengguna dari project '{$project->name}'", $request);

            $projectUser->delete();

            DB::commit();
            return $this->successResponse(['message' => 'Pengguna berhasil dihapus dari proyek.']);
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
            $project = Project::where('slug', $slug)->first();

            if (!$project) {
                throw new Exception('Proyek tidak ditemukan.', 404);
            }

            // Check user access to project
            if (!in_array($user->systemRole->code, ['admin'])) {
                $hasAccess = $project->projectUsers()
                    ->where('user_id', $user->id)
                    ->exists();

                if (!$hasAccess) {
                    throw new Exception('Anda tidak memiliki akses ke proyek ini.', 403);
                }
            }

            // Gabungkan berbagai aktivitas dari project
            $activities = collect();

            // 1. Aktivitas dari audit_logs untuk entitas dalam project
            $auditLogs = DB::table('audit_logs')
                ->join('users', 'audit_logs.user_id', '=', 'users.id')
                ->where(function($q) use ($project) {
                    // Aktivitas project itu sendiri
                    $q->where(function($subQ) use ($project) {
                        $subQ->where('audit_logs.model_type', 'Project')
                             ->where('audit_logs.model_id', $project->id);
                    })
                    // Aktivitas task dalam project
                    ->orWhere(function($subQ) use ($project) {
                        $subQ->where('audit_logs.model_type', 'Task')
                             ->whereIn('audit_logs.model_id', 
                                $project->tasks()->pluck('id')
                             );
                    })
                    // Aktivitas project users
                    ->orWhere(function($subQ) use ($project) {
                        $subQ->where('audit_logs.model_type', 'ProjectUser')
                             ->whereIn('audit_logs.model_id',
                                $project->projectUsers()->pluck('project_id')
                             );
                    })
                    // Aktivitas comments dalam project
                    ->orWhere(function($subQ) use ($project) {
                        $subQ->where('audit_logs.model_type', 'Comment')
                             ->whereIn('audit_logs.model_id',
                                DB::table('comments')
                                  ->whereIn('task_id', 
                                    $project->tasks()->pluck('id')
                                  )->pluck('id')
                             );
                    });
                })
                ->select([
                    'audit_logs.*',
                    'users.name as user_name',
                    'users.email as user_email'
                ])
                ->orderBy('audit_logs.created_at', 'desc')
                ->limit(30)
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

            // 2. Aktivitas dari task_activity_logs untuk project ini
            $taskActivities = DB::table('task_activity_logs')
                ->join('tasks', 'task_activity_logs.task_id', '=', 'tasks.id')
                ->join('users', 'task_activity_logs.user_id', '=', 'users.id')
                ->where('tasks.project_id', $project->id)
                ->select([
                    'task_activity_logs.*',
                    'users.name as user_name',
                    'users.email as user_email',
                    'tasks.title as task_title'
                ])
                ->orderBy('task_activity_logs.created_at', 'desc')
                ->limit(25)
                ->get()
                ->map(function($activity) use ($project) {
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
                            'name' => $project->name
                        ],
                        'action_text' => $activity->action_text,
                        'created_at' => $activity->created_at,
                        'updated_at' => $activity->updated_at
                    ];
                });

            $activities = $activities->merge($taskActivities);

            // 3. Aktivitas komentar terbaru dalam project
            $commentActivities = DB::table('comments')
                ->join('tasks', 'comments.task_id', '=', 'tasks.id')
                ->join('users', 'comments.created_by', '=', 'users.id')
                ->where('tasks.project_id', $project->id)
                ->whereNull('comments.deleted_at')
                ->select([
                    'comments.*',
                    'users.name as user_name',
                    'users.email as user_email',
                    'tasks.title as task_title'
                ])
                ->orderBy('comments.created_at', 'desc')
                ->limit(15)
                ->get()
                ->map(function($comment) use ($project) {
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
                            'name' => $project->name
                        ],
                        'comment' => Str::limit($comment->comment, 100),
                        'created_at' => $comment->created_at,
                        'updated_at' => $comment->updated_at
                    ];
                });

            $activities = $activities->merge($commentActivities);

            // Sort semua aktivitas berdasarkan created_at terbaru
            $sortedActivities = $activities->sortByDesc('created_at')->take(40)->values();

            return $this->successResponse($sortedActivities, 'Aktivitas proyek berhasil diambil.');

        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function getProjectStatus($slug)
    {
        $user = auth()->user();

        try {
            $project = Project::with(['projectStatuses'])->where('slug', $slug)->first();

            if (!$project) {
                throw new Exception('Proyek tidak ditemukan.', 404);
            }

            // Check access: only admin or project members
            if (!in_array($user->systemRole->code, ['admin'])) {
                $hasAccess = $project->projectUsers()->where('user_id', $user->id)->exists();
                if (!$hasAccess) {
                    throw new Exception('Anda tidak memiliki akses ke proyek ini.', 403);
                }
            }

            // Ambil daftar status untuk project ini
            $statuses = $project->projectStatuses()->orderBy('order', 'asc')->get();

            return $this->successResponse($statuses, 'Daftar status proyek berhasil diambil.');
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }
}
