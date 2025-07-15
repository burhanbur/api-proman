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
            $tasks = Task::whereNull('deleted_at')->get();
            return $this->successResponse(TaskResource::collection($tasks));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show($id) {
        try {
            $task = Task::where('id', $id)->whereNull('deleted_at')->first();
            if (!$task) {
                return $this->errorResponse('Task not found or has been deleted.', 404);
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
            $task = Task::where('id', $id)->whereNull('deleted_at')->first();
            if (!$task) {
                return $this->errorResponse('Task not found or already deleted.', 404);
            }
            $task->delete();
            return $this->successResponse(['message' => 'Task deleted successfully.']);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
