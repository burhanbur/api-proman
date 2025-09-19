<?php

namespace App\Http\Requests\Project;

use App\Http\Requests\BaseFormRequest;

class UpdateProjectRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'workspace_id' => 'sometimes|required|integer|exists:workspaces,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'logo' => 'sometimes|nullable|file|image|max:5120',
            'is_active' => 'sometimes|nullable|boolean',
            'is_public' => 'sometimes|nullable|boolean',
            'members' => 'sometimes|nullable|array',
            'members.*.user_id' => 'required_with:members|integer|exists:users,id',
            'members.*.project_role_id' => 'required_with:members|integer|exists:project_roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'workspace_id.required' => 'ID workspace wajib diisi.',
            'workspace_id.integer' => 'ID workspace harus berupa integer.',
            'workspace_id.exists' => 'ID workspace tidak valid.',
            'name.required' => 'Nama project wajib diisi.',
            'name.string' => 'Nama project harus berupa teks.',
            'name.max' => 'Nama project maksimal 255 karakter.',
            'description.string' => 'Deskripsi project harus berupa teks.',
            'logo.file' => 'Logo project harus berupa file.',
            'logo.image' => 'Logo project harus berupa gambar.',
            'logo.max' => 'Ukuran logo project maksimal 5MB.',
            'logo.uploaded' => 'Logo project gagal diunggah. Periksa ukuran file (maks 5MB), tipe file, dan konfigurasi server (upload_max_filesize / post_max_size).',
            'is_active.boolean' => 'Status aktif harus berupa boolean.',
            'is_public.boolean' => 'Status publik harus berupa boolean.',
            'members.array' => 'Anggota harus berupa array.',
            'members.*.user_id.required_with' => 'ID pengguna anggota wajib diisi ketika mengirim data anggota.',
            'members.*.user_id.integer' => 'ID pengguna anggota harus berupa integer.',
            'members.*.user_id.exists' => 'ID pengguna anggota tidak valid.',
            'members.*.project_role_id.required_with' => 'ID peran project anggota wajib diisi ketika mengirim data anggota.',
            'members.*.project_role_id.integer' => 'ID peran project anggota harus berupa integer.',
            'members.*.project_role_id.exists' => 'ID peran project anggota tidak valid.',
        ];
    }
}
