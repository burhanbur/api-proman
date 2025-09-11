<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $guarded = [];
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'detail_url',
        'is_read',
        'read_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
