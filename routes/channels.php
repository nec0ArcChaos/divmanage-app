<?php

use App\Models\ProjectMember;
use App\Models\Task;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('task.{taskId}', function ($user, $taskId) {
    $task = Task::find($taskId);
    if (! $task) {
        return false;
    }

    return ProjectMember::where('project_id', $task->project_id)
        ->where('user_id', $user->id)
        ->exists();
});
