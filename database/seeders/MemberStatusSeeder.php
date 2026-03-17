<?php

namespace Database\Seeders;

use App\Models\MemberStatus;
use Illuminate\Database\Seeder;

class MemberStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['slug' => 'active',   'name' => 'Active'],
            ['slug' => 'on_leave', 'name' => 'On Leave'],
            ['slug' => 'inactive', 'name' => 'Inactive'],
        ];

        foreach ($statuses as $status) {
            MemberStatus::create($status);
        }
    }
}
