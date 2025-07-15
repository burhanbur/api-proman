<?php


namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Lab404\Impersonate\Models\Impersonate;
use Kra8\Snowflake\HasSnowflakePrimary;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, Impersonate;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    protected $table = 'users';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $dates = ['deleted_at'];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    protected $customClaims = [];

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    // public function getJWTCustomClaims()
    // {
    //     return [];
    // }

    public function setCustomClaims(array $claims)
    {
        $this->customClaims = $claims ?? null;
    }

    public function getJWTCustomClaims()
    {
        return $this->customClaims ?? [];
    }

    // Relasi

    public function systemRole()
    {
        return $this->belongsTo(SystemRole::class, 'system_role_id');
    }

    public function workspaces()
    {
        return $this->hasMany(Workspace::class, 'owner_id');
    }

    public function workspaceUsers()
    {
        return $this->hasMany(WorkspaceUser::class, 'user_id');
    }

    public function createdWorkspaces()
    {
        return $this->hasMany(Workspace::class, 'created_by');
    }

    public function updatedWorkspaces()
    {
        return $this->hasMany(Workspace::class, 'updated_by');
    }

    public function deletedWorkspaces()
    {
        return $this->hasMany(Workspace::class, 'deleted_by');
    }

    public function createdProjects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function updatedProjects()
    {
        return $this->hasMany(Project::class, 'updated_by');
    }

    public function deletedProjects()
    {
        return $this->hasMany(Project::class, 'deleted_by');
    }

    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function updatedTasks()
    {
        return $this->hasMany(Task::class, 'updated_by');
    }

    public function deletedTasks()
    {
        return $this->hasMany(Task::class, 'deleted_by');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'created_by');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'created_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function assignedTasks()
    {
        return $this->belongsToMany(Task::class, 'task_assignees', 'user_id', 'task_id');
    }

    public function assignedByTasks()
    {
        return $this->hasMany(TaskAssignee::class, 'assigned_by');
    }

    public function taskActivityLogs()
    {
        return $this->hasMany(TaskActivityLog::class, 'user_id');
    }

    public function updatedComments()
    {
        return $this->hasMany(Comment::class, 'updated_by');
    }

    public function updatedAttachments()
    {
        return $this->hasMany(Attachment::class, 'updated_by');
    }

    public function deletedComments()
    {
        return $this->hasMany(Comment::class, 'deleted_by');
    }

    public function deletedAttachments()
    {
        return $this->hasMany(Attachment::class, 'deleted_by');
    }
}
