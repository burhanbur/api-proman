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
            'point' => 'nullable|integer|min:0',
            'priority_id' => 'nullable|integer|exists:priorities,id',
            'status_id' => 'nullable|integer|exists:project_status,id',
            'assignees' => 'nullable|array',
            'assignees.*.user_id' => 'required|integer|exists:users,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120', // Max 5MB per file
            'related_tasks' => 'nullable|array',
            'related_tasks.*.related_task_id' => 'required|integer|exists:tasks,id',
            'related_tasks.*.relation_type_id' => 'required|integer|exists:task_relation_types,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.string' => 'Judul tugas harus berupa teks.',
            'title.max' => 'Judul tugas maksimal 255 karakter.',
            'description.string' => 'Deskripsi tugas harus berupa teks.',
            'due_date.date' => 'Tanggal jatuh tempo tidak valid.',
            'point.integer' => 'Poin tugas harus berupa angka.',
            'point.min' => 'Poin tugas tidak boleh negatif.',
            'priority_id.integer' => 'ID prioritas harus berupa integer.',
            'priority_id.exists' => 'ID prioritas tidak valid.',
            'status_id.integer' => 'ID status harus berupa integer.',
            'status_id.exists' => 'ID status tidak valid.',
            'assignees.array' => 'Penugasan harus berupa array.',
            'assignees.*.user_id.required' => 'ID pengguna penugasan wajib diisi.',
            'assignees.*.user_id.integer' => 'ID pengguna penugasan harus berupa integer.',
            'assignees.*.user_id.exists' => 'ID pengguna penugasan tidak valid.',
            'attachments.array' => 'Lampiran harus berupa array.',
            'attachments.*.file' => 'Setiap lampiran harus berupa file.',
            'attachments.*.max' => 'Ukuran setiap lampiran maksimal 5MB.',
            'related_tasks.array' => 'Tugas terkait harus berupa array.',
            'related_tasks.*.related_task_id.required' => 'ID tugas terkait wajib diisi.',
            'related_tasks.*.related_task_id.integer' => 'ID tugas terkait harus berupa integer.',
            'related_tasks.*.related_task_id.exists' => 'ID tugas terkait tidak valid.',
            'related_tasks.*.relation_type_id.required' => 'Tipe relasi tugas terkait wajib diisi.',
            'related_tasks.*.relation_type_id.integer' => 'Tipe relasi tugas terkait harus berupa integer.',
            'related_tasks.*.relation_type_id.exists' => 'Tipe relasi tugas terkait tidak valid.',
        ];
    }
}
