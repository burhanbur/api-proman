<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuditLog;
use App\Traits\ApiResponse;

class AuditLogController extends Controller
{
    use ApiResponse;
    
    /**
     * Return a list of audit logs. Admin only.
     * Supports optional filters: model_type, user_id, action.
     * Query param `limit` controls number of items (default 10).
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            $limit = intval($request->query('limit', 10));
            $limit = $limit > 0 ? min($limit, 100) : 10; // cap at 100

            $query = AuditLog::with(['user.systemRole', 'model'])->orderBy('created_at', 'desc');

            if ($modelType = $request->query('model_type')) {
                $query->where('model_type', $modelType);
            }

            if ($userId = $request->query('user_id')) {
                $query->where('user_id', $userId);
            }

            if ($action = $request->query('action')) {
                $query->where('action', $action);
            }

            $logs = $query->limit($limit)->get();

            return $this->successResponse($logs, 'Data audit logs berhasil diambil.');
        } catch (\Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, 500);
        }
    }
}
