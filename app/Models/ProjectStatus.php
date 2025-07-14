<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectStatus extends Model
{
    use SoftDeletes;
    protected $table = 'project_status';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $dates = ['deleted_at'];
    // Relasi

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'status_id');
    }
}
