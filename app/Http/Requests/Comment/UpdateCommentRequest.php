<?php

namespace App\Http\Requests\Comment;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'comment' => 'required|string|min:1|max:5000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'comment.required' => 'Komentar harus diisi.',
            'comment.string' => 'Komentar harus berupa teks.',
            'comment.min' => 'Komentar minimal 1 karakter.',
            'comment.max' => 'Komentar maksimal 5000 karakter.',
        ];
    }
}
