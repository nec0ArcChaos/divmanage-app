<?php

namespace Database\Seeders;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationPreferenceSeeder extends Seeder
{
    public function run(): void
    {
        User::all()->each(function (User $user) {
            NotificationPreference::create([
                'user_id'                => $user->id,
                'email_notifications'    => true,
                'task_assignment_alerts' => true,
                'deadline_reminders'     => true,
                'project_updates'        => false,
            ]);
        });
    }
}
