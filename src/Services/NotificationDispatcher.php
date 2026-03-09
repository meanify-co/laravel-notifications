<?php

namespace Meanify\LaravelNotifications\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use Meanify\LaravelNotifications\Jobs\DispatchNotificationJob;
use Meanify\LaravelNotifications\Jobs\SendNotificationEmailJob;
use Meanify\LaravelNotifications\Support\NotificationRenderer;
use Meanify\LaravelNotifications\Support\TemplateInterpolator;

class NotificationDispatcher
{
    /**
     * @param string|null $notificationTemplateKey
     * @param ?object $user
     * @param string $locale
     * @param int|null $accountId
     * @param int|null $applicationId
     * @param int|null $sessionId
     * @param string $mailDriverType
     * @param array $mailDriverConfigs
     * @param array $recipients
     * @param array $dynamicData
     * @param Carbon|null $scheduledTo
     * @param bool $sendEmailImmediately If "true", notification will not dispatch (the email will send at moment)
     * @param array $attachments Array of email attachments
     * @param string|null $renderedHtml HTML já renderizado para envio imediato
     * @param string|null $renderedSubject Assunto customizado para o HTML renderizado
     * @param array $renderedPayload Dados adicionais para compor o payload quando o HTML já estiver pronto
     * @param array $broadcastChannels Canais de broadcast customizados para notificações in-app
     * @return bool
     */
    public function dispatch(
        ?string $notificationTemplateKey,
        ?object $user,
        string $locale,
        ?int $accountId = null,
        ?int $applicationId = null,
        ?int $sessionId = null,
        string $mailDriverType = 'smtp',
        array $mailDriverConfigs = [],
        array $recipients = [],
        array $dynamicData = [],
        ?Carbon $scheduledTo = null,
        bool $sendEmailImmediately = false,
        array $attachments = [],
        ?string $renderedHtml = null,
        ?string $renderedSubject = null,
        array $renderedPayload = [],
        array $broadcastChannels = [],
    ): bool {

        $dispatched = true;

        if (empty($notificationTemplateKey) && $renderedHtml === null) {
            throw new \InvalidArgumentException('É necessário informar uma notification_template_key ou um HTML renderizado.');
        }

        $useTemplate = !empty($notificationTemplateKey);

        try
        {
            $template = null;
            $channels = ['email'];

            if ($useTemplate) {
                $template = NotificationTemplate::where('key', $notificationTemplateKey)
                    ->where('active', true)
                    ->with('translations', 'variables', 'layout')
                    ->firstOrFail();

                $channels = $template->available_channels;
            } elseif (!empty($renderedPayload['channels']) && is_array($renderedPayload['channels'])) {
                $channels = $renderedPayload['channels'];
            }

            foreach ($channels as $channel)
            {
                DB::beginTransaction();

                try
                {
                    if ($useTemplate) {
                        $translation = $template->translations->where('locale', $locale)->first();

                        $payload = [
                            'dynamic_data'  => $dynamicData,
                            'short_message' => TemplateInterpolator::render($translation->short_message ?? '', $dynamicData),
                            'subject'       => TemplateInterpolator::render($translation->subject ?? '', $dynamicData),
                            'title'         => TemplateInterpolator::render($translation->title ?? '', $dynamicData),
                            'body'          => TemplateInterpolator::render($translation->body ?? '', $dynamicData),
                        ];

                        if ($renderedSubject !== null) {
                            $payload['subject'] = $renderedSubject;
                        }
                    } else {
                        $payload = array_merge(
                            $renderedPayload,
                            [
                                'dynamic_data'        => $dynamicData,
                                'body'                => $renderedHtml,
                                '__rendered_email'    => true,
                            ]
                        );

                        if ($renderedSubject !== null) {
                            $payload['subject'] = $renderedSubject;
                        }

                        $payload['subject'] = $payload['subject'] ?? 'App notification';
                        $payload['title'] = $payload['title'] ?? $payload['subject'];
                    }

                    if (!empty($recipients))
                    {
                        $payload['__recipients'] = $recipients;
                    }

                    if (isset($mailDriverType) and !empty($mailDriverConfigs))
                    {
                        $payload['__mail'] = [
                            'driver'  => $mailDriverType,
                            'configs' => $mailDriverConfigs,
                        ];
                    }

                    if (!empty($attachments))
                    {
                        $payload['__attachments'] = $attachments;
                    }

                    if (!empty($broadcastChannels) && $channel === 'in_app')
                    {
                        $payload['__broadcast_channels'] = $broadcastChannels;
                    }
                    
                    $payload['type'] = $template?->key;
                    
                    $notification = Notification::create([
                        'notification_template_id' => $template?->id,
                        'user_id'                  => $user?->id,
                        'application_id'           => $applicationId,
                        'session_id'               => $sessionId,
                        'account_id'               => $accountId,
                        'channel'                  => $channel,
                        'payload'                  => $payload,
                        'status'                   => Notification::NOTIFICATION_STATUS_PENDING,
                    ]);

                    DB::commit();

                    if(!$sendEmailImmediately)
                    {
                        DispatchNotificationJob::dispatch($notification)
                            ->onQueue(config('meanify-laravel-notifications.default_queue_name', 'meanify_queue_notification'))
                            ->delay($scheduledTo ?? now()->addSeconds(1));
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
                        'template' => $notificationTemplateKey ?? '__rendered__',
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
                'template' => $notificationTemplateKey ?? '__rendered__',
                'user' => $user ?? null,
                'error' => $e1->getMessage(),
            ]);
        }

        return $dispatched;
    }


    /**
     * Renders the email for a notification template (or raw HTML) without dispatching anything.
     *
     * @param string|null $notificationTemplateKey
     * @param string $locale
     * @param array $dynamicData
     * @param bool $interpolate When false, variables are not substituted (raw template is returned)
     * @param string|null $renderedHtml Pre-rendered HTML (alternative to template key)
     * @param string|null $renderedSubject
     * @param array $renderedPayload
     * @return array{subject: string, title: string, body: string, html: string}
     */
    public function preview(
        ?string $notificationTemplateKey,
        string $locale,
        array $dynamicData = [],
        bool $interpolate = true,
        ?string $renderedHtml = null,
        ?string $renderedSubject = null,
        array $renderedPayload = [],
    ): array {
        if (empty($notificationTemplateKey) && $renderedHtml === null) {
            throw new \InvalidArgumentException('É necessário informar uma notification_template_key ou um HTML renderizado.');
        }

        $useTemplate = !empty($notificationTemplateKey);
        $layout      = null;

        if ($useTemplate) {
            $template = NotificationTemplate::where('key', $notificationTemplateKey)
                ->where('active', true)
                ->with('translations', 'variables', 'layout')
                ->firstOrFail();

            $layout      = $template->layout;
            $translation = $template->translations->where('locale', $locale)->first();

            $payload = [
                'dynamic_data'  => $dynamicData,
                'short_message' => $interpolate ? TemplateInterpolator::render($translation->short_message ?? '', $dynamicData) : ($translation->short_message ?? ''),
                'subject'       => $interpolate ? TemplateInterpolator::render($translation->subject ?? '', $dynamicData)       : ($translation->subject ?? ''),
                'title'         => $interpolate ? TemplateInterpolator::render($translation->title ?? '', $dynamicData)         : ($translation->title ?? ''),
                'body'          => $interpolate ? TemplateInterpolator::render($translation->body ?? '', $dynamicData)          : ($translation->body ?? ''),
            ];

            if ($renderedSubject !== null) {
                $payload['subject'] = $renderedSubject;
            }
        } else {
            $payload = array_merge($renderedPayload, [
                'dynamic_data'     => $dynamicData,
                'body'             => $renderedHtml,
                '__rendered_email' => true,
            ]);

            if ($renderedSubject !== null) {
                $payload['subject'] = $renderedSubject;
            }

            $payload['subject'] = $payload['subject'] ?? 'App notification';
            $payload['title']   = $payload['title']   ?? $payload['subject'];
        }

        $html = app(NotificationRenderer::class)->renderEmailFromPayload($payload, $layout, $interpolate);

        return [
            'subject' => $payload['subject'] ?? '',
            'title'   => $payload['title']   ?? '',
            'body'    => $payload['body']    ?? '',
            'html'    => $html,
        ];
    }


}

