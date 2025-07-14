<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;
    protected $table = 'projects';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $dates = ['deleted_at'];
    // Relasi

    public function workspace()
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
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

    public function projectStatuses()
    {
        return $this->hasMany(ProjectStatus::class, 'project_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'project_id');
    }
}
