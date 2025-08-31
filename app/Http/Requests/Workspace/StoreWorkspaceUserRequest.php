<?php

namespace App\Http\Requests\Workspace;

use App\Http\Requests\BaseFormRequest;

class StoreWorkspaceUserRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'workspace_role_id' => 'required|integer|exists:workspace_roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'ID pengguna wajib diisi.',
            'user_id.integer' => 'ID pengguna harus berupa integer.',
            'user_id.exists' => 'ID pengguna tidak valid.',
            'workspace_role_id.required' => 'ID peran workspace wajib diisi.',
            'workspace_role_id.integer' => 'ID peran workspace harus berupa integer.',
            'workspace_role_id.exists' => 'ID peran workspace tidak valid.',
        ];
    }
}
