<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use SoftDeletes;
    protected $table = 'workspaces';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $dates = ['deleted_at'];
    // Relasi

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

    public function projects()
    {
        return $this->hasMany(Project::class, 'workspace_id');
    }

    public function workspaceUsers()
    {
        return $this->hasMany(WorkspaceUser::class, 'workspace_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'model_id')->where('model_type', 'App\Models\Workspace')->orderBy('created_at', 'desc');
    }
}
