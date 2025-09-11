<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectUser extends  MultiplePrimaryKey
{
    protected $guarded = [];
    protected $table = 'project_users';
    protected $primaryKey = ['project_id', 'user_id'];
    public $incrementing = false;

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function projectRole()
    {
        return $this->belongsTo(ProjectRole::class, 'project_role_id');
    }
}
