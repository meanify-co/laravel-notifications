<?php

use Meanify\LaravelNotifications\Support\NotificationBuilder;

if (! function_exists('meanify_notifications')) {
    function meanify_notifications(object $to_user, ?string $locale = null): NotificationBuilder
    {
        return NotificationBuilder::make($to_user, $locale);
    }
}
