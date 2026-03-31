<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCommentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly array $comment,
        public readonly int $taskId
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("task.{$this->taskId}")];
    }

    public function broadcastAs(): string
    {
        return 'comment.created';
    }
}
