<?php

namespace App\Models;

use App\Models\MultiplePrimaryKey;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkspaceUser extends MultiplePrimaryKey
{
    protected $guarded = [];
    protected $table = 'workspace_users';
    protected $primaryKey = ['workspace_id', 'user_id'];
    public $incrementing = false;

    public function workspace()
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function workspaceRole()
    {
        return $this->belongsTo(WorkspaceRole::class, 'workspace_role_id');
    }
}
