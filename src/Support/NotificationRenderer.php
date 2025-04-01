<?php

namespace Meanify\LaravelNotifications\Support;

use Illuminate\Support\Facades\Blade;
use App\Models\Notification;
use App\Models\EmailLayout;

class NotificationRenderer
{
    public function renderEmail(Notification $notification): string
    {
        $payload = $notification->payload ?? [];
        $template = $notification->template;

        $layout = $template?->layout
            ?? EmailLayout::where('key', config('meanify-laravel-notifications.default_email_layout', 'default_dark'))->first();

        $htmlLayout = $layout?->blade_template ?? '{{ $content }}';


        $body = $payload['body'] ?? '';
        $replacements = $payload['replacements'] ?? [];

        $renderedBody = Blade::render($body, $replacements);

        return Blade::render($htmlLayout, [
                'content' => $renderedBody,
                'subject' => $payload['subject'] ?? null,
            ] + $replacements);
    }
}
