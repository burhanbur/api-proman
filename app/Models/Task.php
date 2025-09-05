<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;
    protected $table = 'tasks';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $dates = ['deleted_at'];
    // Relasi

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    public function status()
    {
        return $this->belongsTo(ProjectStatus::class, 'status_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function assignees()
    {
        // include pivot fields and timestamps so we can access assigned_at (pivot created_at)
        return $this->belongsToMany(User::class, 'task_assignees', 'task_id', 'user_id')
            ->withPivot(['assigned_by'])
            ->withTimestamps();
    }

    public function activityLogs()
    {
        return $this->hasMany(TaskActivityLog::class, 'task_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'task_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'model_id')->where('model_type', 'App\Models\Task')->orderBy('created_at', 'desc');
    }
}
