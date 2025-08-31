<?php

namespace App\Http\Requests\Project;

use App\Http\Requests\BaseFormRequest;

class DeleteProjectUserRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'project_id' => 'required|integer|exists:projects,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'ID pengguna wajib diisi.',
            'user_id.integer' => 'ID pengguna harus berupa integer.',
            'user_id.exists' => 'ID pengguna tidak valid.',
            'project_id.required' => 'ID project wajib diisi.',
            'project_id.integer' => 'ID project harus berupa integer.',
            'project_id.exists' => 'ID project tidak valid.',
        ];
    }
}
