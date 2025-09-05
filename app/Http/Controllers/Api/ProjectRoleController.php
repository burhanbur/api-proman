<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectRole;
use App\Traits\ApiResponse;
use App\Traits\HasAuditLog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class ProjectRoleController extends Controller
{
    use ApiResponse, HasAuditLog;

    public function index(Request $request) 
    {
        $user = auth()->user();

        try {
            $query = ProjectRole::with(['projectUsers']);

            // Search functionality
            if ($search = $request->query('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                      ->orWhere('code', 'ilike', "%{$search}%");
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
                'Data peran proyek berhasil diambil.'
            );
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function show($id) 
    {
        try {
            $data = ProjectRole::with(['projectUsers'])->find($id);

            if (!$data) {
                return $this->errorResponse('Peran proyek tidak ditemukan.', 404);
            }

            return $this->successResponse(
                $data, 
                'Data peran proyek berhasil diambil.'
            );
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function store(Request $request) 
    {
        $user = auth()->user();
        
        // Only admin can create project roles
        if (!in_array($user->systemRole->code, ['admin'])) {
            return $this->errorResponse('Hanya admin yang dapat membuat peran proyek.', 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:project_roles,name',
            'code' => 'required|string|max:50|unique:project_roles,code',
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

            $projectRole = ProjectRole::create($data);

            // Log audit untuk role yang dibuat
            $this->auditCreated($projectRole, "Peran proyek '{$projectRole->name}' berhasil dibuat", $request);

            DB::commit();
            return $this->successResponse(
                $projectRole,
                'Peran proyek berhasil dibuat.'
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
        
        // Only admin can update project roles
        if (!in_array($user->systemRole->code, ['admin'])) {
            return $this->errorResponse('Hanya admin yang dapat memperbarui peran proyek.', 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:project_roles,name,' . $id,
            'code' => 'required|string|max:50|unique:project_roles,code,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $projectRole = ProjectRole::find($id);

            if (!$projectRole) {
                return $this->errorResponse('Peran proyek tidak ditemukan.', 404);
            }

            // Simpan data original untuk audit log
            $originalData = $projectRole->toArray();

            $data = $request->validated();
            $data['updated_by'] = $user->id;
            $projectRole->update($data);

            // Log audit untuk role yang diupdate
            $this->auditUpdated($projectRole, $originalData, "Peran proyek '{$projectRole->name}' berhasil diperbarui", $request);

            DB::commit();
            return $this->successResponse(
                $projectRole,
                'Peran proyek berhasil diperbarui.'
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
        
        // Only admin can delete project roles
        if (!in_array($user->systemRole->code, ['admin'])) {
            return $this->errorResponse('Hanya admin yang dapat menghapus peran proyek.', 403);
        }

        DB::beginTransaction();

        try {
            $projectRole = ProjectRole::find($id);
            if (!$projectRole) {
                return $this->errorResponse('Peran proyek tidak ditemukan.', 404);
            }

            // Check if role is being used by project users
            if ($projectRole->projectUsers()->count() > 0) {
                return $this->errorResponse('Peran tidak dapat dihapus karena masih digunakan oleh anggota proyek.', 400);
            }

            // Log audit sebelum menghapus
            $this->auditDeleted($projectRole, "Peran proyek '{$projectRole->name}' berhasil dihapus", request());

            $projectRole->delete();

            DB::commit();
            return $this->successResponse(['message' => 'Peran proyek berhasil dihapus.']);
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }
}
