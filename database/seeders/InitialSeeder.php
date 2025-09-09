<?php 

namespace Database\Seeders;

use App\Models\Priority;
use App\Models\ProjectRole;
use App\Models\SystemRole;
use App\Models\TaskRelationType;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Models\WorkspaceRole;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Exception;

class InitialSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {
            SystemRole::insert([
                [
                    'code' => 'admin',
                    'name' => 'Admin',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'code' => 'user',
                    'name' => 'User',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
            ]);

            User::insert([
                [
                    'uuid' => Str::uuid(),
                    'code' => '216105',
                    'username' => 'bmafazi',
                    'name' => 'Burhan Mafazi',
                    'email' => 'burhan.mafazi@universitaspertamina.ac.id',
                    'password' => bcrypt('burhan123'),
                    'system_role_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'uuid' => Str::uuid(),
                    'code' => '216090',
                    'username' => 'lmawati',
                    'name' => 'Luluk Eko mawati',
                    'email' => 'lulukeko.mawati@universitaspertamina.ac.id',
                    'password' => bcrypt('burhan123'),
                    'system_role_id' => 2,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'uuid' => Str::uuid(),
                    'code' => '219030',
                    'username' => 'bwicaksono',
                    'name' => 'Bayu Wicaksono',
                    'email' => 'bayu.wicaksono@universitaspertamina.ac.id',
                    'password' => bcrypt('burhan123'),
                    'system_role_id' => 2,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
            ]);

            TaskRelationType::insert([
                [
                    'code' => 'depends',
                    'name' => 'Depends On',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'code' => 'related',
                    'name' => 'Related To',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'code' => 'duplicate',
                    'name' => 'Duplicate Of',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'code' => 'subtask',
                    'name' => 'Subtask Of',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
            ]);

            WorkspaceRole::insert([
                [
                    'code' => 'owner',
                    'name' => 'Owner',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'code' => 'admin',
                    'name' => 'Admin',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'code' => 'contributor',
                    'name' => 'Contributor',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'code' => 'guest',
                    'name' => 'Guest',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
            ]);

            ProjectRole::insert([
                [
                    'code' => 'project_manager',
                    'name' => 'Project Manager',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'code' => 'contributor',
                    'name' => 'Contributor',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'code' => 'guest',
                    'name' => 'Guest',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
            ]);

            TemplateStatus::insert([
                [
                    'name' => 'To Do',
                    'color' => '#0fae9cff',
                    'is_completed' => false,
                    'is_cancelled' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'In Progress',
                    'color' => '#007BFF',
                    'is_completed' => false,
                    'is_cancelled' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Review',
                    'color' => '#ff8000',
                    'is_completed' => false,
                    'is_cancelled' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Revised',
                    'color' => '#7119beff',
                    'is_completed' => false,
                    'is_cancelled' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Completed',
                    'color' => '#28A745',
                    'is_completed' => true,
                    'is_cancelled' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Pending',
                    'color' => '#FFC107',
                    'is_completed' => false,
                    'is_cancelled' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Closed',
                    'color' => '#6C757D',
                    'is_completed' => true,
                    'is_cancelled' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Cancelled',
                    'color' => '#DC3545',
                    'is_completed' => false,
                    'is_cancelled' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
            ]);

            Priority::insert([
                [
                    'name' => 'Low',
                    'level' => 1,
                    'color' => '#28A745',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Medium',
                    'level' => 2,
                    'color' => '#FFC107',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'High',
                    'level' => 3,
                    'color' => '#b42c3aff',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Urgent',
                    'level' => 4,
                    'color' => '#7d0000',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
            ]);
            DB::commit();
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}