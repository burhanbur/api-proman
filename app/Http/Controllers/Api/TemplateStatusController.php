<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TemplateStatus;
use App\Traits\ApiResponse;
use App\Traits\HasAuditLog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class TemplateStatusController extends Controller
{
    use ApiResponse, HasAuditLog;

    public function index(Request $request) 
    {
        try {
            $query = TemplateStatus::query();

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
                $allowedSortFields = ['name', 'code', 'order', 'created_at'];
    
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
                'Data template status berhasil diambil.'
            );
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function show($id) 
    {
        try {
            $data = TemplateStatus::find($id);

            if (!$data) {
                return $this->errorResponse('Template status tidak ditemukan.', 404);
            }

            return $this->successResponse(
                $data, 
                'Data template status berhasil diambil.'
            );
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function store(Request $request) 
    {
        $user = auth()->user();
        
        // Only admin can create template statuses
        if (!in_array($user->systemRole->code, ['admin'])) {
            return $this->errorResponse('Hanya admin yang dapat membuat template status.', 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:template_status,name',
            'code' => 'required|string|max:50|unique:template_status,code',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['created_by'] = $user->id;
            $data['updated_by'] = $user->id;

            $templateStatus = TemplateStatus::create($data);

            // Log audit untuk template status yang dibuat
            $this->auditCreated($templateStatus, "Template status '{$templateStatus->name}' berhasil dibuat", $request);

            DB::commit();
            return $this->successResponse(
                $templateStatus,
                'Template status berhasil dibuat.'
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
        
        // Only admin can update template statuses
        if (!in_array($user->systemRole->code, ['admin'])) {
            return $this->errorResponse('Hanya admin yang dapat memperbarui template status.', 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:template_status,name,' . $id,
            'code' => 'required|string|max:50|unique:template_status,code,' . $id,
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $templateStatus = TemplateStatus::find($id);

            if (!$templateStatus) {
                return $this->errorResponse('Template status tidak ditemukan.', 404);
            }

            // Simpan data original untuk audit log
            $originalData = $templateStatus->toArray();

            $data = $request->validated();
            $data['updated_by'] = $user->id;
            $templateStatus->update($data);

            // Log audit untuk template status yang diupdate
            $this->auditUpdated($templateStatus, $originalData, "Template status '{$templateStatus->name}' berhasil diperbarui", $request);

            DB::commit();
            return $this->successResponse(
                $templateStatus,
                'Template status berhasil diperbarui.'
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
        
        // Only admin can delete template statuses
        if (!in_array($user->systemRole->code, ['admin'])) {
            return $this->errorResponse('Hanya admin yang dapat menghapus template status.', 403);
        }

        DB::beginTransaction();

        try {
            $templateStatus = TemplateStatus::find($id);
            if (!$templateStatus) {
                return $this->errorResponse('Template status tidak ditemukan.', 404);
            }

            // Log audit sebelum menghapus
            $this->auditDeleted($templateStatus, "Template status '{$templateStatus->name}' berhasil dihapus", request());

            $templateStatus->delete();

            DB::commit();
            return $this->successResponse(['message' => 'Template status berhasil dihapus.']);
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }
}
