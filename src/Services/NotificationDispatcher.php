<?php

namespace Meanify\LaravelNotifications\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use Meanify\LaravelNotifications\Jobs\DispatchNotificationJob;
use Meanify\LaravelNotifications\Jobs\SendNotificationEmailJob;

class NotificationDispatcher
{
    /**
     * @param string $notificationTemplateKey
     * @param object $user
     * @param string $locale
     * @param int|null $accountId
     * @param int|null $applicationId
     * @param int|null $sessionId
     * @param array $smtpConfigs
     * @param array $recipients
     * @param array $dynamicData
     * @param bool $sendEmailImmediately If "true", notification will not dispatch (the email will send at moment)
     * @return bool
     */
    public function dispatch(
        string $notificationTemplateKey,
        object $user,
        string $locale,
        ?int $accountId = null,
        ?int $applicationId = null,
        ?int $sessionId = null,
        array $smtpConfigs = [],
        array $recipients = [],
        array $dynamicData = [],
        bool $sendEmailImmediately = false,
    ): bool {

        $dispatched = true;

        try
        {
            $template = NotificationTemplate::where('key', $notificationTemplateKey)
                ->where('active', true)
                ->with('translations', 'variables', 'layout')
                ->firstOrFail();

            foreach ($template->available_channels as $channel)
            {
                DB::beginTransaction();

                try
                {
                    $translation = $template->translations->where('locale', $locale)->first();

                    $payload = [
                        'subject'       => $this->interpolate($translation->subject ?? '', $dynamicData),
                        'title'         => $this->interpolate($translation->title ?? '', $dynamicData),
                        'body'          => $this->interpolate($translation->body ?? '', $dynamicData),
                        'short_message' => $this->interpolate($translation->short_message ?? '', $dynamicData),
                        'dynamic_data'  => $dynamicData,
                    ];

                    if (!empty($recipients))
                    {
                        $payload['__recipients'] = $recipients;
                    }

                    if (!empty($smtpConfigs))
                    {
                        $payload['__smtp'] = $smtpConfigs;
                    }

                    $notification = Notification::create([
                        'notification_template_id' => $template->id,
                        'user_id'                  => $user->id ?? null,
                        'application_id'           => $applicationId,
                        'session_id'               => $sessionId,
                        'account_id'               => $accountId,
                        'channel'                  => $channel,
                        'payload'                  => $payload,
                        'status'                   => 'pending',
                    ]);

                    DB::commit();

                    if(!$sendEmailImmediately)
                    {
                        DispatchNotificationJob::dispatch($notification)
                            ->onQueue(config('meanify-laravel-notifications.default_queue_name', 'meanify_queue_notification'))
                            ->delay(now()->addSeconds(1));
                    }
                    else
                    {
                        $dispatched = SendNotificationEmailJob::execute($notification);
                    }

                }
                catch (\Throwable $e2)
                {
                    DB::rollBack();

                    Log::error('Notification dispatch failed', [
                        'template' => $notificationTemplateKey,
                        'user_id' => $user ?? null,
                        'error' => $e2->getMessage(),
                    ]);
                }
            }
        }
        catch(\Exception $e1)
        {
            $dispatched = false;

            Log::error('Notification dispatch failed', [
                'template' => $notificationTemplateKey,
                'user' => $user ?? null,
                'error' => $e1->getMessage(),
            ]);
        }

        return $dispatched;
    }

    /**
     * @param string $text
     * @param array $data
     * @return string
     */
    protected function interpolate(string $text, array $data): string
    {
        foreach ($data as $key => $value)
        {
            $text = str_replace('{!! '.$key.' !!}', $value, $text);
            $text = str_replace('{{ '.$key.' }}', $value, $text);
        }

        return $text;
    }
}

