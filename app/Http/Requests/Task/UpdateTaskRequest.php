<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\BaseFormRequest;

class UpdateTaskRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority_id' => 'nullable|integer|exists:priorities,id',
            'status_id' => 'nullable|integer|exists:project_status,id',
            'assignees' => 'nullable|array',
            'assignees.*.user_id' => 'required|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.string' => 'Judul tugas harus berupa teks.',
            'title.max' => 'Judul tugas maksimal 255 karakter.',
            'description.string' => 'Deskripsi tugas harus berupa teks.',
            'due_date.date' => 'Tanggal jatuh tempo tidak valid.',
            'priority_id.integer' => 'ID prioritas harus berupa integer.',
            'priority_id.exists' => 'ID prioritas tidak valid.',
            'status_id.integer' => 'ID status harus berupa integer.',
            'status_id.exists' => 'ID status tidak valid.',
            'assignees.array' => 'Penugasan harus berupa array.',
            'assignees.*.user_id.required' => 'ID pengguna penugasan wajib diisi.',
            'assignees.*.user_id.integer' => 'ID pengguna penugasan harus berupa integer.',
            'assignees.*.user_id.exists' => 'ID pengguna penugasan tidak valid.',
        ];
    }
}
