<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskRelationType extends Model
{
    protected $table = 'task_relation_types';
    protected $primaryKey = 'id';
    public $incrementing = true;
}
