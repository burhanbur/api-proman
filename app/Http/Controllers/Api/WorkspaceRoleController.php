<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\WorkspaceRole;
use App\Models\Workspace;
use App\Traits\ApiResponse;
use App\Traits\HasAuditLog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class WorkspaceRoleController extends Controller
{
    use ApiResponse, HasAuditLog;

    public function index(Request $request) 
    {
        try {
            $query = WorkspaceRole::query();

            // Search functionality
            if ($search = $request->query('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            // Filter by is_active
            if (null !== ($isActive = $request->query('is_active'))) {
                $query->where('is_active', (int) $isActive === 1);
            }

            // Sorting
            $sortParams = $request->query('sort');
            if ($sortParams) {
                $sorts = explode(';', $sortParams);
                $allowedSortFields = ['name', 'code', 'created_at'];
    
                foreach ($sorts as $sort) {
                    [$field, $direction] = explode(',', $sort) + [null, 'asc'];
                    $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
    
                    if (in_array($field, $allowedSortFields)) {
                        $query->orderBy($field, $direction);
                    }
                }
            } else {
                $query->orderBy('name', 'asc');
            }

            $data = $query->get();

            return $this->successResponse(
                $data,
                'Data peran workspace berhasil diambil.'
            );
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function dropdown(Request $request, $slug) 
    {
        try {
            $query = WorkspaceRole::query();

            // Search functionality
            if ($search = $request->query('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            $user = auth()->user()->load(['systemRole']);

            $isSysAdmin = in_array(strtolower($user->systemRole->code ?? ''), ['admin']);

            // If system admin, return all roles (optionally filtered by search/is_active above)
            if ($isSysAdmin) {
                $data = $query->orderBy('name', 'asc')->get();
                return $this->successResponse($data, 'Data peran workspace aktif berhasil diambil.');
            }

            // Non-system-admin: need workspace context to determine what roles to show
            $workspace = Workspace::where('slug', $slug)->first();

            // If no workspace context provided, return empty set for non-admin users
            if (!$workspace) {
                return $this->successResponse(collect(), 'Data peran workspace aktif berhasil diambil.');
            }

            // Find the current user's workspace membership
            $workspaceUser = $workspace->workspaceUsers()->where('user_id', $user->id)->with('workspaceRole')->first();

            if (!$workspaceUser) {
                // Not a member of this workspace -> no roles available
                return $this->successResponse(collect(), 'Data peran workspace aktif berhasil diambil.');
            }

            $roleCode = strtolower($workspaceUser->workspaceRole->code ?? $workspaceUser->role ?? '');

            // Owner: show all
            if (str_contains($roleCode, 'owner')) {
                $data = $query->orderBy('name', 'asc')->get();
                return $this->successResponse($data, 'Data peran workspace aktif berhasil diambil.');
            }

            // Admin: show all except owner
            if (str_contains($roleCode, 'admin')) {
                $data = $query->where(function($q) {
                    $q->where('code', 'not like', '%owner%')
                      ->where('name', 'not like', '%owner%');
                })->orderBy('name', 'asc')->get();

                return $this->successResponse($data, 'Data peran workspace aktif berhasil diambil.');
            }

            // Other roles: none
            return $this->successResponse(collect(), 'Data peran workspace aktif berhasil diambil.');
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function show($id) 
    {
        try {
            $data = WorkspaceRole::with(['workspaceUsers'])->find($id);

            if (!$data) {
                return $this->errorResponse('Peran workspace tidak ditemukan.', 404);
            }

            return $this->successResponse(
                $data, 
                'Data peran workspace berhasil diambil.'
            );
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function store(Request $request) 
    {
        $user = auth()->user();
        
        // Only admin can create workspace roles
        if (!in_array($user->systemRole->code, ['admin'])) {
            return $this->errorResponse('Hanya admin yang dapat membuat peran workspace.', 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:workspace_roles,name',
            'code' => 'required|string|max:50|unique:workspace_roles,code',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['created_by'] = $user->id;
            $data['updated_by'] = $user->id;

            $workspaceRole = WorkspaceRole::create($data);

            // Log audit untuk role yang dibuat
            $this->auditCreated($workspaceRole, "Peran workspace '{$workspaceRole->name}' berhasil dibuat", $request);

            DB::commit();
            return $this->successResponse(
                $workspaceRole,
                'Peran workspace berhasil dibuat.'
            );
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function update(Request $request, $id) 
    {
        $user = auth()->user();
        
        // Only admin can update workspace roles
        if (!in_array($user->systemRole->code, ['admin'])) {
            return $this->errorResponse('Hanya admin yang dapat memperbarui peran workspace.', 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:workspace_roles,name,' . $id,
            'code' => 'required|string|max:50|unique:workspace_roles,code,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $workspaceRole = WorkspaceRole::find($id);

            if (!$workspaceRole) {
                return $this->errorResponse('Peran workspace tidak ditemukan.', 404);
            }

            // Simpan data original untuk audit log
            $originalData = $workspaceRole->toArray();

            $data = $request->validated();
            $data['updated_by'] = $user->id;
            $workspaceRole->update($data);

            // Log audit untuk role yang diupdate
            $this->auditUpdated($workspaceRole, $originalData, "Peran workspace '{$workspaceRole->name}' berhasil diperbarui", $request);

            DB::commit();
            return $this->successResponse(
                $workspaceRole,
                'Peran workspace berhasil diperbarui.'
            );
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function destroy($id) 
    {
        $user = auth()->user();
        
        // Only admin can delete workspace roles
        if (!in_array($user->systemRole->code, ['admin'])) {
            return $this->errorResponse('Hanya admin yang dapat menghapus peran workspace.', 403);
        }

        DB::beginTransaction();

        try {
            $workspaceRole = WorkspaceRole::find($id);
            if (!$workspaceRole) {
                return $this->errorResponse('Peran workspace tidak ditemukan.', 404);
            }

            // Check if role is being used by workspace users
            if ($workspaceRole->workspaceUsers()->count() > 0) {
                return $this->errorResponse('Peran tidak dapat dihapus karena masih digunakan oleh anggota workspace.', 400);
            }

            // Log audit sebelum menghapus
            $this->auditDeleted($workspaceRole, "Peran workspace '{$workspaceRole->name}' berhasil dihapus", request());

            $workspaceRole->delete();

            DB::commit();
            return $this->successResponse(['message' => 'Peran workspace berhasil dihapus.']);
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }
}
