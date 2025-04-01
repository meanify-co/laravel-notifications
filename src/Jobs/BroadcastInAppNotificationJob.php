<?php

namespace Meanify\LaravelNotifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Notification;
use Meanify\LaravelNotifications\Events\InAppNotificationCreated;

class BroadcastInAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;

        $this->queue = config('meanify-laravel-notifications.in_app.queue', 'meanify_queue_notification_in_app');
    }

    public function handle(): void
    {
        if (! config('meanify-laravel-notifications.in_app.enabled', true)) {
            return;
        }

        broadcast(new InAppNotificationCreated($this->notification));

        $this->notification->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function failed(\Throwable $e): void
    {
        $this->notification->update(['status' => 'failed']);
        Log::warning('In-app notification failed', [
            'notification_id' => $this->notification->id,
            'error' => $e->getMessage(),
        ]);
    }
}
