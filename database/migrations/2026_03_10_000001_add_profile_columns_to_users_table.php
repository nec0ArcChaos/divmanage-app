<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('name');
            $table->string('avatar')->nullable()->after('username');
            $table->string('department')->nullable()->after('avatar');
            $table->string('phone')->nullable()->after('department');
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete()->after('phone');
            $table->foreignId('status_id')->nullable()->constrained('member_statuses')->nullOnDelete()->after('role_id');
            $table->foreignId('job_id')->nullable()->constrained('job_titles')->nullOnDelete()->after('status_id');
            $table->boolean('two_factor_enabled')->default(false)->after('job_id');
            $table->timestamp('last_login_at')->nullable()->after('two_factor_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['global_role']);
            $table->dropForeign(['role_id']);
            $table->dropForeign(['status_id']);
            $table->dropForeign(['job_id']);
            $table->dropColumn([
                'username', 'avatar', 'department', 'phone',
                'role_id', 'status_id', 'job_id', 'two_factor_enabled', 'last_login_at',
            ]);
        });
    }
};
