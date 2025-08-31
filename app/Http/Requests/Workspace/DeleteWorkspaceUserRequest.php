<?php

namespace App\Http\Requests\Workspace;

use App\Http\Requests\BaseFormRequest;

class DeleteWorkspaceUserRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'workspace_id' => 'required|integer|exists:workspaces,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'ID pengguna wajib diisi.',
            'user_id.integer' => 'ID pengguna harus berupa integer.',
            'user_id.exists' => 'ID pengguna tidak valid.',
            'workspace_role_id.required' => 'ID peran workspace wajib diisi.',
            'workspace_id.integer' => 'ID workspace harus berupa integer.',
            'workspace_id.exists' => 'ID workspace tidak valid.',
        ];
    }
}
