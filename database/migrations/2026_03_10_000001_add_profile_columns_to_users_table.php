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
            $table->string('job_title')->nullable()->after('avatar');
            $table->string('department')->nullable()->after('job_title');
            $table->string('phone')->nullable()->after('department');
            $table->enum('global_role', ['admin', 'member'])->default('member')->after('phone');
            $table->enum('status', ['active', 'on_leave', 'inactive'])->default('active')->after('global_role');
            $table->boolean('two_factor_enabled')->default(false)->after('status');
            $table->timestamp('last_login_at')->nullable()->after('two_factor_enabled');

            $table->index('status');
            $table->index('global_role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['global_role']);
            $table->dropColumn([
                'username', 'avatar', 'job_title', 'department', 'phone',
                'global_role', 'status', 'two_factor_enabled', 'last_login_at',
            ]);
        });
    }
};
