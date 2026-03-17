<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['slug' => 'admin',           'name' => 'Admin',           'sort_order' => 1],
            ['slug' => 'project_manager', 'name' => 'Project Manager', 'sort_order' => 2],
            ['slug' => 'developer',       'name' => 'Developer',       'sort_order' => 3],
            ['slug' => 'qa',              'name' => 'QA',              'sort_order' => 4],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
