<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Priority;
use App\Traits\ApiResponse;
use App\Traits\HasAuditLog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class PriorityController extends Controller
{
    use ApiResponse, HasAuditLog;

    public function index(Request $request) 
    {
        try {
            $query = Priority::query();

            // Search functionality
            if ($search = $request->query('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
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
                $allowedSortFields = ['name', 'level', 'created_at'];
    
                foreach ($sorts as $sort) {
                    [$field, $direction] = explode(',', $sort) + [null, 'asc'];
                    $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
    
                    if (in_array($field, $allowedSortFields)) {
                        $query->orderBy($field, $direction);
                    }
                }
            } else {
                $query->orderBy('level', 'asc');
            }

            $data = $query->get();

            return $this->successResponse(
                $data,
                'Data prioritas berhasil diambil.'
            );
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function show($id) 
    {
        try {
            $data = Priority::find($id);

            if (!$data) {
                return $this->errorResponse('Prioritas tidak ditemukan.', 404);
            }

            return $this->successResponse(
                $data, 
                'Data prioritas berhasil diambil.'
            );
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function store(Request $request) 
    {
        $user = auth()->user();
        
        // Only admin can create priorities
        if (!in_array($user->systemRole->code, ['admin'])) {
            return $this->errorResponse('Hanya admin yang dapat membuat prioritas.', 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:priorities,name',
            'level' => 'required|integer|min:1|max:10',
            'color' => 'nullable|string|max:7'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['created_by'] = $user->id;
            $data['updated_by'] = $user->id;

            $priority = Priority::create($data);

            // Log audit untuk prioritas yang dibuat
            $this->auditCreated($priority, "Prioritas '{$priority->name}' berhasil dibuat", $request);

            DB::commit();
            return $this->successResponse(
                $priority,
                'Prioritas berhasil dibuat.'
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
        
        // Only admin can update priorities
        if (!in_array($user->systemRole->code, ['admin'])) {
            return $this->errorResponse('Hanya admin yang dapat memperbarui prioritas.', 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:priorities,name,' . $id,
            'level' => 'required|integer|min:1|max:10',
            'color' => 'nullable|string|max:7',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        DB::beginTransaction();

        try {
            $priority = Priority::find($id);

            if (!$priority) {
                return $this->errorResponse('Prioritas tidak ditemukan.', 404);
            }

            // Simpan data original untuk audit log
            $originalData = $priority->toArray();

            $data = $request->validated();
            $data['updated_by'] = $user->id;
            $priority->update($data);

            // Log audit untuk prioritas yang diupdate
            $this->auditUpdated($priority, $originalData, "Prioritas '{$priority->name}' berhasil diperbarui", $request);

            DB::commit();
            return $this->successResponse(
                $priority,
                'Prioritas berhasil diperbarui.'
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
        
        // Only admin can delete priorities
        if (!in_array($user->systemRole->code, ['admin'])) {
            return $this->errorResponse('Hanya admin yang dapat menghapus prioritas.', 403);
        }

        DB::beginTransaction();

        try {
            $priority = Priority::find($id);
            if (!$priority) {
                return $this->errorResponse('Prioritas tidak ditemukan.', 404);
            }

            // Check if priority is being used by tasks
            if ($priority->tasks()->count() > 0) {
                return $this->errorResponse('Prioritas tidak dapat dihapus karena masih digunakan oleh tugas.', 400);
            }

            // Log audit sebelum menghapus
            $this->auditDeleted($priority, "Prioritas '{$priority->name}' berhasil dihapus", request());

            $priority->delete();

            DB::commit();
            return $this->successResponse(['message' => 'Prioritas berhasil dihapus.']);
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }
}
