<?php

namespace Meanify\LaravelNotifications\Support;

use Illuminate\Support\Facades\Blade;
use App\Models\Notification;
use App\Models\EmailLayout;

class NotificationRenderer
{
    /**
     * @param Notification $notification
     * @return string
     */
    public function renderEmail(Notification $notification): string
    {
        $payload  = $notification->payload ?? [];

        $template = $notification->template;

        $layout = $template?->layout
            ?? EmailLayout::where('key', config('meanify-laravel-notifications.default_email_layout', 'default'))->first();

        $htmlLayout = $layout?->blade_template ?? '{{ $content }}';


        $body        = $payload['body'] ?? '';
        $dynamicData = $payload['dynamic_data'] ?? [];
        unset($dynamicData['dynamic_data']);

        if($layout)
        {
            $dynamicData = array_merge($dynamicData, $layout->metadata);
        }

        $renderedBody = Blade::render($body, $dynamicData);

        return Blade::render($htmlLayout, [
                'content' => $renderedBody,
                'subject' => $payload['subject'] ?? null,
            ] + $dynamicData + $payload);
    }
}
