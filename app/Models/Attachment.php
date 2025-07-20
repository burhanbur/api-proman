<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use SoftDeletes;
    protected $table = 'attachments';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $dates = ['deleted_at'];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class, 'model_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'model_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'model_id');
    }

    public function comment()
    {
        return $this->belongsTo(Comment::class, 'model_id');
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
}
