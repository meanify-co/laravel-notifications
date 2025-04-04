<?php

namespace Meanify\LaravelNotifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Notification;
use Meanify\LaravelNotifications\Support\NotificationRenderer;

class SendNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Notification $notification;

    /**
     * @param Notification $notification
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;

        $this->tries   = config('meanify-laravel-notifications.email.tries', 3);
        $this->backoff = config('meanify-laravel-notifications.email.backoff', 30);

        $this->onQueue(config('meanify-laravel-notifications.email.queue', 'meanify_queue_notification_emails'));
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        if (!config('meanify-laravel-notifications.email.enabled', true)) {
            return;
        }

        self::execute($this->notification);
    }

    /**
     * @param $notification
     * @return bool
     */
    public static function execute($notification): bool
    {
        try
        {
            $smtp = $notification->payload['__smtp'] ?? null;

            if ($smtp) {

                try
                {
                    $password = Crypt::decrypt($smtp['password']);
                }
                catch (\Exception $e)
                {
                    $password = $smtp['password'];
                }

                config([
                    'mail.default'                 => 'smtp',
                    'mail.mailers.smtp.host'       => $smtp['host'] ?? '',
                    'mail.mailers.smtp.port'       => $smtp['port'] ?? 587,
                    'mail.mailers.smtp.encryption' => $smtp['encryption'] ?? 'tls',
                    'mail.mailers.smtp.username'   => $smtp['username'] ?? null,
                    'mail.mailers.smtp.password'   => $password,
                    'mail.from.address'            => $smtp['from_address'] ?? config('mail.from.address'),
                    'mail.from.name'               => $smtp['from_name'] ?? config('mail.from.name'),
                ]);
            }

            $emails  = $notification->payload['__recipients'] ?? [];

            $html    = app(NotificationRenderer::class)->renderEmail($notification);

            $subject = trim(($smtp['subject_prefix'] ?? '') .' '.$notification->payload['subject'] ?? 'App notification');

            foreach ($emails as $email)
            {
                $html = str_replace('{{recipient}}', Crypt::encrypt($email), $html);

                Mail::html($html, function ($message) use ($email, $subject) {
                    $message->to($email);
                    $message->subject($subject);
                });
            }

            $notification->update(['status' => 'sent', 'sent_at' => now()]);

            return true;
        }
        catch (\Throwable $e)
        {
            $notification->update(['status' => 'failed']);

            Log::emergency('Email notification failed', [
                'notification_id' => $notification->id,
                'exception' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'message' => $e->getMessage(),
                ],
            ]);

            return false;
        }
    }

    /**
     * @param \Throwable $e
     * @return void
     */
    public function failed(\Throwable $e): void
    {
        $this->notification->update(['status' => 'failed']);

        Log::emergency('Email notification failed', [
            'notification_id' => $this->notification->id,
            'exception' => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ],
        ]);
    }
}
