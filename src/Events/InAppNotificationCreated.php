<?php

namespace Meanify\LaravelNotifications\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Notification;

class InAppNotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->notification->user_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'in-app.notification';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'short_message' => $this->notification->payload['short_message'] ?? '',
            'created_at' => $this->notification->created_at->toIso8601String(),
        ];
    }
}
