<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemRole extends Model
{
    protected $table = 'system_roles';
    protected $primaryKey = 'id';
    public $incrementing = true;

    public function users()
    {
        return $this->hasMany(User::class, 'system_role_id');
    }
}
