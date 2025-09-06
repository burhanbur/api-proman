<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditLog extends Model
{
    protected $guarded = [];
    protected $table = 'audit_logs';
    protected $primaryKey = 'id';
    public $incrementing = true;
    /**
     * Cast JSON columns to arrays
     */
    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function model()
    {
        return $this->morphTo(null, 'model_type', 'model_id');
    }
}
