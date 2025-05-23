<?php

namespace App\Models;

class Notification extends \Illuminate\Database\Eloquent\Model
{
    const NOTIFICATION_STATUS_PENDING = 'pending';
    const NOTIFICATION_STATUS_PROCESSING = 'processing';
    const NOTIFICATION_STATUS_FAILED = 'failed';
    const NOTIFICATION_STATUS_SENT = 'sent';

    /**
     * @var string
     */
    protected $table = 'notifications';

    /**
     * @var string[]
     */
    protected $fillable = [
        'notification_template_id',
        'application_id',
        'account_id',
        'session_id',
        'user_id',
        'channel',
        'payload',
        'status',
        'scheduled_to',
        'sent_at',
        'failed_at',
        'failed_log',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'payload'      => 'array',
        'failed_log'   => 'object',
        'sent_at'      => 'datetime',
        'scheduled_to' => 'datetime',
        'failed_at'    => 'datetime',
    ];

    /**
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return mixed
     */
    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }
}