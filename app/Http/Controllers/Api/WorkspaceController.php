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
    use ApiResponse;

    // workspace
    public function index(Request $request) 
    {
        $user = auth()->user();

        try {
            $query = Workspace::with([
                'projects.projectUsers.user', 
                'projects.tasks', 
                'workspaceUsers.user'
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
                'projects.tasks',
                'workspaceUsers.user'
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

            foreach($data['members'] ?? [] as $key => $value) {
                WorkspaceUser::create([
                    'workspace_id' => $workspace->id,
                    'user_id' => $value['user_id'],
                    'workspace_role_id' => $value['workspace_role_id'],
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
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

            $workspace->update($workspaceData);

            foreach($data['members'] ?? [] as $key => $value) {
                $workspaceUser = WorkspaceUser::where('workspace_id', $workspace->id)
                    ->where('user_id', $value['user_id'])
                    ->first();

                if ($workspaceUser) {
                    $workspaceUser->update([
                        'workspace_role_id' => $value['workspace_role_id'],
                        'updated_by' => $user->id,
                    ]);
                } else {
                    WorkspaceUser::create([
                        'workspace_id' => $workspace->id,
                        'user_id' => $value['user_id'],
                        'workspace_role_id' => $value['workspace_role_id'],
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
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

            $workspaceUser->update([
                'workspace_role_id' => $data['workspace_role_id'],
                'updated_by' => $user->id,
            ]);

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
}