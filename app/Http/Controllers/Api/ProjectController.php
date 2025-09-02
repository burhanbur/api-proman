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
    use ApiResponse;

    public function index(Request $request) 
    {
        $user = auth()->user();

        try {
            $query = Project::with([
                'workspace',
                'projectUsers.user',
                'tasks'
            ]);

            // Search functionality
            if ($search = $request->query('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%");
                    $q->orWhere('slug', 'ilike', "%{$search}%");
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
                'tasks',
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

            while (Project::where('slug', $slug)->exists()) {
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

            // Auto-add workspace members ke project dengan role mapping
            $memberService = MemberService::getInstance();
            $memberService->autoAddWorkspaceMembersToProject($project);

            // Tambah/update members yang dikirim explicitly (akan override auto-add jika ada)
            foreach($data['members'] ?? [] as $key => $value) {
                $projectUser = ProjectUser::where('project_id', $project->id)
                    ->where('user_id', $value['user_id'])
                    ->first();

                if ($projectUser) {
                    $projectUser->update([
                        'project_role_id' => $value['project_role_id'],
                        'updated_by' => $user->id,
                    ]);
                } else {
                    ProjectUser::create([
                        'project_id' => $project->id,
                        'user_id' => $value['user_id'],
                        'project_role_id' => $value['project_role_id'],
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
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

            $project->update($projectData);

            foreach($data['members'] ?? [] as $key => $value) {
                $projectUser = ProjectUser::where('project_id', $project->id)
                    ->where('user_id', $value['user_id'])
                    ->first();

                if ($projectUser) {
                    $projectUser->update([
                        'project_role_id' => $value['project_role_id'],
                        'updated_by' => $user->id,
                    ]);
                } else {
                    ProjectUser::create([
                        'project_id' => $project->id,
                        'user_id' => $value['user_id'],
                        'project_role_id' => $value['project_role_id'],
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
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

            $projectUser->update([
                'project_role_id' => $data['project_role_id'],
                'updated_by' => $user->id,
            ]);

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

            $projectUser->delete();

            DB::commit();
            return $this->successResponse(['message' => 'Pengguna berhasil dihapus dari proyek.']);
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }
}
