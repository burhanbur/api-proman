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
            $query = Workspace::with(['owner', 'createdBy', 'updatedBy', 'deletedBy', 'workspaceUsers', 'projects', 'workspaceRoles']);

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
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function show($slug) 
    {
        try {
            $workspace = Workspace::where('slug', $slug)->first();
            if (!$workspace) {
                throw new Exception('Workspace not found or has been deleted.', 404);
            }
            return $this->successResponse(new WorkspaceResource($workspace));
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function store(Request $request) 
    {
        $user = auth()->user();
        $rules = [
            'name' => 'required|string|max:255',
            'owner_id' => 'required|exists:users,id',
            'is_active' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
        ];

        $ruleMessages = [
            'name.required' => 'Nama workspace wajib diisi.',
            'owner_id.required' => 'Pemilik workspace wajib diisi.',
            'owner_id.exists' => 'Pemilik workspace tidak ditemukan.',
            'is_active.boolean' => 'Status aktif harus berupa boolean.',
            'is_public.boolean' => 'Visibilitas harus berupa boolean.',
        ];

        $validator = Validator::make($request->all(), $rules, $ruleMessages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();

        try {
            $params = $request->all();
            $slug = Str::slug($request->name);

            while (Workspace::where('slug', $slug)->exists()) {
                $slug = $slug . '-' . Str::random(3);
            }

            $params['slug'] = $slug;
            $params['created_by'] = $user->id;
            $params['updated_by'] = $user->id;

            $workspace = Workspace::create($params);

            DB::commit();
            
            return $this->successResponse(new WorkspaceResource($workspace));
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function update(Request $request, $slug) 
    {
        $user = auth()->user();
        $rules = [
            'name' => 'required|string|max:255',
            'owner_id' => 'required|exists:users,id',
            'is_active' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
        ];

        $ruleMessages = [
            'name.required' => 'Nama workspace wajib diisi.',
            'owner_id.required' => 'Pemilik workspace wajib diisi.',
            'owner_id.exists' => 'Pemilik workspace tidak ditemukan.',
            'is_active.boolean' => 'Status aktif harus berupa boolean.',
            'is_public.boolean' => 'Visibilitas harus berupa boolean.',
        ];

        $validator = Validator::make($request->all(), $rules, $ruleMessages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();

        try {
            $workspace = Workspace::where('slug', $slug)->first();
            $workspace->update($request->all());

            DB::commit();
            return $this->successResponse(new WorkspaceResource($workspace));
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function destroy($slug) 
    {
        DB::beginTransaction();

        try {
            $workspace = Workspace::where('slug', $slug)->first();
            if (!$workspace) {
                throw new Exception('Workspace not found or already deleted.', 404);
            }

            $workspace->delete();

            DB::commit();
            return $this->successResponse(['message' => 'Workspace deleted successfully.']);
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }
}