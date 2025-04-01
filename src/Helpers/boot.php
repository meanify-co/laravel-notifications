<?php

use Meanify\LaravelNotifications\Support\NotificationBuilder;

if (! function_exists('meanify_notifications')) {
    function meanify_notifications(): NotificationBuilder
    {
        return NotificationBuilder::make();
    }
}
