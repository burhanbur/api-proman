<?php

namespace App\Models;

use App\Models\MultiplePrimaryKey;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskAssignee extends MultiplePrimaryKey
{
    protected $table = 'task_assignees';
    protected $primaryKey = ['task_id', 'user_id'];
    public $incrementing = false;

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
