<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly TaskComment $comment,
        public readonly Task $task,
        public readonly User $commenter,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $preview = strip_tags($this->comment->body);
        $preview = mb_strlen($preview) > 120 ? mb_substr($preview, 0, 120) . '…' : $preview;

        return [
            'task_id'         => $this->task->id,
            'task_title'      => $this->task->title,
            'project_id'      => $this->task->project_id,
            'project_name'    => $this->task->project?->name ?? '',
            'commenter_id'    => $this->commenter->id,
            'commenter_name'  => $this->commenter->name,
            'comment_preview' => $preview,
        ];
    }
}
