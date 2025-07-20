<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Exception;

class TaskController extends Controller
{
    use ApiResponse;

    public function index() {
        try {
            $tasks = Task::all();
            return $this->successResponse(TaskResource::collection($tasks));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show($id) {
        try {
            $task = Task::find($id);
            if (!$task) {
                return $this->errorResponse('Tugas tidak ditemukan.', 404);
            }
            return $this->successResponse(new TaskResource($task));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function store(Request $request) {
        try {
            $task = Task::create($request->all());
            return $this->successResponse(new TaskResource($task));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(Request $request, $id) {
        try {
            $task = Task::where('id', $id)->first();
            $task->update($request->all());
            return $this->successResponse(new TaskResource($task));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy($id) {
        try {
            $task = Task::find($id);
            if (!$task) {
                return $this->errorResponse('Tugas tidak ditemukan.', 404);
            }
            $task->delete();
            return $this->successResponse(['message' => 'Tugas berhasil dihapus.']);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
