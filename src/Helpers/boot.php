<?php

use Meanify\LaravelNotifications\Support\NotificationBuilder;
use Meanify\LaravelNotifications\Support\NotificationUtils;

if (! function_exists('meanify_notifications'))
{
    /**
     * @param string|null $notification_template_key
     * @param object|null $to_user
     * @param string|null $locale
     * @return NotificationBuilder|NotificationUtils
     * @throws Exception
     */
    function meanify_notifications(?string $notification_template_key = null, ?object $to_user = null, ?string $locale = null): NotificationBuilder|NotificationUtils
    {
        if($notification_template_key === null and $to_user === null and $locale === null)
        {
            return new NotificationUtils();
        }
        else
        {
            if($notification_template_key === null or $to_user === null)
            {
                throw new \Exception("Notification template key and to_user are required");
            }

            return NotificationBuilder::make($notification_template_key, $to_user, $locale);
        }
    }
}
