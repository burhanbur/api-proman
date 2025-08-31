<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;

class UpdateUserPasswordRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'old_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8',
            'new_password_confirmation' => 'required|string|same:new_password',
        ];
    }

    public function messages(): array
    {
        return [
            'old_password.required' => 'Password lama wajib diisi.',
            'old_password.min' => 'Password lama harus memiliki minimal 8 karakter.',
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password baru harus memiliki minimal 8 karakter.',
            'new_password_confirmation.required' => 'Konfirmasi password baru wajib diisi.',
            'new_password_confirmation.same' => 'Konfirmasi password baru tidak cocok.',
        ];
    }
}
