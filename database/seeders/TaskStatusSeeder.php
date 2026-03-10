<?php

namespace Database\Seeders;

use App\Models\TaskStatus;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class TaskStatusSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::first();

        $statuses = [
            ['name' => 'Backlog',     'slug' => 'backlog',     'color' => '#9ca3b0', 'position' => 0, 'is_default' => false, 'is_done' => false],
            ['name' => 'To Do',       'slug' => 'todo',        'color' => '#5994ff', 'position' => 1, 'is_default' => true,  'is_done' => false],
            ['name' => 'In Progress', 'slug' => 'in_progress', 'color' => '#f59e0b', 'position' => 2, 'is_default' => false, 'is_done' => false],
            ['name' => 'Code Review', 'slug' => 'code_review', 'color' => '#8b5cf6', 'position' => 3, 'is_default' => false, 'is_done' => false],
            ['name' => 'Testing',     'slug' => 'testing',     'color' => '#f97316', 'position' => 4, 'is_default' => false, 'is_done' => false],
            ['name' => 'Done',        'slug' => 'done',        'color' => '#22c55e', 'position' => 5, 'is_default' => false, 'is_done' => true],
        ];

        foreach ($statuses as $status) {
            TaskStatus::create(array_merge($status, ['workspace_id' => $workspace->id]));
        }
    }
}
