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

use Yajra\DataTables\Facades\DataTables;

use Exception;

class WorkspaceController extends Controller
{
    use ApiResponse;

    public function index() {
        try {
            $workspaces = Workspace::all();
            return $this->successResponse(WorkspaceResource::collection($workspaces));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show($slug) {
        try {
            $workspace = Workspace::where('slug', $slug)->first();
            return $this->successResponse(new WorkspaceResource($workspace));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function store(Request $request) {
        try {
            $workspace = Workspace::create($request->all());
            return $this->successResponse(new WorkspaceResource($workspace));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(Request $request, $slug) {
        try {
            $workspace = Workspace::where('slug', $slug)->first();
            $workspace->update($request->all());
            return $this->successResponse(new WorkspaceResource($workspace));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy($slug) {
        try {
            $workspace = Workspace::where('slug', $slug)->first();
            $workspace->delete();
            return $this->successResponse(new WorkspaceResource($workspace));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}