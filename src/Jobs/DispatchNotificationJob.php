<?php

namespace Meanify\LaravelNotifications\Jobs;
namespace Meanify\LaravelNotifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Notification;

class DispatchNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
        $this->onQueue(config('meanify-laravel-notifications.default.queue', 'meanify_queue_notification'));
    }

    public function handle(): void
    {
        try {
            match ($this->notification->channel) {
                'email' => SendNotificationEmailJob::dispatch($this->notification),
                'in_app' => BroadcastInAppNotificationJob::dispatch($this->notification),
                default => Log::warning("Unknown notification channel: {$this->notification->channel}")
            };
        } catch (\Throwable $e) {
            $this->notification->update(['status' => 'failed']);
            Log::error('DispatchNotificationJob failed', [
                'notification_id' => $this->notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
