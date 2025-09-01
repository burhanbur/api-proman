<?php 

namespace App\Services;

use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Models\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\ProjectRole;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Exception;

class MemberService
{
	private static $instance = null;

    // Mapping workspace role ke project role (berdasarkan code)
    private $roleMapping = [
        'owner' => 'project_manager',      // Owner workspace jadi Project Manager
        'admin' => 'project_manager',      // Admin workspace jadi Project Manager  
        'contributor' => 'contributor',    // Contributor tetap Contributor
        'guest' => 'guest'                 // Guest tetap Guest/Viewer
    ];

    public static function getInstance()
    {
        if(self::$instance == null)
        {
            self::$instance = new MemberService();
        }

        return self::$instance;
    }

    /**
     * Sync project users ketika ada perubahan di workspace members
     * Hanya untuk user yang role-nya sesuai dengan mapping default
     * 
     * @param int $workspaceId
     * @return void
     */
    public function syncProjectUsersFromWorkspace($workspaceId)
    {
        DB::beginTransaction();
        
        try {
            $workspace = Workspace::with(['workspaceUsers.user', 'workspaceUsers.workspaceRole'])->find($workspaceId);
            
            if (!$workspace) {
                throw new Exception('Workspace tidak ditemukan');
            }

            // Ambil semua project di workspace ini
            $projects = Project::where('workspace_id', $workspaceId)->get();

            foreach ($projects as $project) {
                $this->syncSingleProjectUsers($project, $workspace->workspaceUsers);
            }

            DB::commit();
            Log::info("Successfully synced project users for workspace {$workspaceId}");
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to sync project users: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync users untuk satu project berdasarkan workspace members
     * Strategy: Hanya sync user yang role project-nya sesuai dengan mapping dari workspace role
     * 
     * @param Project $project
     * @param \Illuminate\Database\Eloquent\Collection $workspaceUsers
     * @return void
     */
    private function syncSingleProjectUsers($project, $workspaceUsers)
    {
        $currentUser = auth()->user();
        
        foreach ($workspaceUsers as $workspaceUser) {
            $workspaceRoleCode = $workspaceUser->workspaceRole->code ?? '';
            $expectedProjectRoleCode = $this->roleMapping[$workspaceRoleCode] ?? 'guest';
            
            // Cari project role berdasarkan code
            $expectedProjectRole = ProjectRole::where('code', $expectedProjectRoleCode)->first();
            
            if (!$expectedProjectRole) {
                Log::warning("Project role with code '{$expectedProjectRoleCode}' not found");
                continue;
            }

            // Cek apakah user sudah ada di project
            $existingProjectUser = ProjectUser::where('project_id', $project->id)
                ->where('user_id', $workspaceUser->user_id)
                ->first();

            if ($existingProjectUser) {
                // Hanya update jika role saat ini sama dengan mapping default
                // Ini berarti role belum di-custom secara manual
                $currentProjectRoleCode = $existingProjectUser->projectRole->code ?? '';
                
                // Cek apakah role saat ini adalah hasil dari mapping workspace role sebelumnya
                if ($this->isDefaultMappedRole($currentProjectRoleCode, $workspaceUser->user_id, $project->workspace_id)) {
                    $existingProjectUser->update([
                        'project_role_id' => $expectedProjectRole->id,
                        'updated_by' => $currentUser->id ?? $workspaceUser->user_id,
                    ]);
                }
                // Jika tidak, berarti sudah di-custom manual, jadi tidak di-update
            } else {
                // Tambah user baru ke project dengan role default
                ProjectUser::create([
                    'project_id' => $project->id,
                    'user_id' => $workspaceUser->user_id,
                    'project_role_id' => $expectedProjectRole->id,
                    'created_by' => $currentUser->id ?? $workspaceUser->user_id,
                    'updated_by' => $currentUser->id ?? $workspaceUser->user_id,
                ]);
            }
        }

        // Hapus users yang sudah tidak ada di workspace
        // Tapi hanya yang role-nya masih sesuai mapping default
        $workspaceUserIds = $workspaceUsers->pluck('user_id')->toArray();
        
        $projectUsersToRemove = ProjectUser::where('project_id', $project->id)
            ->whereNotIn('user_id', $workspaceUserIds)
            ->with('projectRole')
            ->get();

        foreach ($projectUsersToRemove as $projectUser) {
            // Hanya hapus jika role-nya masih default mapping
            if ($this->isDefaultMappedRole($projectUser->projectRole->code ?? '', $projectUser->user_id, $project->workspace_id)) {
                $projectUser->delete();
            }
        }
    }

    /**
     * Cek apakah role project user adalah hasil mapping default dari workspace role
     * 
     * @param string $projectRoleCode
     * @param int $userId
     * @param int $workspaceId
     * @return bool
     */
    private function isDefaultMappedRole($projectRoleCode, $userId, $workspaceId)
    {
        // Ambil workspace role user saat ini
        $workspaceUser = WorkspaceUser::with('workspaceRole')
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->first();

        if (!$workspaceUser) {
            return true; // User sudah tidak ada di workspace, boleh dihapus
        }

        $workspaceRoleCode = $workspaceUser->workspaceRole->code ?? '';
        $expectedProjectRoleCode = $this->roleMapping[$workspaceRoleCode] ?? 'guest';

        // Jika role project sama dengan yang diharapkan dari mapping, berarti masih default
        return $projectRoleCode === $expectedProjectRoleCode;
    }

    /**
     * Sync user ketika ditambahkan ke workspace
     * 
     * @param int $workspaceId
     * @param int $userId
     * @param int $workspaceRoleId
     * @return void
     */
    public function syncUserAddedToWorkspace($workspaceId, $userId, $workspaceRoleId)
    {
        DB::beginTransaction();
        
        try {
            $workspaceRole = WorkspaceRole::find($workspaceRoleId);
            $projectRoleCode = $this->roleMapping[$workspaceRole->code] ?? 'guest';
            
            $projectRole = ProjectRole::where('code', $projectRoleCode)->first();
            
            if (!$projectRole) {
                throw new Exception("Project role with code '{$projectRoleCode}' not found");
            }

            // Tambahkan user ke semua project di workspace
            $projects = Project::where('workspace_id', $workspaceId)->get();
            
            foreach ($projects as $project) {
                // Cek apakah user sudah ada di project
                $existingProjectUser = ProjectUser::where('project_id', $project->id)
                    ->where('user_id', $userId)
                    ->first();

                if (!$existingProjectUser) {
                    ProjectUser::create([
                        'project_id' => $project->id,
                        'user_id' => $userId,
                        'project_role_id' => $projectRole->id,
                        'created_by' => auth()->id() ?? $userId,
                        'updated_by' => auth()->id() ?? $userId,
                    ]);
                }
            }

            DB::commit();
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Sync user ketika dihapus dari workspace
     * 
     * @param int $workspaceId
     * @param int $userId
     * @return void
     */
    public function syncUserRemovedFromWorkspace($workspaceId, $userId)
    {
        DB::beginTransaction();
        
        try {
            // Hapus user dari semua project di workspace 
            // Hanya jika role-nya masih default mapping
            $projects = Project::where('workspace_id', $workspaceId)->get();
            
            foreach ($projects as $project) {
                $projectUser = ProjectUser::with('projectRole')
                    ->where('project_id', $project->id)
                    ->where('user_id', $userId)
                    ->first();

                if ($projectUser && $this->isDefaultMappedRole($projectUser->projectRole->code ?? '', $userId, $workspaceId)) {
                    $projectUser->delete();
                }
            }

            DB::commit();
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Auto-add workspace members ke project baru
     * 
     * @param Project $project
     * @return void
     */
    public function autoAddWorkspaceMembersToProject($project)
    {
        $workspaceUsers = WorkspaceUser::with('workspaceRole')
            ->where('workspace_id', $project->workspace_id)
            ->get();

        $this->syncSingleProjectUsers($project, $workspaceUsers);
    }

    /**
     * Cek apakah user memiliki permission untuk mengubah role project user
     * 
     * @param int $projectId
     * @param int $targetUserId
     * @return bool
     */
    public function canManageProjectUser($projectId, $targetUserId)
    {
        $currentUser = auth()->user();
        
        // Admin sistem bisa manage semua
        if (in_array($currentUser->systemRole->code, ['admin'])) {
            return true;
        }

        // Cek role di project
        $currentUserProjectRole = ProjectUser::with('projectRole')
            ->where('project_id', $projectId)
            ->where('user_id', $currentUser->id)
            ->first();

        if (!$currentUserProjectRole) {
            return false;
        }

        // Project manager bisa manage semua member
        if ($currentUserProjectRole->projectRole->code === 'project_manager') {
            return true;
        }

        return false;
    }
}