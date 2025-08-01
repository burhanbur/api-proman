<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    public $incrementing = true;
    // Relasi

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
