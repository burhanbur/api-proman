<?php

namespace App\Http\Requests\Project;

use App\Http\Requests\BaseFormRequest;

class StoreProjectUserRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'project_role_id' => 'required|integer|exists:project_roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'ID pengguna wajib diisi.',
            'user_id.integer' => 'ID pengguna harus berupa integer.',
            'user_id.exists' => 'ID pengguna tidak valid.',
            'project_role_id.required' => 'ID peran project wajib diisi.',
            'project_role_id.integer' => 'ID peran project harus berupa integer.',
            'project_role_id.exists' => 'ID peran project tidak valid.',
        ];
    }
}
