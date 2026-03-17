<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Data migration: map 'member' → 'developer' before changing enum
        DB::statement("UPDATE users SET global_role = 'developer' WHERE global_role = 'member'");

        DB::statement("ALTER TABLE users MODIFY COLUMN global_role ENUM('admin', 'project_manager', 'developer', 'qa') NOT NULL DEFAULT 'developer'");
    }

    public function down(): void
    {
        // Reverse data migration: map non-admin roles back to 'member'
        DB::statement("UPDATE users SET global_role = 'member' WHERE global_role IN ('project_manager', 'developer', 'qa')");

        DB::statement("ALTER TABLE users MODIFY COLUMN global_role ENUM('admin', 'member') NOT NULL DEFAULT 'member'");
    }
};
