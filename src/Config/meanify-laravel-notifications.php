<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default queue
    |--------------------------------------------------------------------------
    |
    | Define name for default queue to processing laravel notifications
    |
    */

    'default_queue_name' => 'meanify_queue_notification',

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | Define the model that represents the user who receives notifications.
    | This is used for resolving relationships and user data during rendering.
    |
    */

    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Default Email Layout
    |--------------------------------------------------------------------------
    |
    | Define the layout "key" to be used when the template does not explicitly
    | define one. Must exist in the emails_layouts table.
    |
    */

    'default_email_layout' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Email Channel Options
    |--------------------------------------------------------------------------
    |
    | Control how email notifications are processed. You can define the queue
    | used, the number of retries, and whether to send immediately or delay.
    |
    */

    'email' => [
        'enabled' => true,
        'queue' => 'meanify_queue_notification_emails',
        'tries' => 3,
        'backoff' => 30,
        'send_immediately' => false, // If true, bypasses the queue
    ],

    /*
    |--------------------------------------------------------------------------
    | In-App Notifications
    |--------------------------------------------------------------------------
    |
    | Control how in-app notifications are handled. You can enable/disable,
    | define the queue, and other runtime behaviors.
    |
    */

    'in_app' => [
        'enabled' => true,
        'queue' => 'meanify_queue_notification_in_app',
    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcast Configuration
    |--------------------------------------------------------------------------
    |
    | Channel prefix and structure used for in-app real-time notifications.
    | By default, it uses obfuscated + base64-encoded channels.
    |
    */

    'broadcast' => [
        'channel_prefix' => 'mfy_channel_',
        'enabled' => true,
    ],
];
