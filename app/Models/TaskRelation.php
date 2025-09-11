<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskRelation extends Model
{
    protected $guarded = [];
    protected $table = 'task_relations';
    protected $primaryKey = 'id';
    public $incrementing = true;

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function relatedTask()
    {
        return $this->belongsTo(Task::class, 'related_task_id');
    }

    public function relationType()
    {
        return $this->belongsTo(TaskRelationType::class, 'relation_type_id');
    }
}
