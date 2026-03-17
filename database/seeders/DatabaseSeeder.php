<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            MemberStatusSeeder::class,
            JobTitleSeeder::class,
            UserSeeder::class,
            WorkspaceSeeder::class,
            TaskStatusSeeder::class,
            ProjectSeeder::class,
            MilestoneSeeder::class,
            TaskSeeder::class,
            ActivityLogSeeder::class,
            NotificationPreferenceSeeder::class,
        ]);
    }
}
