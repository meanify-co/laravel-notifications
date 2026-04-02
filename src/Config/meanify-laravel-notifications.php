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

    'default_queue_name' => 'meanify.queue.notifications',

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
        'queue' => 'meanify.queue.notifications.emails',
        'tries' => 3,
        'backoff' => 30,
        'verify_ssl' => false,
        'send_immediately' => false, // If true, bypasses the queue

        /*
        |----------------------------------------------------------------------
        | Recipient Filter
        |----------------------------------------------------------------------
        |
        | Filters recipient emails before sending. When a recipient matches
        | a blocked domain pattern, or the email appears to be encrypted or
        | base64-encoded, the actual send is skipped.
        |
        | on_block_status:
        |   "simulated" – saves the notification as "sent" (useful for testing)
        |   "skipped"   – saves the notification as "skipped"
        |
        */

        'recipient_filter' => [
            'enabled'          => false,
            'blocked_domains'  => [],       // e.g. ['example.com', 'mailinator.com', '*.test']
            'block_encrypted'  => true,     // Block emails that appear to be encrypted (eyJ..., non-ASCII, etc.)
            'block_base64'     => true,     // Block emails that appear to be base64-encoded
            'on_block_status'  => 'simulated', // 'simulated' or 'skipped'
        ],
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
        'queue' => 'meanify.queue.notifications.in_app',
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
