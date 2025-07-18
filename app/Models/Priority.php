<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Priority extends Model
{
    protected $table = 'priorities';
    protected $primaryKey = 'id';
    public $incrementing = true;

    public function tasks()
    {
        return $this->hasMany(Task::class, 'priority_id');
    }
}
