<?php

namespace App\Http\Requests\Workspace;

use App\Http\Requests\BaseFormRequest;

class StoreWorkspaceRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'members' => 'nullable|array',
            'members.*.user_id' => 'required|integer|exists:users,id',
            'members.*.workspace_role_id' => 'required|integer|exists:workspace_roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama workspace wajib diisi.',
            'is_active.boolean' => 'Status aktif harus berupa boolean.',
            'is_public.boolean' => 'Visibilitas harus berupa boolean.',
            'members.array' => 'Anggota harus berupa array.',
            'members.*.user_id.required' => 'ID pengguna anggota wajib diisi.',
            'members.*.user_id.integer' => 'ID pengguna anggota harus berupa integer.',
            'members.*.user_id.exists' => 'ID pengguna anggota tidak valid.',
            'members.*.workspace_role_id.required' => 'ID peran workspace anggota wajib diisi.',
            'members.*.workspace_role_id.integer' => 'ID peran workspace anggota harus berupa integer.',
            'members.*.workspace_role_id.exists' => 'ID peran workspace anggota tidak valid.',
        ];
    }
}
