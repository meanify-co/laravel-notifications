<?php

use Meanify\LaravelNotifications\Support\NotificationBuilder;

if (! function_exists('meanify_notifications')) {
    function meanify_notifications(string $templateKey): NotificationBuilder
    {
        return NotificationBuilder::make($templateKey);
    }
}
