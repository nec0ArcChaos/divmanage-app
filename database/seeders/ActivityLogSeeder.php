<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
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
        $rizky = User::where('email', 'rizky.pratama@company.co.id')->first();

        $erp    = Project::where('name', 'ERP System Migration')->first();
        $mobile = Project::where('name', 'Mobile App Redesign')->first();
        $api    = Project::where('name', 'API Gateway v2')->first();
        $dw     = Project::where('name', 'Data Warehouse Setup')->first();
        $sso    = Project::where('name', 'SSO Integration')->first();
        $mon    = Project::where('name', 'Internal Monitoring Stack')->first();

        $taskApiDoc    = Task::where('title', 'Review API documentation')->first();
        $taskLoginBug  = Task::where('title', 'Fix login timeout issue')->first();
        $taskDeploy    = Task::where('title', 'Deploy staging server')->first();
        $taskPipeline  = Task::where('title', 'Data pipeline – Finance source')->first();
        $taskStaging   = Task::where('title', 'Setup staging environment')->first();
        $taskGoLive    = Task::where('title', 'Production go-live – Phase 1')->first();
        $taskCutover   = Task::where('title', 'Cutover to production')->first();
        $taskAlerts    = Task::where('title', 'Define log-based alerting rules')->first();

        // 20 activity logs persis sesuai schema + tambahan realistis
        $logs = [
            [
                'user'         => $budi,
                'project'      => $api,
                'subject'      => $taskApiDoc,
                'action'       => 'moved',
                'description'  => 'moved task "API Authentication" → Code Review',
                'created_at'   => now()->subMinutes(14),
            ],
            [
                'user'         => $dina,
                'project'      => $mobile,
                'subject'      => $taskLoginBug,
                'action'       => 'created',
                'description'  => 'created task "Fix Login Timeout Bug"',
                'created_at'   => now()->subMinutes(38),
            ],
            [
                'user'         => $fajar,
                'project'      => $api,
                'subject'      => null,
                'action'       => 'completed',
                'description'  => 'completed milestone "Beta Release v2.1"',
                'created_at'   => now()->subHours(1)->subMinutes(12),
            ],
            [
                'user'         => $rina,
                'project'      => $dw,
                'subject'      => $taskPipeline,
                'action'       => 'commented',
                'description'  => 'commented on "Data Pipeline Architecture"',
                'created_at'   => now()->subHours(2)->subMinutes(5),
            ],
            [
                'user'         => $andi,
                'project'      => $sso,
                'subject'      => $taskDeploy,
                'action'       => 'assigned',
                'description'  => 'assigned task "Deploy Staging Server" to Budi Santoso',
                'created_at'   => now()->subHours(3),
            ],
            [
                'user'         => $hadi,
                'project'      => $erp,
                'subject'      => $taskGoLive,
                'action'       => 'completed',
                'description'  => 'completed task "Production go-live – Phase 1"',
                'created_at'   => now()->subHours(5),
            ],
            [
                'user'         => $rizky,
                'project'      => $api,
                'subject'      => $taskApiDoc,
                'action'       => 'moved',
                'description'  => 'moved task "Write unit tests for auth module" → Code Review',
                'created_at'   => now()->subHours(6)->subMinutes(30),
            ],
            [
                'user'         => $budi,
                'project'      => $erp,
                'subject'      => $taskStaging,
                'action'       => 'updated',
                'description'  => 'updated task "Setup staging environment" deadline to Mar 12',
                'created_at'   => now()->subHours(8),
            ],
            [
                'user'         => $fajar,
                'project'      => $api,
                'subject'      => $taskCutover,
                'action'       => 'created',
                'description'  => 'created task "Cutover to production"',
                'created_at'   => now()->subHours(10),
            ],
            [
                'user'         => $andi,
                'project'      => $mobile,
                'subject'      => $taskLoginBug,
                'action'       => 'assigned',
                'description'  => 'assigned task "Fix login timeout issue" to Sari Dewi',
                'created_at'   => now()->subHours(12),
            ],
            [
                'user'         => $hadi,
                'project'      => $mon,
                'subject'      => $taskAlerts,
                'action'       => 'updated',
                'description'  => 'updated "Define log-based alerting rules" status → In Progress',
                'created_at'   => now()->subHours(14),
            ],
            [
                'user'         => $rina,
                'project'      => $dw,
                'subject'      => null,
                'action'       => 'updated',
                'description'  => 'updated project "Data Warehouse Setup" progress to 31%',
                'created_at'   => now()->subHours(18),
            ],
            [
                'user'         => $budi,
                'project'      => $erp,
                'subject'      => $taskGoLive,
                'action'       => 'completed',
                'description'  => 'completed task "Post-go-live monitoring – 48h"',
                'created_at'   => now()->subHours(22),
            ],
            [
                'user'         => $dina,
                'project'      => $mobile,
                'subject'      => null,
                'action'       => 'updated',
                'description'  => 'updated project "Mobile App Redesign" progress to 45%',
                'created_at'   => now()->subDay()->subHours(2),
            ],
            [
                'user'         => $fajar,
                'project'      => $api,
                'subject'      => null,
                'action'       => 'completed',
                'description'  => 'completed task "Staging deployment"',
                'created_at'   => now()->subDay()->subHours(4),
            ],
            [
                'user'         => $rizky,
                'project'      => $erp,
                'subject'      => null,
                'action'       => 'completed',
                'description'  => 'completed task "UAT – Finance modules"',
                'created_at'   => now()->subDay()->subHours(6),
            ],
            [
                'user'         => $andi,
                'project'      => $erp,
                'subject'      => null,
                'action'       => 'completed',
                'description'  => 'completed "Stakeholder sign-off – Phase 1"',
                'created_at'   => now()->subDay()->subHours(8),
            ],
            [
                'user'         => $hadi,
                'project'      => $mon,
                'subject'      => null,
                'action'       => 'completed',
                'description'  => 'completed task "Setup Loki for log aggregation"',
                'created_at'   => now()->subDays(2)->subHours(3),
            ],
            [
                'user'         => $budi,
                'project'      => $erp,
                'subject'      => null,
                'action'       => 'created',
                'description'  => 'created task "Build reporting API endpoints"',
                'created_at'   => now()->subDays(2)->subHours(6),
            ],
            [
                'user'         => $rina,
                'project'      => $dw,
                'subject'      => null,
                'action'       => 'completed',
                'description'  => 'completed "ERD – Dimensional model design"',
                'created_at'   => now()->subDays(2)->subHours(10),
            ],
        ];

        foreach ($logs as $log) {
            $subject = $log['subject'];

            ActivityLog::create([
                'workspace_id' => $workspace->id,
                'user_id'      => $log['user']->id,
                'project_id'   => $log['project']?->id,
                'subject_type' => $subject ? Task::class : null,
                'subject_id'   => $subject?->id,
                'action'       => $log['action'],
                'description'  => $log['description'],
                'properties'   => null,
                'created_at'   => $log['created_at'],
            ]);
        }
    }
}
