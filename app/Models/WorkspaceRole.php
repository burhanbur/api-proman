<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkspaceRole extends Model
{
    use SoftDeletes;
    protected $table = 'workspace_roles';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $dates = ['deleted_at'];
    // Relasi

    public function workspaceUsers()
    {
        return $this->hasMany(WorkspaceUser::class, 'workspace_role_id');
    }
}
