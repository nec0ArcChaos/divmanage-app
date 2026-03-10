<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class WorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', 'andi.rahmat@company.co.id')->first();

        $workspace = Workspace::create([
            'name'        => 'IT Division',
            'slug'        => 'it-division',
            'timezone'    => 'Asia/Jakarta',
            'date_format' => 'DD/MM/YYYY',
            'owner_id'    => $owner->id,
        ]);

        // Assign semua user ke workspace ini
        User::query()->update(['workspace_id' => $workspace->id]);
    }
}
