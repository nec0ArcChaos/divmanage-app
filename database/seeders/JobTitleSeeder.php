<?php

namespace Database\Seeders;

use App\Models\JobTitle;
use Illuminate\Database\Seeder;

class JobTitleSeeder extends Seeder
{
    public function run(): void
    {
        $titles = [
            'UI/UX',
            'Frontend Developer',
            'Backend Developer',
            'QA',
            'IT Division Lead',
            'DevOps Engineer',
        ];

        foreach ($titles as $name) {
            JobTitle::create(['name' => $name]);
        }
    }
}
