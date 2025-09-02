<?php 

namespace Database\Seeders;

use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Models\TaskAssignee;
use App\Models\TaskRelation;
use App\Models\Comment;
use App\Models\Attachment;
use App\Models\Notification;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Exception;

class DummySeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // Create 8 Workspaces
            $workspaces = [];
            $workspaceData = [
                [
                    'slug' => 'universitas-pertamina-workspace',
                    'name' => 'Universitas Pertamina Workspace',
                    'description' => 'Workspace untuk aktivitas akademik dan administrasi Universitas Pertamina.',
                    'is_active' => true,
                    'is_public' => false,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'slug' => 'development-team-workspace',
                    'name' => 'Development Team Workspace',
                    'description' => 'Tim pengembang yang mengerjakan proyek-proyek teknis dan produk.',
                    'is_active' => true,
                    'is_public' => true,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'slug' => 'research-workspace',
                    'name' => 'Research & Innovation Workspace',
                    'description' => 'Ruang kerja untuk penelitian dan inovasi, khususnya bidang AI dan ML.',
                    'is_active' => true,
                    'is_public' => false,
                    'created_by' => 2,
                    'updated_by' => 2,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'slug' => 'marketing-workspace',
                    'name' => 'Marketing Team Workspace',
                    'description' => 'Tim pemasaran yang menangani kampanye digital dan komunikasi publik.',
                    'is_active' => true,
                    'is_public' => false,
                    'created_by' => 3,
                    'updated_by' => 3,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'slug' => 'academic-workspace',
                    'name' => 'Academic Affairs Workspace',
                    'description' => 'Koordinasi kegiatan akademik, kurikulum, dan administrasi pendidikan.',
                    'is_active' => true,
                    'is_public' => true,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'slug' => 'it-infrastructure-workspace',
                    'name' => 'IT Infrastructure Workspace',
                    'description' => 'Mengelola infrastruktur dan operasi IT kampus, termasuk server dan jaringan.',
                    'is_active' => true,
                    'is_public' => false,
                    'created_by' => 2,
                    'updated_by' => 2,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'slug' => 'student-affairs-workspace',
                    'name' => 'Student Affairs Workspace',
                    'description' => 'Penanganan urusan kemahasiswaan seperti beasiswa dan kegiatan mahasiswa.',
                    'is_active' => true,
                    'is_public' => false,
                    'created_by' => 3,
                    'updated_by' => 3,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'slug' => 'quality-assurance-workspace',
                    'name' => 'Quality Assurance Workspace',
                    'description' => 'Tim QA yang bertanggung jawab atas audit kualitas sistem dan proses.',
                    'is_active' => false,
                    'is_public' => false,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => now()->subDays(30),
                    'updated_at' => now()->subDays(5)
                ]
            ];

            foreach ($workspaceData as $workspace) {
                $workspaces[] = Workspace::create($workspace);
            }

            // Create Workspace Users (Assign users to workspaces)
            $workspaceUserData = [
                // Workspace 1 - Universitas Pertamina
                ['workspace_id' => $workspaces[0]->id, 'user_id' => 1, 'workspace_role_id' => 1], // Owner
                ['workspace_id' => $workspaces[0]->id, 'user_id' => 2, 'workspace_role_id' => 2], // Admin
                ['workspace_id' => $workspaces[0]->id, 'user_id' => 3, 'workspace_role_id' => 3], // Contributor
                
                // Workspace 2 - Development Team
                ['workspace_id' => $workspaces[1]->id, 'user_id' => 1, 'workspace_role_id' => 1], // Owner
                ['workspace_id' => $workspaces[1]->id, 'user_id' => 3, 'workspace_role_id' => 2], // Admin
                ['workspace_id' => $workspaces[1]->id, 'user_id' => 2, 'workspace_role_id' => 3], // Contributor
                
                // Workspace 3 - Research
                ['workspace_id' => $workspaces[2]->id, 'user_id' => 2, 'workspace_role_id' => 1], // Owner
                ['workspace_id' => $workspaces[2]->id, 'user_id' => 1, 'workspace_role_id' => 3], // Contributor
                ['workspace_id' => $workspaces[2]->id, 'user_id' => 3, 'workspace_role_id' => 4], // Guest
                
                // Workspace 4 - Marketing
                ['workspace_id' => $workspaces[3]->id, 'user_id' => 3, 'workspace_role_id' => 1], // Owner
                ['workspace_id' => $workspaces[3]->id, 'user_id' => 1, 'workspace_role_id' => 2], // Admin
                ['workspace_id' => $workspaces[3]->id, 'user_id' => 2, 'workspace_role_id' => 3], // Contributor
                
                // Workspace 5 - Academic Affairs
                ['workspace_id' => $workspaces[4]->id, 'user_id' => 1, 'workspace_role_id' => 1], // Owner
                ['workspace_id' => $workspaces[4]->id, 'user_id' => 2, 'workspace_role_id' => 3], // Contributor
                
                // Workspace 6 - IT Infrastructure
                ['workspace_id' => $workspaces[5]->id, 'user_id' => 2, 'workspace_role_id' => 1], // Owner
                ['workspace_id' => $workspaces[5]->id, 'user_id' => 3, 'workspace_role_id' => 3], // Contributor
                
                // Workspace 7 - Student Affairs
                ['workspace_id' => $workspaces[6]->id, 'user_id' => 3, 'workspace_role_id' => 1], // Owner
                ['workspace_id' => $workspaces[6]->id, 'user_id' => 1, 'workspace_role_id' => 3], // Contributor
                
                // Workspace 8 - Quality Assurance
                ['workspace_id' => $workspaces[7]->id, 'user_id' => 1, 'workspace_role_id' => 1], // Owner
            ];

            foreach ($workspaceUserData as $workspaceUser) {
                WorkspaceUser::create($workspaceUser);
            }

            // Create 12 Projects
            $projects = [];
            $projectData = [
                [
                    'slug' => 'sistem-informasi-akademik',
                    'workspace_id' => $workspaces[0]->id,
                    'name' => 'Sistem Informasi Akademik',
                    'description' => 'Pengembangan sistem informasi akademik untuk mengelola data mahasiswa, dosen, dan mata kuliah.',
                    'is_active' => true,
                    'is_public' => false,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => now()->subDays(60),
                    'updated_at' => now()->subDays(2)
                ],
                [
                    'slug' => 'web-portal-mahasiswa',
                    'workspace_id' => $workspaces[0]->id,
                    'name' => 'Web Portal Mahasiswa',
                    'description' => 'Portal web untuk mahasiswa mengakses informasi akademik, jadwal, dan pengumuman.',
                    'is_active' => true,
                    'is_public' => true,
                    'created_by' => 2,
                    'updated_by' => 2,
                    'created_at' => now()->subDays(45),
                    'updated_at' => now()->subDays(1)
                ],
                [
                    'slug' => 'mobile-app-proman',
                    'workspace_id' => $workspaces[1]->id,
                    'name' => 'Mobile App ProMan',
                    'description' => 'Aplikasi mobile untuk project management dengan fitur task tracking dan collaboration.',
                    'is_active' => true,
                    'is_public' => false,
                    'created_by' => 1,
                    'updated_by' => 3,
                    'created_at' => now()->subDays(30),
                    'updated_at' => now()
                ],
                [
                    'slug' => 'api-integration-system',
                    'workspace_id' => $workspaces[1]->id,
                    'name' => 'API Integration System',
                    'description' => 'Sistem integrasi API untuk menghubungkan berbagai aplikasi internal.',
                    'is_active' => true,
                    'is_public' => false,
                    'created_by' => 3,
                    'updated_by' => 1,
                    'created_at' => now()->subDays(20),
                    'updated_at' => now()->subHours(6)
                ],
                [
                    'slug' => 'penelitian-ai-machine-learning',
                    'workspace_id' => $workspaces[2]->id,
                    'name' => 'Penelitian AI & Machine Learning',
                    'description' => 'Proyek penelitian pengembangan algoritma AI dan machine learning untuk industri energi.',
                    'is_active' => true,
                    'is_public' => true,
                    'created_by' => 2,
                    'updated_by' => 2,
                    'created_at' => now()->subDays(90),
                    'updated_at' => now()->subDays(3)
                ],
                [
                    'slug' => 'kampanye-digital-marketing',
                    'workspace_id' => $workspaces[3]->id,
                    'name' => 'Kampanye Digital Marketing',
                    'description' => 'Kampanye pemasaran digital untuk meningkatkan brand awareness universitas.',
                    'is_active' => true,
                    'is_public' => false,
                    'created_by' => 3,
                    'updated_by' => 3,
                    'created_at' => now()->subDays(15),
                    'updated_at' => now()->subHours(12)
                ],
                [
                    'slug' => 'website-official-universitas',
                    'workspace_id' => $workspaces[3]->id,
                    'name' => 'Website Official Universitas',
                    'description' => 'Redesign dan pengembangan website resmi Universitas Pertamina.',
                    'is_active' => true,
                    'is_public' => true,
                    'created_by' => 1,
                    'updated_by' => 3,
                    'created_at' => now()->subDays(25),
                    'updated_at' => now()->subHours(2)
                ],
                [
                    'slug' => 'kurikulum-digital-transformation',
                    'workspace_id' => $workspaces[4]->id,
                    'name' => 'Kurikulum Digital Transformation',
                    'description' => 'Pengembangan kurikulum baru untuk program digital transformation.',
                    'is_active' => true,
                    'is_public' => false,
                    'created_by' => 1,
                    'updated_by' => 2,
                    'created_at' => now()->subDays(50),
                    'updated_at' => now()->subDays(4)
                ],
                [
                    'slug' => 'server-infrastructure-upgrade',
                    'workspace_id' => $workspaces[5]->id,
                    'name' => 'Server Infrastructure Upgrade',
                    'description' => 'Upgrade infrastruktur server untuk mendukung aplikasi kampus.',
                    'is_active' => true,
                    'is_public' => false,
                    'created_by' => 2,
                    'updated_by' => 3,
                    'created_at' => now()->subDays(40),
                    'updated_at' => now()->subDays(1)
                ],
                [
                    'slug' => 'program-beasiswa-mahasiswa',
                    'workspace_id' => $workspaces[6]->id,
                    'name' => 'Program Beasiswa Mahasiswa',
                    'description' => 'Pengelolaan program beasiswa untuk mahasiswa berprestasi.',
                    'is_active' => true,
                    'is_public' => true,
                    'created_by' => 3,
                    'updated_by' => 1,
                    'created_at' => now()->subDays(35),
                    'updated_at' => now()->subHours(8)
                ],
                [
                    'slug' => 'event-wisuda-semester-genap',
                    'workspace_id' => $workspaces[6]->id,
                    'name' => 'Event Wisuda Semester Genap',
                    'description' => 'Persiapan dan pelaksanaan acara wisuda semester genap 2025.',
                    'is_active' => true,
                    'is_public' => false,
                    'created_by' => 1,
                    'updated_by' => 3,
                    'created_at' => now()->subDays(10),
                    'updated_at' => now()->subHours(4)
                ],
                [
                    'slug' => 'audit-sistem-keamanan',
                    'workspace_id' => $workspaces[7]->id,
                    'name' => 'Audit Sistem Keamanan',
                    'description' => 'Audit menyeluruh terhadap sistem keamanan aplikasi dan infrastruktur.',
                    'is_active' => false,
                    'is_public' => false,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => now()->subDays(100),
                    'updated_at' => now()->subDays(20)
                ]
            ];

            foreach ($projectData as $project) {
                $projects[] = Project::create($project);
            }

            // Create Project Users (Assign users to projects)
            $projectUserData = [
                // Project 1 - Sistem Informasi Akademik
                ['project_id' => $projects[0]->id, 'user_id' => 1, 'project_role_id' => 1], // Project Manager
                ['project_id' => $projects[0]->id, 'user_id' => 2, 'project_role_id' => 2], // Contributor
                ['project_id' => $projects[0]->id, 'user_id' => 3, 'project_role_id' => 2], // Contributor
                
                // Project 2 - Web Portal Mahasiswa
                ['project_id' => $projects[1]->id, 'user_id' => 2, 'project_role_id' => 1], // Project Manager
                ['project_id' => $projects[1]->id, 'user_id' => 1, 'project_role_id' => 2], // Contributor
                
                // Project 3 - Mobile App ProMan
                ['project_id' => $projects[2]->id, 'user_id' => 1, 'project_role_id' => 1], // Project Manager
                ['project_id' => $projects[2]->id, 'user_id' => 3, 'project_role_id' => 2], // Contributor
                
                // Project 4 - API Integration System
                ['project_id' => $projects[3]->id, 'user_id' => 3, 'project_role_id' => 1], // Project Manager
                ['project_id' => $projects[3]->id, 'user_id' => 1, 'project_role_id' => 2], // Contributor
                ['project_id' => $projects[3]->id, 'user_id' => 2, 'project_role_id' => 3], // Guest
                
                // Project 5 - Penelitian AI
                ['project_id' => $projects[4]->id, 'user_id' => 2, 'project_role_id' => 1], // Project Manager
                ['project_id' => $projects[4]->id, 'user_id' => 1, 'project_role_id' => 3], // Guest
                
                // Project 6 - Kampanye Digital Marketing
                ['project_id' => $projects[5]->id, 'user_id' => 3, 'project_role_id' => 1], // Project Manager
                ['project_id' => $projects[5]->id, 'user_id' => 1, 'project_role_id' => 2], // Contributor
                
                // Project 7 - Website Official
                ['project_id' => $projects[6]->id, 'user_id' => 1, 'project_role_id' => 1], // Project Manager
                ['project_id' => $projects[6]->id, 'user_id' => 3, 'project_role_id' => 2], // Contributor
                ['project_id' => $projects[6]->id, 'user_id' => 2, 'project_role_id' => 2], // Contributor
                
                // Project 8 - Kurikulum Digital
                ['project_id' => $projects[7]->id, 'user_id' => 1, 'project_role_id' => 1], // Project Manager
                ['project_id' => $projects[7]->id, 'user_id' => 2, 'project_role_id' => 2], // Contributor
                
                // Project 9 - Server Infrastructure
                ['project_id' => $projects[8]->id, 'user_id' => 2, 'project_role_id' => 1], // Project Manager
                ['project_id' => $projects[8]->id, 'user_id' => 3, 'project_role_id' => 2], // Contributor
                
                // Project 10 - Program Beasiswa
                ['project_id' => $projects[9]->id, 'user_id' => 3, 'project_role_id' => 1], // Project Manager
                ['project_id' => $projects[9]->id, 'user_id' => 1, 'project_role_id' => 2], // Contributor
                
                // Project 11 - Event Wisuda
                ['project_id' => $projects[10]->id, 'user_id' => 1, 'project_role_id' => 1], // Project Manager
                ['project_id' => $projects[10]->id, 'user_id' => 3, 'project_role_id' => 2], // Contributor
                ['project_id' => $projects[10]->id, 'user_id' => 2, 'project_role_id' => 3], // Guest
                
                // Project 12 - Audit Sistem
                ['project_id' => $projects[11]->id, 'user_id' => 1, 'project_role_id' => 1], // Project Manager
            ];

            foreach ($projectUserData as $projectUser) {
                ProjectUser::create($projectUser);
            }

            // Create Project Status for each project (based on template status)
            foreach ($projects as $project) {
                // Create default status for each project based on template
                $templateStatuses = [
                    ['name' => 'To Do', 'color' => '#0fae9cff'],
                    ['name' => 'In Progress', 'color' => '#007BFF'],
                    ['name' => 'Review', 'color' => '#ff8000'],
                    ['name' => 'Done', 'color' => '#28A745'],
                    ['name' => 'Cancelled', 'color' => '#DC3545'],
                ];

                foreach ($templateStatuses as $status) {
                    ProjectStatus::create([
                        'project_id' => $project->id,
                        'name' => $status['name'],
                        'color' => $status['color'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            // Create Project Status untuk setiap project (berdasarkan template_status)
            $projectStatuses = [];
            foreach ($projects as $project) {
                // Ambil beberapa status template untuk setiap project
                $templateStatuses = [1, 2, 3, 5]; // To Do, In Progress, Review, Done
                foreach ($templateStatuses as $templateStatusId) {
                    $templateStatus = \App\Models\TemplateStatus::find($templateStatusId);
                    $projectStatus = \App\Models\ProjectStatus::create([
                        'project_id' => $project->id,
                        'name' => $templateStatus->name,
                        'color' => $templateStatus->color,
                    ]);
                    $projectStatuses[] = $projectStatus;
                }
            }

            // Helper function untuk mendapatkan project status ID
            $getProjectStatusId = function($projectIndex, $templateStatusId) use ($projectStatuses) {
                $baseIndex = $projectIndex * 4; // Setiap project punya 4 status
                switch($templateStatusId) {
                    case 1: return $projectStatuses[$baseIndex]->id;     // To Do
                    case 2: return $projectStatuses[$baseIndex + 1]->id; // In Progress  
                    case 3: return $projectStatuses[$baseIndex + 2]->id; // Review
                    case 5: return $projectStatuses[$baseIndex + 3]->id; // Done
                    default: return $projectStatuses[$baseIndex]->id;    // Default To Do
                }
            };

            // Create 25+ Tasks across different projects  
            $tasks = [];
            $taskData = [
                // Tasks for Project 1 - Sistem Informasi Akademik
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[0]->id,
                    'title' => 'Setup Database Schema',
                    'description' => 'Membuat skema database untuk sistem informasi akademik dengan tabel mahasiswa, dosen, mata kuliah.',
                    'point' => 8.0,
                    'due_date' => now()->addDays(7),
                    'priority_id' => 3, // High
                    'status_id' => $getProjectStatusId(0, 2), // In Progress untuk project 0
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => now()->subDays(5),
                    'updated_at' => now()->subDays(1)
                ],
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[0]->id,
                    'title' => 'Design User Interface',
                    'description' => 'Mendesain antarmuka pengguna untuk modul pendaftaran mahasiswa dan pengelolaan data akademik.',
                    'point' => 5.0,
                    'due_date' => now()->addDays(14),
                    'priority_id' => 2, // Medium
                    'status_id' => $getProjectStatusId(0, 1), // To Do untuk project 0
                    'created_by' => 2,
                    'updated_by' => 2,
                    'created_at' => now()->subDays(3),
                    'updated_at' => now()->subDays(1)
                ],
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[0]->id,
                    'title' => 'Implement Authentication Module',
                    'description' => 'Mengimplementasikan sistem autentikasi dan autorisasi untuk berbagai role pengguna.',
                    'point' => 6.0,
                    'due_date' => now()->addDays(10),
                    'priority_id' => 3, // High
                    'status_id' => $getProjectStatusId(0, 1), // To Do untuk project 0
                    'created_by' => 1,
                    'updated_by' => 3,
                    'created_at' => now()->subDays(2),
                    'updated_at' => now()
                ],

                // Tasks for Project 2 - Web Portal Mahasiswa
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[1]->id,
                    'title' => 'Create Student Dashboard',
                    'description' => 'Membuat dashboard untuk mahasiswa melihat jadwal kuliah, nilai, dan pengumuman.',
                    'point' => 4.0,
                    'due_date' => now()->addDays(12),
                    'priority_id' => 2, // Medium
                    'status_id' => $getProjectStatusId(1, 3), // Review untuk project 1
                    'created_by' => 2,
                    'updated_by' => 1,
                    'created_at' => now()->subDays(8),
                    'updated_at' => now()->subHours(6)
                ],
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[1]->id,
                    'title' => 'Integration with Academic System',
                    'description' => 'Integrasi portal mahasiswa dengan sistem informasi akademik yang ada.',
                    'point' => 7.0,
                    'due_date' => now()->addDays(20),
                    'priority_id' => 4, // Urgent
                    'status_id' => $getProjectStatusId(1, 2), // In Progress untuk project 1
                    'created_by' => 1,
                    'updated_by' => 2,
                    'created_at' => now()->subDays(6),
                    'updated_at' => now()->subHours(2)
                ],

                // Tasks for Project 3 - Mobile App ProMan
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[2]->id,
                    'title' => 'Design Mobile App UI/UX',
                    'description' => 'Mendesain antarmuka dan pengalaman pengguna untuk aplikasi mobile project management.',
                    'point' => 6.0,
                    'due_date' => now()->addDays(15),
                    'priority_id' => 2, // Medium
                    'status_id' => 4, // Done
                    'created_by' => 1,
                    'updated_by' => 3,
                    'created_at' => now()->subDays(10),
                    'updated_at' => now()->subDays(2)
                ],
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[2]->id,
                    'title' => 'Develop Task Management Features',
                    'description' => 'Mengembangkan fitur pengelolaan task, assignment, dan tracking progress.',
                    'point' => 9.0,
                    'due_date' => now()->addDays(25),
                    'priority_id' => 3, // High
                    'status_id' => 2, // In Progress
                    'created_by' => 3,
                    'updated_by' => 1,
                    'created_at' => now()->subDays(7),
                    'updated_at' => now()->subHours(4)
                ],
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[2]->id,
                    'title' => 'Implement Push Notifications',
                    'description' => 'Mengimplementasikan sistem notifikasi push untuk update task dan deadline.',
                    'point' => 4.0,
                    'due_date' => now()->addDays(18),
                    'priority_id' => 2, // Medium
                    'status_id' => 1, // To Do
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => now()->subDays(4),
                    'updated_at' => now()->subDays(1)
                ],

                // Tasks for Project 4 - API Integration System
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[3]->id,
                    'title' => 'Design API Architecture',
                    'description' => 'Merancang arsitektur API yang scalable dan secure untuk integrasi antar sistem.',
                    'point' => 8.0,
                    'due_date' => now()->addDays(8),
                    'priority_id' => 4, // Urgent
                    'status_id' => 3, // Review
                    'created_by' => 3,
                    'updated_by' => 1,
                    'created_at' => now()->subDays(12),
                    'updated_at' => now()->subHours(8)
                ],
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[3]->id,
                    'title' => 'Implement OAuth2 Authentication',
                    'description' => 'Mengimplementasikan sistem autentikasi OAuth2 untuk keamanan API.',
                    'point' => 6.0,
                    'due_date' => now()->addDays(16),
                    'priority_id' => 3, // High
                    'status_id' => 2, // In Progress
                    'created_by' => 1,
                    'updated_by' => 3,
                    'created_at' => now()->subDays(9),
                    'updated_at' => now()->subHours(1)
                ],

                // Tasks for Project 5 - Penelitian AI
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[4]->id,
                    'title' => 'Literature Review on AI in Energy',
                    'description' => 'Melakukan studi literatur tentang penerapan AI dalam industri energi.',
                    'point' => 5.0,
                    'due_date' => now()->addDays(30),
                    'priority_id' => 1, // Low
                    'status_id' => 4, // Done
                    'created_by' => 2,
                    'updated_by' => 2,
                    'created_at' => now()->subDays(25),
                    'updated_at' => now()->subDays(5)
                ],
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[4]->id,
                    'title' => 'Develop ML Model Prototype',
                    'description' => 'Mengembangkan prototype model machine learning untuk prediksi konsumsi energi.',
                    'point' => 10.0,
                    'due_date' => now()->addDays(45),
                    'priority_id' => 3, // High
                    'status_id' => 2, // In Progress
                    'created_by' => 2,
                    'updated_by' => 1,
                    'created_at' => now()->subDays(20),
                    'updated_at' => now()->subDays(3)
                ],

                // Tasks for Project 6 - Kampanye Digital Marketing
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[5]->id,
                    'title' => 'Create Social Media Content',
                    'description' => 'Membuat konten untuk media sosial Instagram, LinkedIn, dan Facebook.',
                    'point' => 3.0,
                    'due_date' => now()->addDays(5),
                    'priority_id' => 2, // Medium
                    'status_id' => 2, // In Progress
                    'created_by' => 3,
                    'updated_by' => 1,
                    'created_at' => now()->subDays(8),
                    'updated_at' => now()->subHours(12)
                ],
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[5]->id,
                    'title' => 'Setup Google Ads Campaign',
                    'description' => 'Menyiapkan kampanye iklan Google Ads untuk program studi baru.',
                    'point' => 4.0,
                    'due_date' => now()->addDays(10),
                    'priority_id' => 3, // High
                    'status_id' => 1, // To Do
                    'created_by' => 1,
                    'updated_by' => 3,
                    'created_at' => now()->subDays(5),
                    'updated_at' => now()->subDays(2)
                ],

                // Tasks for Project 7 - Website Official
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[6]->id,
                    'title' => 'Redesign Homepage Layout',
                    'description' => 'Mendesain ulang layout homepage website dengan tampilan yang lebih modern.',
                    'point' => 5.0,
                    'due_date' => now()->addDays(14),
                    'priority_id' => 2, // Medium
                    'status_id' => 3, // Review
                    'created_by' => 1,
                    'updated_by' => 3,
                    'created_at' => now()->subDays(11),
                    'updated_at' => now()->subHours(18)
                ],
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[6]->id,
                    'title' => 'Optimize Website Performance',
                    'description' => 'Mengoptimalkan performa website untuk loading time yang lebih cepat.',
                    'point' => 6.0,
                    'due_date' => now()->addDays(21),
                    'priority_id' => 3, // High
                    'status_id' => 1, // To Do
                    'created_by' => 3,
                    'updated_by' => 2,
                    'created_at' => now()->subDays(7),
                    'updated_at' => now()->subDays(1)
                ],

                // Tasks for Project 8 - Kurikulum Digital
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[7]->id,
                    'title' => 'Analyze Industry Requirements',
                    'description' => 'Menganalisis kebutuhan industri untuk skill digital transformation.',
                    'point' => 7.0,
                    'due_date' => now()->addDays(28),
                    'priority_id' => 2, // Medium
                    'status_id' => 4, // Done
                    'created_by' => 1,
                    'updated_by' => 2,
                    'created_at' => now()->subDays(35),
                    'updated_at' => now()->subDays(7)
                ],
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[7]->id,
                    'title' => 'Design Course Modules',
                    'description' => 'Merancang modul-modul mata kuliah untuk program digital transformation.',
                    'point' => 8.0,
                    'due_date' => now()->addDays(35),
                    'priority_id' => 3, // High
                    'status_id' => 2, // In Progress
                    'created_by' => 2,
                    'updated_by' => 1,
                    'created_at' => now()->subDays(15),
                    'updated_at' => now()->subDays(2)
                ],

                // Tasks for Project 9 - Server Infrastructure
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[8]->id,
                    'title' => 'Evaluate Current Infrastructure',
                    'description' => 'Mengevaluasi infrastruktur server saat ini dan identifikasi kebutuhan upgrade.',
                    'point' => 6.0,
                    'due_date' => now()->addDays(12),
                    'priority_id' => 3, // High
                    'status_id' => 4, // Done
                    'created_by' => 2,
                    'updated_by' => 3,
                    'created_at' => now()->subDays(18),
                    'updated_at' => now()->subDays(8)
                ],
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[8]->id,
                    'title' => 'Procure New Server Hardware',
                    'description' => 'Pengadaan hardware server baru untuk mendukung aplikasi kampus.',
                    'point' => 4.0,
                    'due_date' => now()->addDays(22),
                    'priority_id' => 4, // Urgent
                    'status_id' => 2, // In Progress
                    'created_by' => 3,
                    'updated_by' => 2,
                    'created_at' => now()->subDays(10),
                    'updated_at' => now()->subHours(6)
                ],

                // Tasks for Project 10 - Program Beasiswa
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[9]->id,
                    'title' => 'Create Scholarship Database',
                    'description' => 'Membuat database untuk mengelola data penerima beasiswa dan kriteria.',
                    'point' => 5.0,
                    'due_date' => now()->addDays(18),
                    'priority_id' => 2, // Medium
                    'status_id' => 3, // Review
                    'created_by' => 3,
                    'updated_by' => 1,
                    'created_at' => now()->subDays(12),
                    'updated_at' => now()->subHours(10)
                ],
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[9]->id,
                    'title' => 'Develop Selection System',
                    'description' => 'Mengembangkan sistem seleksi otomatis berdasarkan kriteria beasiswa.',
                    'point' => 7.0,
                    'due_date' => now()->addDays(25),
                    'priority_id' => 3, // High
                    'status_id' => 1, // To Do
                    'created_by' => 1,
                    'updated_by' => 3,
                    'created_at' => now()->subDays(8),
                    'updated_at' => now()->subDays(3)
                ],

                // Tasks for Project 11 - Event Wisuda
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[10]->id,
                    'title' => 'Venue Booking and Setup',
                    'description' => 'Booking venue dan setup acara wisuda semester genap 2025.',
                    'point' => 3.0,
                    'due_date' => now()->addDays(6),
                    'priority_id' => 4, // Urgent
                    'status_id' => 2, // In Progress
                    'created_by' => 1,
                    'updated_by' => 3,
                    'created_at' => now()->subDays(4),
                    'updated_at' => now()->subHours(3)
                ],
                [
                    'uuid' => Str::uuid(),
                    'project_id' => $projects[10]->id,
                    'title' => 'Graduate Data Verification',
                    'description' => 'Verifikasi data lulusan yang akan mengikuti wisuda.',
                    'point' => 4.0,
                    'due_date' => now()->addDays(8),
                    'priority_id' => 4, // Urgent
                    'status_id' => 3, // Review
                    'created_by' => 3,
                    'updated_by' => 1,
                    'created_at' => now()->subDays(6),
                    'updated_at' => now()->subHours(5)
                ]
            ];

            foreach ($taskData as $task) {
                $tasks[] = Task::create($task);
            }

            // Create Task Assignees
            $taskAssigneeData = [
                // Task assignments (hanya untuk task yang ada)
                ['task_id' => $tasks[0]->id, 'user_id' => 2, 'assigned_by' => 1],
                ['task_id' => $tasks[0]->id, 'user_id' => 3, 'assigned_by' => 1],
                ['task_id' => $tasks[1]->id, 'user_id' => 1, 'assigned_by' => 2],
                ['task_id' => $tasks[2]->id, 'user_id' => 3, 'assigned_by' => 1],
                ['task_id' => $tasks[3]->id, 'user_id' => 1, 'assigned_by' => 2],
                ['task_id' => $tasks[3]->id, 'user_id' => 2, 'assigned_by' => 2],
                ['task_id' => $tasks[4]->id, 'user_id' => 2, 'assigned_by' => 1],
                ['task_id' => $tasks[5]->id, 'user_id' => 3, 'assigned_by' => 1],
                ['task_id' => $tasks[6]->id, 'user_id' => 1, 'assigned_by' => 3],
                ['task_id' => $tasks[7]->id, 'user_id' => 3, 'assigned_by' => 1],
                ['task_id' => $tasks[8]->id, 'user_id' => 1, 'assigned_by' => 3],
                ['task_id' => $tasks[9]->id, 'user_id' => 3, 'assigned_by' => 1],
                ['task_id' => $tasks[10]->id, 'user_id' => 2, 'assigned_by' => 3],
                ['task_id' => $tasks[11]->id, 'user_id' => 2, 'assigned_by' => 2],
                ['task_id' => $tasks[12]->id, 'user_id' => 1, 'assigned_by' => 2],
                ['task_id' => $tasks[13]->id, 'user_id' => 3, 'assigned_by' => 1],
                ['task_id' => $tasks[14]->id, 'user_id' => 1, 'assigned_by' => 3],
                ['task_id' => $tasks[15]->id, 'user_id' => 2, 'assigned_by' => 1],
                ['task_id' => $tasks[16]->id, 'user_id' => 3, 'assigned_by' => 1],
                ['task_id' => $tasks[17]->id, 'user_id' => 1, 'assigned_by' => 2],
                ['task_id' => $tasks[18]->id, 'user_id' => 2, 'assigned_by' => 1],
                ['task_id' => $tasks[19]->id, 'user_id' => 3, 'assigned_by' => 2],
                ['task_id' => $tasks[20]->id, 'user_id' => 1, 'assigned_by' => 3],
            ];

            foreach ($taskAssigneeData as $assignee) {
                TaskAssignee::create($assignee);
            }

            // Create Task Relations
            $taskRelationData = [
                ['task_id' => $tasks[0]->id, 'related_task_id' => $tasks[1]->id, 'relation_type_id' => 1], // Setup DB depends on Design UI
                ['task_id' => $tasks[1]->id, 'related_task_id' => $tasks[2]->id, 'relation_type_id' => 1], // Design UI depends on Auth Module
                ['task_id' => $tasks[3]->id, 'related_task_id' => $tasks[4]->id, 'relation_type_id' => 2], // Student Dashboard related to Integration
                ['task_id' => $tasks[5]->id, 'related_task_id' => $tasks[6]->id, 'relation_type_id' => 1], // UI Design depends on Task Management
                ['task_id' => $tasks[6]->id, 'related_task_id' => $tasks[7]->id, 'relation_type_id' => 4], // Task Management subtask of Push Notifications
                ['task_id' => $tasks[8]->id, 'related_task_id' => $tasks[9]->id, 'relation_type_id' => 1], // API Architecture depends on OAuth2
                ['task_id' => $tasks[10]->id, 'related_task_id' => $tasks[11]->id, 'relation_type_id' => 1], // Literature Review depends on ML Model
                ['task_id' => $tasks[12]->id, 'related_task_id' => $tasks[13]->id, 'relation_type_id' => 2], // Social Media related to Google Ads
                ['task_id' => $tasks[14]->id, 'related_task_id' => $tasks[15]->id, 'relation_type_id' => 2], // Homepage redesign related to Performance
                ['task_id' => $tasks[16]->id, 'related_task_id' => $tasks[17]->id, 'relation_type_id' => 1], // Industry Analysis depends on Course Design
                ['task_id' => $tasks[18]->id, 'related_task_id' => $tasks[19]->id, 'relation_type_id' => 1], // Infrastructure evaluation depends on Procurement
                ['task_id' => $tasks[20]->id, 'related_task_id' => $tasks[19]->id, 'relation_type_id' => 2], // Last task related to another
            ];

            foreach ($taskRelationData as $relation) {
                TaskRelation::create($relation);
            }

            // Create Comments for various tasks
            $commentData = [
                [
                    'uuid' => Str::uuid(),
                    'task_id' => $tasks[0]->id,
                    'created_by' => 2,
                    'updated_by' => 2,
                    'comment' => 'Database schema sudah 80% selesai. Masih perlu review untuk tabel relasi mata kuliah.',
                    'created_at' => now()->subDays(2),
                    'updated_at' => now()->subDays(2)
                ],
                [
                    'uuid' => Str::uuid(),
                    'task_id' => $tasks[0]->id,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'comment' => 'Good progress! Pastikan foreign key constraints sudah benar semua.',
                    'created_at' => now()->subDays(1),
                    'updated_at' => now()->subDays(1)
                ],
                [
                    'uuid' => Str::uuid(),
                    'task_id' => $tasks[3]->id,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'comment' => 'Dashboard design looks great! User testing results very positive.',
                    'created_at' => now()->subHours(12),
                    'updated_at' => now()->subHours(12)
                ],
                [
                    'uuid' => Str::uuid(),
                    'task_id' => 6,
                    'created_by' => 3,
                    'updated_by' => 3,
                    'comment' => 'UI mockups completed and approved by stakeholders.',
                    'created_at' => now()->subDays(3),
                    'updated_at' => now()->subDays(3)
                ],
                [
                    'uuid' => Str::uuid(),
                    'task_id' => 7,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'comment' => 'Task management API endpoints sudah ready untuk testing.',
                    'created_at' => now()->subHours(8),
                    'updated_at' => now()->subHours(8)
                ],
                [
                    'uuid' => Str::uuid(),
                    'task_id' => 9,
                    'created_by' => 3,
                    'updated_by' => 1,
                    'comment' => 'API architecture document telah direview dan disetujui tim.',
                    'created_at' => now()->subHours(15),
                    'updated_at' => now()->subHours(10)
                ],
                [
                    'uuid' => Str::uuid(),
                    'task_id' => 11,
                    'created_by' => 2,
                    'updated_by' => 2,
                    'comment' => 'Literature review selesai dengan 50+ paper yang relevan.',
                    'created_at' => now()->subDays(6),
                    'updated_at' => now()->subDays(6)
                ],
                [
                    'uuid' => Str::uuid(),
                    'task_id' => 13,
                    'created_by' => 3,
                    'updated_by' => 1,
                    'comment' => 'Konten social media untuk minggu ini sudah siap publish.',
                    'created_at' => now()->subHours(18),
                    'updated_at' => now()->subHours(16)
                ],
                [
                    'uuid' => Str::uuid(),
                    'task_id' => 15,
                    'created_by' => 1,
                    'updated_by' => 3,
                    'comment' => 'Homepage redesign mendapat feedback positif dari user testing.',
                    'created_at' => now()->subHours(20),
                    'updated_at' => now()->subHours(18)
                ],
                [
                    'uuid' => Str::uuid(),
                    'task_id' => 19,
                    'created_by' => 2,
                    'updated_by' => 3,
                    'comment' => 'Infrastructure evaluation report sudah completed dan reviewed.',
                    'created_at' => now()->subDays(9),
                    'updated_at' => now()->subDays(8)
                ],
                [
                    'uuid' => Str::uuid(),
                    'task_id' => 23,
                    'created_by' => 1,
                    'updated_by' => 3,
                    'comment' => 'Venue sudah booked, tinggal finalisasi technical setup.',
                    'created_at' => now()->subHours(5),
                    'updated_at' => now()->subHours(4)
                ],
                [
                    'uuid' => Str::uuid(),
                    'task_id' => 24,
                    'created_by' => 3,
                    'updated_by' => 1,
                    'comment' => 'Data verifikasi lulusan 95% complete, masih ada beberapa yang perlu konfirmasi.',
                    'created_at' => now()->subHours(7),
                    'updated_at' => now()->subHours(6)
                ]
            ];

            foreach ($commentData as $comment) {
                Comment::create($comment);
            }

            // Create Attachments for tasks and comments
            $attachmentData = [
                [
                    'uuid' => Str::uuid(),
                    'model_type' => 'App\\Models\\Task',
                    'model_id' => 1,
                    'created_by' => 2,
                    'updated_by' => 2,
                    'file_path' => 'attachments/tasks/database_schema_v1.pdf',
                    'original_filename' => 'database_schema_v1.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 2048000,
                    'created_at' => now()->subDays(3),
                    'updated_at' => now()->subDays(3)
                ],
                [
                    'uuid' => Str::uuid(),
                    'model_type' => 'App\\Models\\Task',
                    'model_id' => 2,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'file_path' => 'attachments/tasks/ui_mockup_student_portal.figma',
                    'original_filename' => 'ui_mockup_student_portal.figma',
                    'mime_type' => 'application/octet-stream',
                    'file_size' => 5120000,
                    'created_at' => now()->subDays(4),
                    'updated_at' => now()->subDays(4)
                ],
                [
                    'uuid' => Str::uuid(),
                    'model_type' => 'App\\Models\\Task',
                    'model_id' => 6,
                    'created_by' => 3,
                    'updated_by' => 3,
                    'file_path' => 'attachments/tasks/mobile_app_wireframes.png',
                    'original_filename' => 'mobile_app_wireframes.png',
                    'mime_type' => 'image/png',
                    'file_size' => 3072000,
                    'created_at' => now()->subDays(5),
                    'updated_at' => now()->subDays(5)
                ],
                [
                    'uuid' => Str::uuid(),
                    'model_type' => 'App\\Models\\Task',
                    'model_id' => 9,
                    'created_by' => 3,
                    'updated_by' => 1,
                    'file_path' => 'attachments/tasks/api_architecture_diagram.pdf',
                    'original_filename' => 'api_architecture_diagram.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 1536000,
                    'created_at' => now()->subDays(8),
                    'updated_at' => now()->subDays(6)
                ],
                [
                    'uuid' => Str::uuid(),
                    'model_type' => 'App\\Models\\Task',
                    'model_id' => 11,
                    'created_by' => 2,
                    'updated_by' => 2,
                    'file_path' => 'attachments/tasks/literature_review_summary.docx',
                    'original_filename' => 'literature_review_summary.docx',
                    'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'file_size' => 4096000,
                    'created_at' => now()->subDays(15),
                    'updated_at' => now()->subDays(15)
                ],
                [
                    'uuid' => Str::uuid(),
                    'model_type' => 'App\\Models\\Task',
                    'model_id' => 13,
                    'created_by' => 3,
                    'updated_by' => 1,
                    'file_path' => 'attachments/tasks/social_media_content_calendar.xlsx',
                    'original_filename' => 'social_media_content_calendar.xlsx',
                    'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'file_size' => 512000,
                    'created_at' => now()->subDays(6),
                    'updated_at' => now()->subDays(4)
                ],
                [
                    'uuid' => Str::uuid(),
                    'model_type' => 'App\\Models\\Comment',
                    'model_id' => 3,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'file_path' => 'attachments/comments/user_testing_results.pdf',
                    'original_filename' => 'user_testing_results.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 1024000,
                    'created_at' => now()->subHours(10),
                    'updated_at' => now()->subHours(10)
                ],
                [
                    'uuid' => Str::uuid(),
                    'model_type' => 'App\\Models\\Task',
                    'model_id' => 19,
                    'created_by' => 2,
                    'updated_by' => 3,
                    'file_path' => 'attachments/tasks/infrastructure_evaluation_report.pdf',
                    'original_filename' => 'infrastructure_evaluation_report.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 2560000,
                    'created_at' => now()->subDays(12),
                    'updated_at' => now()->subDays(10)
                ]
            ];

            foreach ($attachmentData as $attachment) {
                Attachment::create($attachment);
            }

            // Create Notifications for users
            $notificationData = [
                [
                    'uuid' => Str::uuid(),
                    'user_id' => 1,
                    'type' => 'info',
                    'title' => 'New Task Assignment',
                    'message' => 'You have been assigned to task "Design User Interface" in project Sistem Informasi Akademik.',
                    'detail_url' => '/projects/1/tasks/2',
                    'is_read' => true,
                    'read_at' => now()->subHours(2),
                    'created_at' => now()->subDays(3),
                    'updated_at' => now()->subHours(2)
                ],
                [
                    'uuid' => Str::uuid(),
                    'user_id' => 2,
                    'type' => 'warning',
                    'title' => 'Task Deadline Approaching',
                    'message' => 'Task "Setup Database Schema" is due in 7 days.',
                    'detail_url' => '/projects/1/tasks/1',
                    'is_read' => false,
                    'read_at' => null,
                    'created_at' => now()->subHours(6),
                    'updated_at' => now()->subHours(6)
                ],
                [
                    'uuid' => Str::uuid(),
                    'user_id' => 3,
                    'type' => 'success',
                    'title' => 'Task Completed',
                    'message' => 'Task "Design Mobile App UI/UX" has been marked as completed.',
                    'detail_url' => '/projects/3/tasks/6',
                    'is_read' => true,
                    'read_at' => now()->subDays(1),
                    'created_at' => now()->subDays(2),
                    'updated_at' => now()->subDays(1)
                ],
                [
                    'uuid' => Str::uuid(),
                    'user_id' => 1,
                    'type' => 'info',
                    'title' => 'New Comment Added',
                    'message' => 'Burhan Mafazi added a comment to task "Design API Architecture".',
                    'detail_url' => '/projects/4/tasks/9',
                    'is_read' => false,
                    'read_at' => null,
                    'created_at' => now()->subHours(8),
                    'updated_at' => now()->subHours(8)
                ],
                [
                    'uuid' => Str::uuid(),
                    'user_id' => 2,
                    'type' => 'error',
                    'title' => 'Task Overdue',
                    'message' => 'Task "Integration with Academic System" is overdue by 2 days.',
                    'detail_url' => '/projects/2/tasks/5',
                    'is_read' => false,
                    'read_at' => null,
                    'created_at' => now()->subHours(12),
                    'updated_at' => now()->subHours(12)
                ],
                [
                    'uuid' => Str::uuid(),
                    'user_id' => 3,
                    'type' => 'info',
                    'title' => 'Project Status Update',
                    'message' => 'Project "Mobile App ProMan" status has been updated.',
                    'detail_url' => '/projects/3',
                    'is_read' => true,
                    'read_at' => now()->subHours(4),
                    'created_at' => now()->subHours(18),
                    'updated_at' => now()->subHours(4)
                ],
                [
                    'uuid' => Str::uuid(),
                    'user_id' => 1,
                    'type' => 'warning',
                    'title' => 'High Priority Task',
                    'message' => 'High priority task "Venue Booking and Setup" requires immediate attention.',
                    'detail_url' => '/projects/11/tasks/23',
                    'is_read' => false,
                    'read_at' => null,
                    'created_at' => now()->subHours(3),
                    'updated_at' => now()->subHours(3)
                ],
                [
                    'uuid' => Str::uuid(),
                    'user_id' => 2,
                    'type' => 'success',
                    'title' => 'Milestone Achieved',
                    'message' => 'Project "Penelitian AI & Machine Learning" reached 50% completion.',
                    'detail_url' => '/projects/5',
                    'is_read' => true,
                    'read_at' => now()->subDays(4),
                    'created_at' => now()->subDays(5),
                    'updated_at' => now()->subDays(4)
                ],
                [
                    'uuid' => Str::uuid(),
                    'user_id' => 3,
                    'type' => 'info',
                    'title' => 'New File Uploaded',
                    'message' => 'A new file has been uploaded to task "Create Social Media Content".',
                    'detail_url' => '/projects/6/tasks/13',
                    'is_read' => false,
                    'read_at' => null,
                    'created_at' => now()->subHours(14),
                    'updated_at' => now()->subHours(14)
                ],
                [
                    'uuid' => Str::uuid(),
                    'user_id' => 1,
                    'type' => 'warning',
                    'title' => 'Review Required',
                    'message' => 'Task "Graduate Data Verification" is waiting for your review.',
                    'detail_url' => '/projects/11/tasks/24',
                    'is_read' => false,
                    'read_at' => null,
                    'created_at' => now()->subHours(5),
                    'updated_at' => now()->subHours(5)
                ]
            ];

            foreach ($notificationData as $notification) {
                Notification::create($notification);
            }

            DB::commit();
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}