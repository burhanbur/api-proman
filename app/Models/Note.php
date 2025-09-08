<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use SoftDeletes;
    
    protected $table = 'notes';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $dates = ['deleted_at'];
    protected $guarded = [];

    protected $fillable = [
        'uuid', 'model_type', 'model_id', 'content', 'created_by', 'updated_by'
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class, 'model_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'model_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'model_id')
            ->where('model_type', self::class);
    }
}
