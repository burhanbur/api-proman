<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuditLog;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Models\Project;
use App\Models\ProjectUser;
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
        $user = auth()->user();

        try {
            $query = AuditLog::with(['user.systemRole']);

            if ($modelType = $request->query('model_type')) {
                $query->where('model_type', $modelType);
            }

            if ($userId = $request->query('user_id')) {
                $query->where('user_id', $userId);
            }

            if ($action = $request->query('action')) {
                $query->where('action', $action);
            }

            if (!in_array($user->systemRole->code, ['admin'])) {
                // Determine which workspaces, projects, tasks, and comments the user has access to
                $workspaceIds = Workspace::whereHas('workspaceUsers', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->pluck('id')->toArray();

                $projectIds = Project::whereHas('projectUsers', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->orWhereIn('workspace_id', $workspaceIds)->pluck('id')->toArray();

                // Tasks inside accessible projects
                $taskIds = \App\Models\Task::whereIn('project_id', $projectIds)->pluck('id')->toArray();

                // Comments that belong to those tasks
                $commentIds = \App\Models\Comment::whereIn('task_id', $taskIds)->pluck('id')->toArray();

                // Build a condition: audit logs where model is one of these models and model_id in allowed ids
                $query->where(function ($q) use ($workspaceIds, $projectIds, $taskIds, $commentIds, $user) {
                    // workspace-related audits
                    if (count($workspaceIds) > 0) {
                        $q->orWhere(function ($q2) use ($workspaceIds) {
                            $q2->where('model_type', 'App\\Models\\Workspace')->whereIn('model_id', $workspaceIds);
                        });
                    }

                    // project-related audits
                    if (count($projectIds) > 0) {
                        $q->orWhere(function ($q2) use ($projectIds) {
                            $q2->where('model_type', 'App\\Models\\Project')->whereIn('model_id', $projectIds);
                        });
                    }

                    // task-related audits
                    if (count($taskIds) > 0) {
                        $q->orWhere(function ($q2) use ($taskIds) {
                            $q2->where('model_type', 'App\\Models\\Task')->whereIn('model_id', $taskIds);
                        });
                    }

                    // comment-related audits
                    if (count($commentIds) > 0) {
                        $q->orWhere(function ($q2) use ($commentIds) {
                            $q2->where('model_type', 'App\\Models\\Comment')->whereIn('model_id', $commentIds);
                        });
                    }

                    // also include audits performed by the user (own actions)
                    $q->orWhere('user_id', $user->id);
                });
            }

            $query->orderBy('created_at', 'desc');

            $limit = intval($request->query('limit', 10));
            $limit = $limit > 0 ? min($limit, 100) : 10; // cap at 100
            $logs = $query->limit($limit)->get();

            return $this->successResponse($logs, 'Data audit logs berhasil diambil.');
        } catch (\Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, 500);
        }
    }
}
