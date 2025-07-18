<?php

namespace Database\Seeders;

use App\Models\Priority;
use App\Models\SystemRole;
use App\Models\TemplateStatus;
use App\Models\TaskRelationType;
use App\Models\User;

use App\Models\WorkspaceRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        SystemRole::insert([
            [
                'code' => 'admin',
                'name' => 'Admin',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'workspace_admin',
                'name' => 'Workspace Admin',
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
                'system_role_id' => 3,
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
                'code' => 'pm',
                'name' => 'Project Manager',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'member',
                'name' => 'Member',
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
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'In Progress',
                'color' => '#007BFF',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Review',
                'color' => '#ff8000',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Done',
                'color' => '#28A745',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Pending',
                'color' => '#FFC107',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Closed',
                'color' => '#6C757D',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Cancelled',
                'color' => '#DC3545',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        Priority::insert([
            [
                'name' => 'Low',
                'weight' => 1,
                'color' => '#28A745',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Medium',
                'weight' => 2,
                'color' => '#FFC107',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'High',
                'weight' => 3,
                'color' => '#b42c3aff',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Urgent',
                'weight' => 4,
                'color' => '#7d0000ff',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
