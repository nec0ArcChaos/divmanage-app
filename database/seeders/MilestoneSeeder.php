<?php

namespace Database\Seeders;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class MilestoneSeeder extends Seeder
{
    public function run(): void
    {
        $andi  = User::where('email', 'andi.rahmat@company.co.id')->first();
        $budi  = User::where('email', 'budi.santoso@company.co.id')->first();
        $fajar = User::where('email', 'fajar.hidayat@company.co.id')->first();
        $rina  = User::where('email', 'rina.wulandari@company.co.id')->first();
        $hadi  = User::where('email', 'hadi.kurniawan@company.co.id')->first();

        $milestones = [
            // ERP System Migration
            'ERP System Migration' => [
                ['Phase 1: Database Schema Migration',     '2026-01-31', 'completed', $budi,  now()->subDays(40)],
                ['Phase 2: Core Modules Go-Live',          '2026-02-28', 'completed', $budi,  now()->subDays(10)],
                ['Phase 3: Reporting & Analytics Module',  '2026-03-28', 'in_progress', $budi, null],
            ],
            // Mobile App Redesign
            'Mobile App Redesign' => [
                ['Design System & Wireframes',             '2026-02-15', 'completed', $andi,  now()->subDays(20)],
                ['Alpha Release – Core Screens',           '2026-03-15', 'in_progress', $andi, null],
                ['Beta Testing & Bug Fix Round',           '2026-04-10', 'pending', $andi,    null],
            ],
            // API Gateway v2
            'API Gateway v2' => [
                ['Auth Middleware & Rate Limiting',        '2026-01-20', 'completed', $fajar, now()->subDays(49)],
                ['Beta Release v2.1',                      '2026-02-28', 'completed', $fajar, now()->subDays(10)],
                ['Production Cutover',                     '2026-03-15', 'in_progress', $fajar, null],
            ],
            // Data Warehouse Setup
            'Data Warehouse Setup' => [
                ['Data Source Mapping & ERD',              '2026-03-01', 'completed', $rina,  now()->subDays(9)],
                ['ETL Pipeline Development',               '2026-04-01', 'pending', $rina,    null],
                ['Reporting Layer & Dashboards',           '2026-05-01', 'pending', $rina,    null],
            ],
            // SSO Integration
            'SSO Integration' => [
                ['SAML 2.0 Integration',                   '2025-12-15', 'completed', $andi,  now()->subDays(85)],
                ['OAuth2 Token Service',                   '2026-01-20', 'completed', $andi,  now()->subDays(49)],
                ['User Provisioning & Go-Live',            '2026-03-02', 'completed', $andi,  now()->subDays(8)],
            ],
            // Internal Monitoring Stack
            'Internal Monitoring Stack' => [
                ['Prometheus & Alertmanager Setup',        '2026-02-01', 'completed', $hadi,  now()->subDays(37)],
                ['Grafana Dashboards',                     '2026-03-01', 'completed', $hadi,  now()->subDays(9)],
                ['Loki Log Aggregation & Alerting Rules',  '2026-04-20', 'pending', $hadi,    null],
            ],
        ];

        foreach ($milestones as $projectName => $items) {
            $project = Project::where('name', $projectName)->first();
            if (! $project) {
                continue;
            }

            foreach ($items as [$title, $dueDate, $status, $creator, $completedAt]) {
                Milestone::create([
                    'project_id'   => $project->id,
                    'title'        => $title,
                    'due_date'     => $dueDate,
                    'status'       => $status,
                    'completed_at' => $completedAt,
                    'created_by'   => $creator->id,
                ]);
            }
        }
    }
}
