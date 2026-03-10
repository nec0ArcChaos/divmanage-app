<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    private array $statuses = [];

    public function run(): void
    {
        // Cache semua status by slug
        foreach (TaskStatus::all() as $s) {
            $this->statuses[$s->slug] = $s->id;
        }

        $andi  = User::where('email', 'andi.rahmat@company.co.id')->first();
        $budi  = User::where('email', 'budi.santoso@company.co.id')->first();
        $dina  = User::where('email', 'dina.permata@company.co.id')->first();
        $fajar = User::where('email', 'fajar.hidayat@company.co.id')->first();
        $rina  = User::where('email', 'rina.wulandari@company.co.id')->first();
        $hadi  = User::where('email', 'hadi.kurniawan@company.co.id')->first();
        $sari  = User::where('email', 'sari.dewi@company.co.id')->first();
        $rizky = User::where('email', 'rizky.pratama@company.co.id')->first();

        // ============================================================
        // ERP SYSTEM MIGRATION (36 tasks)
        // ============================================================
        $erp = Project::where('name', 'ERP System Migration')->first();
        $erpTasks = [
            // Done (26 tasks ~72%)
            ['Audit existing ERP modules',                  'done',        'high',     $budi,  '2026-01-20'],
            ['Map legacy DB schema to new structure',       'done',        'high',     $fajar, '2026-01-25'],
            ['Setup microservices scaffolding',             'done',        'medium',   $fajar, '2026-01-28'],
            ['Migrate Users module',                        'done',        'high',     $budi,  '2026-02-05'],
            ['Migrate Finance module – GL',                 'done',        'critical', $budi,  '2026-02-10'],
            ['Migrate Finance module – AR/AP',              'done',        'critical', $budi,  '2026-02-12'],
            ['Migrate Inventory module',                    'done',        'high',     $fajar, '2026-02-15'],
            ['Migrate HR module',                           'done',        'medium',   $fajar, '2026-02-18'],
            ['Data validation – Phase 1',                   'done',        'high',     $rizky, '2026-02-20'],
            ['Performance baseline testing',                'done',        'medium',   $rizky, '2026-02-22'],
            ['API contracts for inter-service communication','done',       'high',     $budi,  '2026-02-24'],
            ['Docker containerization',                     'done',        'medium',   $hadi,  '2026-02-25'],
            ['CI pipeline for migration scripts',           'done',        'low',      $hadi,  '2026-02-26'],
            ['UAT – Finance modules',                       'done',        'critical', $rizky, '2026-02-28'],
            ['UAT – HR module',                             'done',        'high',     $rizky, '2026-03-01'],
            ['UAT – Inventory module',                      'done',        'high',     $rizky, '2026-03-02'],
            ['Fix UAT-reported bugs – Finance',             'done',        'critical', $budi,  '2026-03-04'],
            ['Fix UAT-reported bugs – Inventory',           'done',        'medium',   $fajar, '2026-03-05'],
            ['Deploy Phase 1 to staging',                   'done',        'high',     $hadi,  '2026-03-07'],
            ['Stakeholder sign-off – Phase 1',              'done',        'high',     $andi,  '2026-03-08'],
            ['Write rollback runbooks',                     'done',        'medium',   $budi,  '2026-03-08'],
            ['Production go-live – Phase 1',                'done',        'critical', $hadi,  '2026-03-09'],
            ['Post-go-live monitoring – 48h',               'done',        'high',     $hadi,  '2026-03-10'],
            ['Database index optimization',                 'done',        'medium',   $fajar, '2026-03-10'],
            ['Reporting module DB schema design',           'done',        'high',     $rina,  '2026-03-10'],
            ['Archive old DB tables',                       'done',        'low',      $budi,  '2026-03-10'],
            // In Progress / Code Review (7 tasks)
            ['Build reporting API endpoints',               'in_progress', 'high',     $budi,  '2026-03-18'],
            ['Setup staging environment',                   'todo',        'medium',   $andi,  '2026-03-12'],  // Andi's task shown in dashboard
            ['Integrate analytics engine',                  'in_progress', 'high',     $fajar, '2026-03-20'],
            ['Write unit tests for reporting module',       'code_review', 'medium',   $rizky, '2026-03-18'],
            ['Database schema migration script',            'todo',        'high',     $andi,  '2026-03-16'],  // Andi's task
            ['Dashboard widget components',                 'in_progress', 'medium',   $budi,  '2026-03-22'],
            ['Phase 3 UAT planning',                        'todo',        'low',      $rizky, '2026-03-25'],
            // Backlog (3 tasks)
            ['Performance load testing – Phase 3',          'backlog',     'medium',   null,   '2026-03-28'],
            ['Final data reconciliation',                   'backlog',     'high',     null,   '2026-03-26'],
            ['Documentation & runbooks – Phase 3',          'backlog',     'low',      null,   '2026-03-28'],
        ];
        $this->seedTasks($erp, $erpTasks, $andi);

        // ============================================================
        // MOBILE APP REDESIGN (28 tasks)
        // ============================================================
        $mobile = Project::where('name', 'Mobile App Redesign')->first();
        $mobileTasks = [
            // Done (13 tasks ~45%)
            ['UX research & user interviews',               'done',        'high',     $dina,  '2026-02-08'],
            ['Competitor analysis',                         'done',        'medium',   $dina,  '2026-02-10'],
            ['Information architecture redesign',           'done',        'high',     $dina,  '2026-02-12'],
            ['Design system – typography & colors',         'done',        'high',     $dina,  '2026-02-15'],
            ['Design system – components',                  'done',        'high',     $dina,  '2026-02-18'],
            ['Wireframes – Login & Onboarding',             'done',        'medium',   $dina,  '2026-02-20'],
            ['Wireframes – Dashboard & Home',               'done',        'medium',   $dina,  '2026-02-22'],
            ['High-fidelity mockups – Login',               'done',        'medium',   $dina,  '2026-02-25'],
            ['High-fidelity mockups – Home screen',         'done',        'medium',   $sari,  '2026-02-27'],
            ['Setup React Native project',                  'done',        'high',     $sari,  '2026-03-01'],
            ['Implement auth screens',                      'done',        'critical', $sari,  '2026-03-03'],
            ['Stakeholder review – Phase 1 designs',        'done',        'high',     $andi,  '2026-03-05'],
            ['Fix login timeout issue',                     'in_progress', 'critical', $andi,  '2026-03-09'],  // Andi's task shown in dashboard
            // In Progress
            ['Implement home screen',                       'in_progress', 'high',     $sari,  '2026-03-12'],
            ['Implement navigation shell',                  'in_progress', 'medium',   $sari,  '2026-03-14'],
            ['API integration – auth endpoints',            'code_review', 'high',     $sari,  '2026-03-12'],
            ['Push notification setup',                     'todo',        'medium',   $sari,  '2026-03-20'],
            // To Do
            ['Implement task list screen',                  'todo',        'high',     $sari,  '2026-03-22'],
            ['Implement profile screen',                    'todo',        'medium',   $dina,  '2026-03-25'],
            ['Offline mode support',                        'todo',        'high',     $sari,  '2026-03-28'],
            ['Unit tests – auth module',                    'todo',        'medium',   $rizky, '2026-03-25'],
            // Testing / Backlog
            ['Device compatibility testing – Android',      'backlog',     'high',     $rizky, '2026-04-02'],
            ['Device compatibility testing – iOS',          'backlog',     'high',     $rizky, '2026-04-02'],
            ['Accessibility audit',                         'backlog',     'medium',   $dina,  '2026-04-05'],
            ['Performance profiling',                       'backlog',     'medium',   $rizky, '2026-04-07'],
            ['Beta testing with 20 users',                  'backlog',     'high',     $andi,  '2026-04-08'],
            ['Crash analytics integration',                 'backlog',     'low',      $sari,  '2026-04-09'],
            ['App store submission prep',                   'backlog',     'medium',   $sari,  '2026-04-10'],
        ];
        $this->seedTasks($mobile, $mobileTasks, $andi);

        // ============================================================
        // API GATEWAY v2 (22 tasks)
        // ============================================================
        $api = Project::where('name', 'API Gateway v2')->first();
        $apiTasks = [
            // Done (19 tasks ~88%)
            ['Architecture design & ADR',                   'done',        'high',     $fajar, '2025-12-10'],
            ['Setup Kong gateway instance',                 'done',        'high',     $fajar, '2025-12-15'],
            ['Auth plugin – JWT validation',                'done',        'critical', $fajar, '2025-12-20'],
            ['Auth plugin – API key rotation',              'done',        'high',     $fajar, '2025-12-22'],
            ['Rate limiting plugin',                        'done',        'high',     $budi,  '2025-12-28'],
            ['Request logging middleware',                  'done',        'medium',   $budi,  '2026-01-05'],
            ['Response caching layer',                      'done',        'medium',   $fajar, '2026-01-08'],
            ['API versioning strategy',                     'done',        'high',     $fajar, '2026-01-10'],
            ['Developer portal – documentation',            'done',        'medium',   $budi,  '2026-01-15'],
            ['Review API documentation',                    'in_progress', 'high',     $andi,  '2026-03-10'],  // Andi's task in dashboard
            ['Upstream service routing config',             'done',        'high',     $hadi,  '2026-01-20'],
            ['Health check & circuit breaker',              'done',        'medium',   $fajar, '2026-01-22'],
            ['Load balancing config',                       'done',        'high',     $hadi,  '2026-01-25'],
            ['Blue-green deployment setup',                 'done',        'high',     $hadi,  '2026-02-01'],
            ['Integration tests – all upstream services',   'done',        'high',     $rizky, '2026-02-10'],
            ['Security audit',                              'done',        'critical', $rizky, '2026-02-15'],
            ['Performance benchmark vs v1',                 'done',        'high',     $rizky, '2026-02-20'],
            ['Staging deployment',                          'done',        'high',     $hadi,  '2026-02-25'],
            ['Beta v2.1 release',                           'done',        'critical', $fajar, '2026-02-28'],
            // Code Review / Testing
            ['Write unit tests for auth module',            'code_review', 'low',      $andi,  '2026-03-14'],  // Andi's task in dashboard
            ['Final production cutover runbook',            'testing',     'critical', $hadi,  '2026-03-12'],
            ['Cutover to production',                       'todo',        'critical', $fajar, '2026-03-15'],
        ];
        $this->seedTasks($api, $apiTasks, $andi);

        // ============================================================
        // DATA WAREHOUSE SETUP (18 tasks)
        // ============================================================
        $dw = Project::where('name', 'Data Warehouse Setup')->first();
        $dwTasks = [
            ['Define data requirements – Finance',          'done',        'high',     $rina,  '2026-02-20'],
            ['Define data requirements – HR',               'done',        'high',     $rina,  '2026-02-22'],
            ['ERD – Dimensional model design',              'done',        'high',     $rina,  '2026-02-28'],
            ['Source system analysis',                      'done',        'medium',   $budi,  '2026-02-28'],
            ['Setup dbt project scaffold',                  'done',        'medium',   $fajar, '2026-03-02'],
            ['Data pipeline – Finance source',              'in_progress', 'high',     $rina,  '2026-03-18'],
            ['Data pipeline – HR source',                   'todo',        'high',     $rina,  '2026-03-25'],
            ['Data pipeline – ERP source',                  'todo',        'high',     $fajar, '2026-04-01'],
            ['Setup Redshift cluster',                      'in_progress', 'high',     $hadi,  '2026-03-15'],
            ['Data quality validation rules',               'todo',        'medium',   $rina,  '2026-03-28'],
            ['Build staging layer models',                  'todo',        'high',     $fajar, '2026-04-01'],
            ['Build core mart models – Finance',            'backlog',     'high',     $rina,  '2026-04-10'],
            ['Build core mart models – HR',                 'backlog',     'medium',   $rina,  '2026-04-15'],
            ['Orchestration with Airflow',                  'backlog',     'high',     $hadi,  '2026-04-20'],
            ['Reporting dashboard – Metabase',              'backlog',     'high',     $rina,  '2026-04-25'],
            ['User acceptance testing',                     'backlog',     'high',     $rizky, '2026-05-01'],
            ['Data governance documentation',               'backlog',     'medium',   $rina,  '2026-04-30'],
            ['Training for stakeholders',                   'backlog',     'low',      $andi,  '2026-05-01'],
        ];
        $this->seedTasks($dw, $dwTasks, $andi);

        // ============================================================
        // SSO INTEGRATION (14 tasks – all done)
        // ============================================================
        $sso = Project::where('name', 'SSO Integration')->first();
        $ssoTasks = [
            ['SSO requirements gathering',                  'done', 'high',     $andi,  '2025-11-10'],
            ['SAML 2.0 IDP selection',                     'done', 'high',     $andi,  '2025-11-15'],
            ['SAML SP configuration – App 1',              'done', 'high',     $budi,  '2025-11-25'],
            ['SAML SP configuration – App 2',              'done', 'high',     $fajar, '2025-11-28'],
            ['SAML SP configuration – App 3',              'done', 'medium',   $budi,  '2025-12-05'],
            ['OAuth2 authorization server setup',           'done', 'critical', $fajar, '2025-12-10'],
            ['Token refresh flow implementation',           'done', 'high',     $fajar, '2025-12-15'],
            ['User provisioning from AD',                  'done', 'high',     $budi,  '2025-12-20'],
            ['Role mapping configuration',                 'done', 'high',     $andi,  '2025-12-25'],
            ['Security penetration testing',               'done', 'critical', $rizky, '2026-01-10'],
            ['UAT with 10 pilot users',                    'done', 'high',     $rizky, '2026-01-20'],
            ['Production deployment',                      'done', 'critical', $hadi,  '2026-02-15'],
            ['Deploy staging server',                      'done', 'high',     $andi,  '2026-02-20'],
            ['Project closure & handover docs',            'done', 'medium',   $andi,  '2026-03-02'],
        ];
        $this->seedTasks($sso, $ssoTasks, $andi);

        // ============================================================
        // INTERNAL MONITORING STACK (20 tasks)
        // ============================================================
        $mon = Project::where('name', 'Internal Monitoring Stack')->first();
        $monTasks = [
            ['Monitoring requirements analysis',            'done',        'high',     $hadi,  '2026-01-15'],
            ['Setup Prometheus server',                     'done',        'high',     $hadi,  '2026-01-20'],
            ['Configure node exporters – all servers',     'done',        'high',     $hadi,  '2026-01-25'],
            ['Setup Alertmanager',                          'done',        'high',     $hadi,  '2026-01-28'],
            ['Define initial alert rules',                  'done',        'medium',   $hadi,  '2026-02-01'],
            ['Setup Grafana instance',                      'done',        'high',     $budi,  '2026-02-05'],
            ['Build infrastructure overview dashboard',     'done',        'high',     $budi,  '2026-02-10'],
            ['Build application performance dashboard',     'done',        'high',     $budi,  '2026-02-15'],
            ['Build database metrics dashboard',            'done',        'medium',   $budi,  '2026-02-20'],
            ['Integrate with Slack alerts',                 'done',        'medium',   $hadi,  '2026-02-25'],
            ['Setup Loki for log aggregation',              'done',        'high',     $hadi,  '2026-03-01'],
            ['Configure Promtail agents',                   'done',        'high',     $hadi,  '2026-03-05'],
            ['Setup Loki log exploration in Grafana',       'done',        'medium',   $budi,  '2026-03-08'],
            // In Progress / Todo
            ['Define log-based alerting rules',             'in_progress', 'high',     $hadi,  '2026-03-20'],
            ['Setup on-call rotation in Alertmanager',      'in_progress', 'medium',   $hadi,  '2026-03-22'],
            ['Cost monitoring dashboard – cloud spend',     'todo',        'medium',   $budi,  '2026-03-28'],
            ['Distributed tracing setup – Tempo',           'todo',        'high',     $fajar, '2026-04-05'],
            ['Security event monitoring',                   'backlog',     'high',     $hadi,  '2026-04-10'],
            ['SLO/SLA tracking dashboards',                 'backlog',     'medium',   $budi,  '2026-04-15'],
            ['Runbook documentation',                       'backlog',     'low',      $hadi,  '2026-04-20'],
        ];
        $this->seedTasks($mon, $monTasks, $andi);

        // ============================================================
        // TASK COMMENTS (pada beberapa task aktif)
        // ============================================================
        $this->seedComments($andi, $budi, $dina, $fajar, $rina, $hadi, $rizky);
    }

    private function seedTasks(Project $project, array $tasks, User $creator): void
    {
        foreach ($tasks as $i => [$title, $statusSlug, $priority, $assignee, $deadline]) {
            $statusId = $this->statuses[$statusSlug];

            $task = Task::create([
                'project_id'     => $project->id,
                'task_status_id' => $statusId,
                'title'          => $title,
                'priority'       => $priority,
                'assigned_to'    => $assignee?->id,
                'created_by'     => $creator->id,
                'deadline'       => $deadline,
                'position'       => $i,
                'started_at'     => in_array($statusSlug, ['in_progress', 'code_review', 'testing', 'done'])
                    ? now()->subDays(rand(3, 20))
                    : null,
                'completed_at'   => $statusSlug === 'done'
                    ? now()->subDays(rand(1, 15))
                    : null,
            ]);
        }
    }

    private function seedComments(User ...$users): void
    {
        [$andi, $budi, $dina, $fajar, $rina, $hadi, $rizky] = $users;

        $commentSets = [
            [
                'task_title' => 'Setup staging environment',
                'comments'   => [
                    [$andi,  'Budi, bisa tolong konfirmasi spek server staging yang akan dipakai?'],
                    [$budi,  'Sudah cek, pakai t3.medium di AWS. Saya akan setup ini besok pagi.'],
                    [$hadi,  'Saya bantu setup Terraform config-nya ya, biar konsisten dengan prod.'],
                ],
            ],
            [
                'task_title' => 'Fix login timeout issue',
                'comments'   => [
                    [$dina,  'Bug ini di-report oleh 3 user field officers. Priority sangat tinggi.'],
                    [$budi,  'Root cause sudah ketemu: token refresh tidak handle race condition. Sedang fix.'],
                    [$rizky, 'Sudah bisa reproduce di emulator Android 12. Test case sudah disiapkan.'],
                    [$budi,  'Fix sudah di-push ke branch fix/login-timeout. Mohon di-review.'],
                ],
            ],
            [
                'task_title' => 'Review API documentation',
                'comments'   => [
                    [$fajar, 'Dokumentasi endpoint /auth sudah update ke versi 2.1. Mohon dicek Andi.'],
                    [$andi,  'Sudah saya review, ada 2 endpoint yang masih kurang contoh response error-nya.'],
                    [$fajar, 'Siap, akan saya lengkapi hari ini. Thanks feedbacknya.'],
                ],
            ],
            [
                'task_title' => 'Database schema migration script',
                'comments'   => [
                    [$rina,  'Script sudah bisa dijalankan di local, tapi perlu test di environment yang mirip prod.'],
                    [$budi,  'Saya akan bantu setup test environment khusus untuk ini minggu ini.'],
                    [$andi,  'Pastikan ada dry-run mode sebelum kita run di staging ya.'],
                ],
            ],
            [
                'task_title' => 'Define log-based alerting rules',
                'comments'   => [
                    [$hadi,  'Sudah define 15 alert rules untuk error rate dan latency. Butuh review dari tim.'],
                    [$budi,  'Rules untuk DB connection pool sudah bagus. Tambahkan threshold untuk memory leak?'],
                    [$hadi,  'Good point, akan saya tambahkan. Estimasi selesai akhir minggu ini.'],
                ],
            ],
        ];

        foreach ($commentSets as $set) {
            $task = Task::where('title', $set['task_title'])->first();
            if (! $task) {
                continue;
            }
            foreach ($set['comments'] as $delay => [$user, $body]) {
                TaskComment::create([
                    'task_id'    => $task->id,
                    'user_id'    => $user->id,
                    'body'       => $body,
                    'created_at' => now()->subHours((count($set['comments']) - $delay) * 3),
                    'updated_at' => now()->subHours((count($set['comments']) - $delay) * 3),
                ]);
            }
        }
    }
}
