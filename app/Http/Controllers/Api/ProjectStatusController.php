<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectStatus;
use App\Traits\ApiResponse;
use App\Traits\HasAuditLog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class ProjectStatusController extends Controller
{
    use ApiResponse, HasAuditLog;

    public function index(Request $request) 
    {
        $user = auth()->user();

        try {
            $query = ProjectStatus::with(['project', 'tasks']);

            // Search functionality
            if ($search = $request->query('search')) {
                $query->where('name', 'like', "%{$search}%");
            }

            // Filter by project_id
            if ($projectId = $request->query('project_id')) {
                $query->where('project_id', $projectId);
            }

            // Permission: only show statuses from projects user has access to
            if (!in_array($user->systemRole->code, ['admin'])) {
                $query->whereHas('project.projectUsers', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            // Sorting
            $sortParams = $request->query('sort');
            if ($sortParams) {
                $sorts = explode(';', $sortParams);
                $allowedSortFields = ['name', 'order', 'created_at'];
    
                foreach ($sorts as $sort) {
                    [$field, $direction] = explode(',', $sort) + [null, 'asc'];
                    $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
    
                    if (in_array($field, $allowedSortFields)) {
                        $query->orderBy($field, $direction);
                    }
                }
            } else {
                $query->orderBy('order', 'asc');
            }

            $data = $query->get();

            return $this->successResponse(
                $data,
                'Data status proyek berhasil diambil.'
            );
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function show($id) 
    {
        $user = auth()->user();

        try {
            $query = ProjectStatus::with(['project', 'tasks']);

            // Permission: only show if user has access to project
            if (!in_array($user->systemRole->code, ['admin'])) {
                $query->whereHas('project.projectUsers', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            $data = $query->find($id);

            if (!$data) {
                return $this->errorResponse('Status proyek tidak ditemukan.', 404);
            }

            return $this->successResponse(
                $data, 
                'Data status proyek berhasil diambil.'
            );
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function store(Request $request) 
    {
        $user = auth()->user();
        
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer|min:0',
            'is_default' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $data = $request->validated();

            // Permission: only project members or admin can create status
            if (!in_array($user->systemRole->code, ['admin'])) {
                $project = \App\Models\Project::find($data['project_id']);
                if (!$project || !$project->projectUsers()->where('user_id', $user->id)->exists()) {
                    return $this->errorResponse('Tidak punya izin untuk membuat status di proyek ini.', 403);
                }
            }

            $data['created_by'] = $user->id;
            $data['updated_by'] = $user->id;

            $projectStatus = ProjectStatus::create($data);

            // Log audit untuk status yang dibuat
            $this->auditCreated($projectStatus, "Status proyek '{$projectStatus->name}' berhasil dibuat", $request);

            DB::commit();
            return $this->successResponse(
                $projectStatus,
                'Status proyek berhasil dibuat.'
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
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer|min:0',
            'is_default' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $projectStatus = ProjectStatus::find($id);

            if (!$projectStatus) {
                return $this->errorResponse('Status proyek tidak ditemukan.', 404);
            }

            // Permission: only project members or admin can update
            if (!in_array($user->systemRole->code, ['admin'])) {
                if (!$projectStatus->project || !$projectStatus->project->projectUsers()->where('user_id', $user->id)->exists()) {
                    return $this->errorResponse('Tidak punya izin untuk memperbarui status ini.', 403);
                }
            }

            // Simpan data original untuk audit log
            $originalData = $projectStatus->toArray();

            $data = $request->validated();
            $data['updated_by'] = $user->id;
            $projectStatus->update($data);

            // Log audit untuk status yang diupdate
            $this->auditUpdated($projectStatus, $originalData, "Status proyek '{$projectStatus->name}' berhasil diperbarui", $request);

            DB::commit();
            return $this->successResponse(
                $projectStatus,
                'Status proyek berhasil diperbarui.'
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
        DB::beginTransaction();

        try {
            $projectStatus = ProjectStatus::find($id);
            if (!$projectStatus) {
                return $this->errorResponse('Status proyek tidak ditemukan.', 404);
            }

            // Permission: only project members or admin can delete
            if (!in_array($user->systemRole->code, ['admin'])) {
                if (!$projectStatus->project || !$projectStatus->project->projectUsers()->where('user_id', $user->id)->exists()) {
                    return $this->errorResponse('Tidak punya izin untuk menghapus status ini.', 403);
                }
            }

            // Check if status is being used by tasks
            if ($projectStatus->tasks()->count() > 0) {
                return $this->errorResponse('Status tidak dapat dihapus karena masih digunakan oleh tugas.', 400);
            }

            // Log audit sebelum menghapus
            $this->auditDeleted($projectStatus, "Status proyek '{$projectStatus->name}' berhasil dihapus", request());

            $projectStatus->deleted_by = $user->id;
            $projectStatus->save();
            $projectStatus->delete();

            DB::commit();
            return $this->successResponse(['message' => 'Status proyek berhasil dihapus.']);
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }
}
