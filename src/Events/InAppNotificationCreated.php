<?php

namespace Meanify\LaravelNotifications\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Notification;
use Meanify\LaravelNotifications\Support\ChannelBuilder;

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
        // Verificar se há canais customizados no payload
        $customChannels = $this->notification->payload['__broadcast_channels'] ?? [];
        
        if (!empty($customChannels)) {
            $channels = [];
            
            foreach ($customChannels as $channel) {
                if (is_string($channel)) {
                    // Canal simples: 'user.123'
                    $channels[] = new PrivateChannel($channel);
                } elseif (is_array($channel)) {
                    if (isset($channel['model']) && isset($channel['id'])) {
                        // Usar ChannelBuilder: ['model' => User::class, 'id' => 123]
                        $channelName = ChannelBuilder::makeChannel($channel['model'], $channel['id']);
                        $channels[] = new PrivateChannel($channelName);
                    } elseif (isset($channel['channel'])) {
                        // Canal com configuração: ['channel' => 'user.123', 'event' => 'custom.event']
                        $channels[] = new PrivateChannel($channel['channel']);
                    }
                }
            }
            
            return $channels;
        }
        
        // Canal padrão baseado no user_id
        return [
            new PrivateChannel("user.{$this->notification->user_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        // Verificar se há evento customizado no payload
        $customChannels = $this->notification->payload['__broadcast_channels'] ?? [];
        
        foreach ($customChannels as $channel) {
            if (is_array($channel) && isset($channel['event'])) {
                return $channel['event'];
            }
        }
        
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
