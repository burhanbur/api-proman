<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use App\Models\WorkspaceRole;
use App\Models\WorkspaceUser;
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

    public function index(Request $request) 
    {
        try {
            $query = Workspace::with(['owner', 'createdBy', 'updatedBy', 'deletedBy', 'workspaceUsers', 'projects', 'workspaceRoles'])->whereNull('deleted_at');

            // Search functionality
            if ($search = $request->query('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%");
                });
            }

            // Filter by owner
            if ($ownerId = $request->query('owner_id')) {
                $query->where('owner_id', $ownerId);
            }

            // Filter by status
            if ($status = $request->query('is_active') == 1 ? true : false) {
                $query->where('is_active', $status);
            }

            // Filter by visibility
            if ($status = $request->query('is_public') == 1 ? true : false) {
                $query->where('is_public', $status);
            }

            $sortParams = $request->query('sort');
            if ($sortParams) {
                $sorts = explode(';', $sortParams);
                $allowedSortFields = ['created_at', 'name', 'slug', 'owner_id', 'is_active', 'is_public'];
    
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

            $data = $query->paginate((int) $request->query('limit', 10));

            return $this->successResponse(WorkspaceResource::collection($data));
        } catch (Exception $ex) {
            return $this->errorResponse($ex->getMessage(), $ex->getCode());
        }
    }

    public function show($slug) 
    {
        try {
            $workspace = Workspace::where('slug', $slug)->whereNull('deleted_at')->first();
            if (!$workspace) {
                throw new Exception('Workspace not found or has been deleted.', 404);
            }
            return $this->successResponse(new WorkspaceResource($workspace));
        } catch (Exception $ex) {
            return $this->errorResponse($ex->getMessage(), $ex->getCode());
        }
    }

    public function store(Request $request) 
    {
        DB::beginTransaction();

        try {
            $workspace = Workspace::create($request->all());

            DB::commit();
            
            return $this->successResponse(new WorkspaceResource($workspace));
        } catch (Exception $ex) {
            DB::rollBack();
            return $this->errorResponse($ex->getMessage(), $ex->getCode());
        }
    }

    public function update(Request $request, $slug) 
    {
        DB::beginTransaction();

        try {
            $workspace = Workspace::where('slug', $slug)->first();
            $workspace->update($request->all());

            DB::commit();
            return $this->successResponse(new WorkspaceResource($workspace));
        } catch (Exception $ex) {
            DB::rollBack();
            return $this->errorResponse($ex->getMessage(), $ex->getCode());
        }
    }

    public function destroy($slug) 
    {
        DB::beginTransaction();

        try {
            $workspace = Workspace::where('slug', $slug)->whereNull('deleted_at')->first();
            if (!$workspace) {
                throw new Exception('Workspace not found or already deleted.', 404);
            }

            $workspace->delete();

            DB::commit();
            return $this->successResponse(['message' => 'Workspace deleted successfully.']);
        } catch (Exception $ex) {
            DB::rollBack();
            return $this->errorResponse($ex->getMessage(), $ex->getCode());
        }
    }
}