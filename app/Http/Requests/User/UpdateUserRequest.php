<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use \App\Models\User;

class UpdateUserRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $uuid = $this->route('uuid'); // Mengambil parameter 'uuid' dari route
        
        // Cari user berdasarkan UUID untuk mendapatkan ID
        $user = User::where('uuid', $uuid)->first();
        $userId = $user ? $user->id : null;

        return [
            'username' => 'required|string|max:255|unique:users,username,' . $userId,
            'code' => 'required|string|max:255|unique:users,code,' . $userId,
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $userId,
            'system_role_id' => 'required|exists:system_roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'code.required' => 'Kode wajib diisi.',
            'code.unique' => 'Kode sudah digunakan.',
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'system_role_id.required' => 'Role wajib diisi.',
            'system_role_id.exists' => 'Role tidak ditemukan.',
        ];
    }
}
