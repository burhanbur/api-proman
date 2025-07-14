<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditLog extends Model
{
    use SoftDeletes;
    protected $table = 'audit_logs';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $dates = ['deleted_at'];

    /**
     * User yang melakukan aksi.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Model yang diubah (polymorphic).
     */
    public function model()
    {
        return $this->morphTo(null, 'model_type', 'model_id');
    }
}
