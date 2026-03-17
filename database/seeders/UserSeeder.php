<?php

namespace Database\Seeders;

use App\Models\JobTitle;
use App\Models\MemberStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminId   = Role::where('slug', 'admin')->value('id');
        $pmId      = Role::where('slug', 'project_manager')->value('id');
        $devId     = Role::where('slug', 'developer')->value('id');
        $qaId      = Role::where('slug', 'qa')->value('id');

        $activeId   = MemberStatus::where('slug', 'active')->value('id');
        $onLeaveId  = MemberStatus::where('slug', 'on_leave')->value('id');

        $uiuxId     = JobTitle::where('name', 'UI/UX')->value('id');
        $frontendId = JobTitle::where('name', 'Frontend Developer')->value('id');
        $backendId  = JobTitle::where('name', 'Backend Developer')->value('id');
        $qaJobId    = JobTitle::where('name', 'QA')->value('id');
        $leadId     = JobTitle::where('name', 'IT Division Lead')->value('id');
        $devopsId   = JobTitle::where('name', 'DevOps Engineer')->value('id');

        $users = [
            [
                'name'              => 'Andi Rahmat',
                'email'             => 'andi.rahmat@company.co.id',
                'username'          => 'andi_rahmat',
                'password'          => Hash::make('password'),
                'department'        => 'IT Division',
                'phone'             => '+62 812-0001-0001',
                'role_id'           => $adminId,
                'status_id'         => $activeId,
                'job_id'            => $leadId,
                'email_verified_at' => now(),
            ],
            [
                'name'              => 'Budi Santoso',
                'email'             => 'budi.santoso@company.co.id',
                'username'          => 'budi_santoso',
                'password'          => Hash::make('password'),
                'department'        => 'IT Division',
                'phone'             => '+62 812-0001-0002',
                'role_id'           => $pmId,
                'status_id'         => $activeId,
                'job_id'            => $backendId,
                'email_verified_at' => now(),
            ],
            [
                'name'              => 'Dina Permata',
                'email'             => 'dina.permata@company.co.id',
                'username'          => 'dina_permata',
                'password'          => Hash::make('password'),
                'department'        => 'IT Division',
                'phone'             => '+62 812-0001-0003',
                'role_id'           => $pmId,
                'status_id'         => $activeId,
                'job_id'            => $uiuxId,
                'email_verified_at' => now(),
            ],
            [
                'name'              => 'Fajar Hidayat',
                'email'             => 'fajar.hidayat@company.co.id',
                'username'          => 'fajar_hidayat',
                'password'          => Hash::make('password'),
                'department'        => 'IT Division',
                'phone'             => '+62 812-0001-0004',
                'role_id'           => $devId,
                'status_id'         => $activeId,
                'job_id'            => $backendId,
                'email_verified_at' => now(),
            ],
            [
                'name'              => 'Rina Wulandari',
                'email'             => 'rina.wulandari@company.co.id',
                'username'          => 'rina_wulandari',
                'password'          => Hash::make('password'),
                'department'        => 'IT Division',
                'phone'             => '+62 812-0001-0005',
                'role_id'           => $devId,
                'status_id'         => $activeId,
                'job_id'            => $backendId,
                'email_verified_at' => now(),
            ],
            [
                'name'              => 'Hadi Kurniawan',
                'email'             => 'hadi.kurniawan@company.co.id',
                'username'          => 'hadi_kurniawan',
                'password'          => Hash::make('password'),
                'department'        => 'IT Division',
                'phone'             => '+62 812-0001-0006',
                'role_id'           => $devId,
                'status_id'         => $activeId,
                'job_id'            => $devopsId,
                'email_verified_at' => now(),
            ],
            [
                'name'              => 'Sari Dewi',
                'email'             => 'sari.dewi@company.co.id',
                'username'          => 'sari_dewi',
                'password'          => Hash::make('password'),
                'department'        => 'IT Division',
                'phone'             => '+62 812-0001-0007',
                'role_id'           => $devId,
                'status_id'         => $onLeaveId,
                'job_id'            => $frontendId,
                'email_verified_at' => now(),
            ],
            [
                'name'              => 'Rizky Pratama',
                'email'             => 'rizky.pratama@company.co.id',
                'username'          => 'rizky_pratama',
                'password'          => Hash::make('password'),
                'department'        => 'IT Division',
                'phone'             => '+62 812-0001-0008',
                'role_id'           => $qaId,
                'status_id'         => $activeId,
                'job_id'            => $qaJobId,
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
