<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemRole extends Model
{
    use SoftDeletes;
    protected $table = 'system_roles';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $dates = ['deleted_at'];
    // Relasi

    public function users()
    {
        return $this->hasMany(User::class, 'system_role_id');
    }
}
