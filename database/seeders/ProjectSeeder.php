<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::first();

        $andi  = User::where('email', 'andi.rahmat@company.co.id')->first();
        $budi  = User::where('email', 'budi.santoso@company.co.id')->first();
        $dina  = User::where('email', 'dina.permata@company.co.id')->first();
        $fajar = User::where('email', 'fajar.hidayat@company.co.id')->first();
        $rina  = User::where('email', 'rina.wulandari@company.co.id')->first();
        $hadi  = User::where('email', 'hadi.kurniawan@company.co.id')->first();
        $sari  = User::where('email', 'sari.dewi@company.co.id')->first();
        $rizky = User::where('email', 'rizky.pratama@company.co.id')->first();

        // ========================================================
        // 6 PROJECT UTAMA (matching schema artifacts)
        // ========================================================
        $mainProjects = [
            [
                'name'        => 'ERP System Migration',
                'description' => 'Full migration of legacy ERP modules to cloud-based microservices architecture with zero downtime requirement.',
                'color'       => '#3b6ff8',
                'status'      => 'active',
                'progress'    => 72,
                'start_date'  => '2026-01-15',
                'deadline'    => '2026-03-28',
                'pm'          => $budi,
                'members'     => [
                    [$andi,  'project_manager'],
                    [$budi,  'project_manager'],
                    [$fajar, 'developer'],
                    [$hadi,  'developer'],
                    [$rizky, 'qa'],
                ],
            ],
            [
                'name'        => 'Mobile App Redesign',
                'description' => 'Complete UI/UX overhaul of the internal mobile application for field officers, targeting improved usability and performance.',
                'color'       => '#f59e0b',
                'status'      => 'active',
                'progress'    => 45,
                'start_date'  => '2026-02-01',
                'deadline'    => '2026-04-10',
                'pm'          => $dina,
                'members'     => [
                    [$dina,  'project_manager'],
                    [$sari,  'developer'],
                    [$rizky, 'qa'],
                    [$andi,  'viewer'],
                ],
            ],
            [
                'name'        => 'API Gateway v2',
                'description' => 'Rebuild the central API gateway with rate limiting, auth middleware, versioning support, and improved observability.',
                'color'       => '#22c55e',
                'status'      => 'active',
                'progress'    => 88,
                'start_date'  => '2025-12-01',
                'deadline'    => '2026-03-15',
                'pm'          => $fajar,
                'members'     => [
                    [$fajar, 'project_manager'],
                    [$budi,  'developer'],
                    [$hadi,  'developer'],
                    [$rizky, 'qa'],
                ],
            ],
            [
                'name'        => 'Data Warehouse Setup',
                'description' => 'Design and implement a centralized data warehouse for business intelligence reporting across all departments.',
                'color'       => '#8b5cf6',
                'status'      => 'planning',
                'progress'    => 31,
                'start_date'  => '2026-02-15',
                'deadline'    => '2026-05-01',
                'pm'          => $rina,
                'members'     => [
                    [$rina,  'project_manager'],
                    [$budi,  'developer'],
                    [$fajar, 'developer'],
                    [$andi,  'viewer'],
                ],
            ],
            [
                'name'        => 'SSO Integration',
                'description' => 'Implement Single Sign-On across all internal applications using SAML 2.0 and OAuth2 protocols.',
                'color'       => '#22c55e',
                'status'      => 'completed',
                'progress'    => 100,
                'start_date'  => '2025-11-01',
                'deadline'    => '2026-03-02',
                'pm'          => $andi,
                'members'     => [
                    [$andi,  'project_manager'],
                    [$budi,  'developer'],
                    [$fajar, 'developer'],
                    [$rizky, 'qa'],
                ],
            ],
            [
                'name'        => 'Internal Monitoring Stack',
                'description' => 'Deploy and configure a full observability stack (Prometheus, Grafana, Loki) for all production services.',
                'color'       => '#f97316',
                'status'      => 'maintenance',
                'progress'    => 65,
                'start_date'  => '2026-01-10',
                'deadline'    => '2026-04-20',
                'pm'          => $hadi,
                'members'     => [
                    [$hadi,  'project_manager'],
                    [$budi,  'developer'],
                    [$andi,  'viewer'],
                ],
            ],
        ];

        foreach ($mainProjects as $data) {
            $project = Project::create([
                'workspace_id' => $workspace->id,
                'name'         => $data['name'],
                'slug'         => Str::slug($data['name']),
                'description'  => $data['description'],
                'color'        => $data['color'],
                'status'       => $data['status'],
                'progress'     => $data['progress'],
                'start_date'   => $data['start_date'],
                'deadline'     => $data['deadline'],
                'created_by'   => $andi->id,
            ]);

            foreach ($data['members'] as [$user, $role]) {
                ProjectMember::create([
                    'project_id' => $project->id,
                    'user_id'    => $user->id,
                    'role'       => $role,
                    'joined_at'  => now()->subDays(rand(10, 60)),
                ]);
            }
        }

        // ========================================================
        // 18 PROJECT TAMBAHAN (untuk stats: total 24 projects)
        // ========================================================

        // 6 Active tambahan
        $activeExtra = [
            ['Network Infrastructure Audit',   '#3b6ff8', 'active',    58, '2026-01-20', '2026-04-15', $hadi],
            ['Helpdesk System v2',              '#f59e0b', 'active',    40, '2026-02-10', '2026-05-10', $sari],
            ['HRIS Integration Module',         '#8b5cf6', 'active',    67, '2026-01-05', '2026-03-30', $budi],
            ['CI/CD Pipeline Setup',            '#22c55e', 'active',    55, '2026-02-01', '2026-04-01', $hadi],
            ['Email Gateway Migration',         '#f97316', 'active',    33, '2026-02-20', '2026-05-20', $fajar],
            ['Asset Management System',         '#3b6ff8', 'active',    78, '2025-12-15', '2026-03-20', $rina],
        ];

        // 6 Completed
        $completedExtra = [
            ['VPN Infrastructure Upgrade',      '#22c55e', 'completed', 100, '2025-08-01', '2025-10-15', $hadi],
            ['Backup & Recovery System',        '#22c55e', 'completed', 100, '2025-07-01', '2025-09-30', $budi],
            ['Office365 Migration',             '#22c55e', 'completed', 100, '2025-06-01', '2025-09-01', $andi],
            ['Firewall Policy Upgrade',         '#22c55e', 'completed', 100, '2025-05-15', '2025-07-15', $hadi],
            ['Server Virtualization Project',   '#22c55e', 'completed', 100, '2025-04-01', '2025-07-01', $budi],
            ['Wi-Fi Network Expansion',         '#22c55e', 'completed', 100, '2025-03-01', '2025-06-01', $hadi],
        ];

        // 3 Planning
        $planningExtra = [
            ['BI Dashboard Development',        '#8b5cf6', 'planning',  15, '2026-04-01', '2026-07-01', $rina],
            ['DevSecOps Implementation',        '#f97316', 'planning',  8,  '2026-04-15', '2026-08-01', $hadi],
            ['Knowledge Base Portal',           '#3b6ff8', 'planning',  20, '2026-03-20', '2026-06-30', $sari],
        ];

        // 3 Archived
        $archivedExtra = [
            ['Legacy CRM Decommission',         '#9ca3b0', 'archived',  95, '2024-06-01', '2024-12-31', $andi],
            ['Old Intranet Shutdown',            '#9ca3b0', 'archived',  100,'2024-03-01', '2024-08-01', $budi],
            ['Printer Network Migration',       '#9ca3b0', 'archived',  100,'2024-01-01', '2024-06-01', $hadi],
        ];

        $allExtra = array_merge($activeExtra, $completedExtra, $planningExtra, $archivedExtra);

        foreach ($allExtra as [$name, $color, $status, $progress, $start, $deadline, $pm]) {
            $project = Project::create([
                'workspace_id' => $workspace->id,
                'name'         => $name,
                'slug'         => Str::slug($name),
                'description'  => "Project {$name} untuk keperluan infrastruktur dan sistem IT Division.",
                'color'        => $color,
                'status'       => $status,
                'progress'     => $progress,
                'start_date'   => $start,
                'deadline'     => $deadline,
                'created_by'   => $andi->id,
            ]);

            // PM sebagai project_manager
            ProjectMember::create([
                'project_id' => $project->id,
                'user_id'    => $pm->id,
                'role'       => 'project_manager',
                'joined_at'  => now()->subDays(rand(30, 90)),
            ]);

            // 1-2 developer tambahan
            $devPool = [$budi, $fajar, $hadi, $sari, $rina];
            $devs = array_slice($devPool, rand(0, 2), 2);
            foreach ($devs as $dev) {
                if ($dev->id !== $pm->id) {
                    ProjectMember::create([
                        'project_id' => $project->id,
                        'user_id'    => $dev->id,
                        'role'       => 'developer',
                        'joined_at'  => now()->subDays(rand(20, 80)),
                    ]);
                }
            }
        }
    }
}
