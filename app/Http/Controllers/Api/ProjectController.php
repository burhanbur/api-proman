<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Exception;

class ProjectController extends Controller
{
    use ApiResponse;

    public function index() {
        try {
            $projects = Project::all();
            return $this->successResponse(ProjectResource::collection($projects));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show($id) {
        try {
            $project = Project::find($id);
            if (!$project) {
                return $this->errorResponse('Proyek tidak ditemukan.', 404);
            }
            return $this->successResponse(new ProjectResource($project));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function store(Request $request) {
        try {
            $project = Project::create($request->all());
            return $this->successResponse(new ProjectResource($project));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(Request $request, $id) {
        try {
            $project = Project::where('id', $id)->first();
            $project->update($request->all());
            return $this->successResponse(new ProjectResource($project));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy($id) {
        try {
            $project = Project::find($id);
            if (!$project) {
                return $this->errorResponse('Proyek tidak ditemukan.', 404);
            }
            $project->delete();
            return $this->successResponse(['message' => 'Proyek berhasil dihapus.']);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
