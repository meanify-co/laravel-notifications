<?php

namespace Meanify\LaravelNotifications\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use Meanify\LaravelNotifications\Jobs\DispatchNotificationJob;

class NotificationDispatcher
{
    public function dispatch(
        string $templateKey,
        string $locale,
        object $user,
        array $replacements = [],
        ?int $accountId = null,
        ?int $applicationId = null,
        ?int $sessionId = null,
        array $overrideEmails = [],
        array $smtpConfigs = []
    ): void {
        $template = NotificationTemplate::where('key', $templateKey)
            ->where('active', true)
            ->with('translations', 'variables', 'layout')
            ->firstOrFail();

        foreach ($template->available_channels as $channel) {
            DB::beginTransaction();

            try {
                $translation = $template->translations
                    ->where('locale', $locale)
                    ->first();

                $payload = [
                    'subject' => $this->interpolate($translation->subject ?? '', $replacements),
                    'body' => $this->interpolate($translation->body ?? '', $replacements),
                    'short_message' => $this->interpolate($translation->short_message ?? '', $replacements),
                    'replacements' => $replacements,
                ];

                if (!empty($overrideEmails)) {
                    $payload['__override_emails'] = $overrideEmails;
                }

                if (!empty($smtpConfigs)) {
                    $payload['__smtp'] = $smtpConfigs;
                }


                $notification = Notification::create([
                    'notification_template_id' => $template->id,
                    'user_id' => $user->id ?? null,
                    'application_id' => $applicationId,
                    'session_id' => $sessionId,
                    'account_id' => $accountId,
                    'channel' => $channel,
                    'payload' => $payload,
                    'status' => 'pending',
                ]);

                DB::commit();

                DispatchNotificationJob::dispatch($notification)
                    ->onQueue(config('meanify-laravel-notifications.default_queue_name', 'meanify_queue_notification'))
                    ->delay(now()->addSeconds(1));

            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Notification dispatch failed', [
                    'template' => $templateKey,
                    'user_id' => $user->id ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function interpolate(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{{ '.$key.' }}', $value, $text);
        }

        return $text;
    }
}

