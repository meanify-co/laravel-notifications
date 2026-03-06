<?php

namespace App\Models;

use Illuminate\Support\Facades\Crypt;

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
        // payload is handled by accessor/mutator (supports legacy unencrypted records)
        'failed_log'   => 'object',
        'sent_at'      => 'datetime',
        'scheduled_to' => 'datetime',
        'failed_at'    => 'datetime',
    ];

    /**
     * @param $value
     * @return array
     */
    public function getPayloadAttribute($value): array
    {
        if ($value === null) {
            return [];
        }

        try {
            return json_decode(Crypt::decrypt($value), true) ?? [];
        } catch (\Exception) {
            // Fallback for legacy unencrypted records
            return json_decode($value, true) ?? [];
        }
    }

    /**
     * @param array $value
     * @return void
     */
    public function setPayloadAttribute(array $value): void
    {
        $this->attributes['payload'] = Crypt::encrypt(json_encode($value));
    }

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