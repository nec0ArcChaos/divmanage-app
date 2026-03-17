<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'               => 'Andi Rahmat',
                'email'              => 'andi.rahmat@company.co.id',
                'username'           => 'andi_rahmat',
                'password'           => Hash::make('password'),
                'job_title'          => 'IT Division Lead',
                'department'         => 'IT Division',
                'phone'              => '+62 812-0001-0001',
                'global_role'        => 'admin',
                'status'             => 'active',
                'email_verified_at'  => now(),
            ],
            [
                'name'               => 'Budi Santoso',
                'email'              => 'budi.santoso@company.co.id',
                'username'           => 'budi_santoso',
                'password'           => Hash::make('password'),
                'job_title'          => 'Senior Backend Developer',
                'department'         => 'IT Division',
                'phone'              => '+62 812-0001-0002',
                'global_role'        => 'project_manager',
                'status'             => 'active',
                'email_verified_at'  => now(),
            ],
            [
                'name'               => 'Dina Permata',
                'email'              => 'dina.permata@company.co.id',
                'username'           => 'dina_permata',
                'password'           => Hash::make('password'),
                'job_title'          => 'UI/UX Designer',
                'department'         => 'IT Division',
                'phone'              => '+62 812-0001-0003',
                'global_role'        => 'project_manager',
                'status'             => 'active',
                'email_verified_at'  => now(),
            ],
            [
                'name'               => 'Fajar Hidayat',
                'email'              => 'fajar.hidayat@company.co.id',
                'username'           => 'fajar_hidayat',
                'password'           => Hash::make('password'),
                'job_title'          => 'Backend Developer',
                'department'         => 'IT Division',
                'phone'              => '+62 812-0001-0004',
                'global_role'        => 'developer',
                'status'             => 'active',
                'email_verified_at'  => now(),
            ],
            [
                'name'               => 'Rina Wulandari',
                'email'              => 'rina.wulandari@company.co.id',
                'username'           => 'rina_wulandari',
                'password'           => Hash::make('password'),
                'job_title'          => 'Data Engineer',
                'department'         => 'IT Division',
                'phone'              => '+62 812-0001-0005',
                'global_role'        => 'developer',
                'status'             => 'active',
                'email_verified_at'  => now(),
            ],
            [
                'name'               => 'Hadi Kurniawan',
                'email'              => 'hadi.kurniawan@company.co.id',
                'username'           => 'hadi_kurniawan',
                'password'           => Hash::make('password'),
                'job_title'          => 'DevOps Engineer',
                'department'         => 'IT Division',
                'phone'              => '+62 812-0001-0006',
                'global_role'        => 'developer',
                'status'             => 'active',
                'email_verified_at'  => now(),
            ],
            [
                'name'               => 'Sari Dewi',
                'email'              => 'sari.dewi@company.co.id',
                'username'           => 'sari_dewi',
                'password'           => Hash::make('password'),
                'job_title'          => 'Frontend Developer',
                'department'         => 'IT Division',
                'phone'              => '+62 812-0001-0007',
                'global_role'        => 'developer',
                'status'             => 'on_leave',
                'email_verified_at'  => now(),
            ],
            [
                'name'               => 'Rizky Pratama',
                'email'              => 'rizky.pratama@company.co.id',
                'username'           => 'rizky_pratama',
                'password'           => Hash::make('password'),
                'job_title'          => 'QA Engineer',
                'department'         => 'IT Division',
                'phone'              => '+62 812-0001-0008',
                'global_role'        => 'qa',
                'status'             => 'active',
                'email_verified_at'  => now(),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
