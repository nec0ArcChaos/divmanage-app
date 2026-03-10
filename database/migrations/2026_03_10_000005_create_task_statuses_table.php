<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');           // "Backlog", "To Do", "In Progress", etc.
            $table->string('slug');           // "backlog", "todo", "in_progress"
            $table->string('color', 7);       // hex for badge/dot color
            $table->unsignedSmallInteger('position')->default(0); // ordering in workflow
            $table->boolean('is_default')->default(false);        // default status for new tasks
            $table->boolean('is_done')->default(false);           // marks task as completed
            $table->timestamps();

            $table->unique(['workspace_id', 'slug']);
            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_statuses');
    }
};
