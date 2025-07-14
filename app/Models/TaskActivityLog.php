<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskActivityLog extends Model
{
    use SoftDeletes;
    protected $table = 'task_activity_logs';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $dates = ['deleted_at'];
    // Relasi

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
