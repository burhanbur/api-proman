<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemplateStatus extends Model
{
    protected $table = 'template_status';
    protected $primaryKey = 'id';
    public $incrementing = true;
}
