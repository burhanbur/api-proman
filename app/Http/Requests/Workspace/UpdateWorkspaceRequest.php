<?php

namespace App\Http\Requests\Workspace;

use App\Http\Requests\BaseFormRequest;
use \App\Models\Workspace;

class UpdateWorkspaceRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'is_active' => 'sometimes|nullable|boolean',
            'is_public' => 'sometimes|nullable|boolean',
            'members' => 'sometimes|nullable|array',
            'members.*.user_id' => 'required_with:members|integer|exists:users,id',
            'members.*.workspace_role_id' => 'required_with:members|integer|exists:workspace_roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama workspace wajib diisi.',
            'name.string' => 'Nama workspace harus berupa teks.',
            'name.max' => 'Nama workspace maksimal 255 karakter.',
            'is_active.boolean' => 'Status aktif harus berupa boolean.',
            'is_public.boolean' => 'Visibilitas harus berupa boolean.',
            'members.array' => 'Anggota harus berupa array.',
            'members.*.user_id.required_with' => 'ID pengguna anggota wajib diisi ketika mengirim data anggota.',
            'members.*.user_id.integer' => 'ID pengguna anggota harus berupa integer.',
            'members.*.user_id.exists' => 'ID pengguna anggota tidak valid.',
            'members.*.workspace_role_id.required_with' => 'ID peran workspace anggota wajib diisi ketika mengirim data anggota.',
            'members.*.workspace_role_id.integer' => 'ID peran workspace anggota harus berupa integer.',
            'members.*.workspace_role_id.exists' => 'ID peran workspace anggota tidak valid.',
        ];
    }
}
