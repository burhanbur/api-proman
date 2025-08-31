<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserPasswordRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\SystemRole;
use App\Traits\ApiResponse;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use Exception;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $query = User::with(['systemRole', 'workspaceUsers']);

            // Search functionality
            if ($search = $request->query('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                      ->orWhere('email', 'ilike', "%{$search}%")
                      ->orWhere('username', 'ilike', "%{$search}%");
                });
            }

            // Filter by role
            if ($roleId = $request->query('system_role_id')) {
                $query->where('system_role_id', $roleId);
            }

            // Sorting
            $sortParams = $request->query('sort');
            if ($sortParams) {
                $sorts = explode(';', $sortParams);
                $allowedSortFields = ['created_at', 'name', 'email', 'username', 'system_role_id'];
    
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

            return $this->successResponse(UserResource::collection($data));
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function show($uuid)
    {
        try {
            $user = User::with(['systemRole', 'workspaceUsers.workspace'])
                ->where('uuid', $uuid)
                ->first();

            if (!$user) {
                return $this->errorResponse('User tidak ditemukan.', 404);
            }

            return $this->successResponse(new UserResource($user));
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $data['password'] = Hash::make($request->password);
            
            $user = User::create($data);

            DB::commit();
            return $this->successResponse(new UserResource($user));
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function update(UpdateUserRequest $request, $uuid)
    {
        $user = User::where('uuid', $uuid)->first();
        if (!$user) {
            return $this->errorResponse('User tidak ditemukan.', 404);
        }

        $data = $request->validated();

        DB::beginTransaction();

        try {
            if ($request->has('password') && !empty($request->password)) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            DB::commit();
            return $this->successResponse(new UserResource($user));
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function changeMyPassword(UpdateUserPasswordRequest $request, $uuid)
    {
        $user = User::where('uuid', $uuid)->first();
        if (!$user) {
            return $this->errorResponse('User tidak ditemukan.', 404);
        }

        $data = $request->validated();

        DB::beginTransaction();

        try {
            // Optional: Verify old password if provided
            if ($request->has('old_password') && !empty($request->old_password)) {
                if (!Hash::check($request->old_password, $user->password)) {
                    return $this->errorResponse('Password lama tidak sesuai.', 400);
                }
            }

            $data['password'] = Hash::make($request->new_password);
            $user->update(['password' => $data['password']]);

            DB::commit();
            return $this->successResponse(new UserResource($user));
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function destroy($uuid)
    {
        DB::beginTransaction();

        try {
            $user = User::where('uuid', $uuid)->first();
            if (!$user) {
                return $this->errorResponse('User tidak ditemukan atau sudah dihapus.', 404);
            }

            $user->delete();

            DB::commit();
            return $this->successResponse(['message' => 'User berhasil dihapus.']);
        } catch (Exception $ex) {
            DB::rollBack();
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }

    public function me()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return $this->errorResponse('User tidak ditemukan.', 404);
            }

            return $this->successResponse(new UserResource($user));
        } catch (Exception $ex) {
            $errMessage = $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine();
            return $this->errorResponse($errMessage, $ex->getCode());
        }
    }
}
