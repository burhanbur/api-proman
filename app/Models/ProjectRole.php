<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectRole extends Model
{
    protected $table = 'project_roles';
    protected $primaryKey = 'id';
    public $incrementing = true;

    public function projectUsers()
    {
        return $this->hasMany(ProjectUser::class, 'project_role_id');
    }
}
