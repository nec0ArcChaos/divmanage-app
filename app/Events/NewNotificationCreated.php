<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewNotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $recipientId,
        public readonly array $notification
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->recipientId}")];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }
}
