<?php

namespace App\Models;

class Notification extends \Illuminate\Database\Eloquent\Model
{
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
        'sent_at',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
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