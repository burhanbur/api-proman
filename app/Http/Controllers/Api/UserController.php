<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

    public function show($id)
    {
        try {
            $user = User::with(['systemRole', 'workspaceUsers'])
                ->where('id', $id)
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

    public function store(Request $request)
    {
        $rules = [
            'username' => 'required|string|max:255|unique:users,username',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'system_role_id' => 'required|exists:system_roles,id',
        ];

        $ruleMessages = [
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'system_role_id.required' => 'Role wajib diisi.',
            'system_role_id.exists' => 'Role tidak ditemukan.',
        ];

        $validator = Validator::make($request->all(), $rules, $ruleMessages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();

        try {
            $data = $request->all();
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

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse('User tidak ditemukan.', 404);
        }

        $rules = [
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'system_role_id' => 'required|exists:system_roles,id',
        ];

        $ruleMessages = [
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.min' => 'Password minimal 8 karakter.',
            'system_role_id.required' => 'Role wajib diisi.',
            'system_role_id.exists' => 'Role tidak ditemukan.',
        ];

        $validator = Validator::make($request->all(), $rules, $ruleMessages);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();

        try {
            $data = $request->all();
            if ($request->has('password')) {
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

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $user = User::find($id);
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
