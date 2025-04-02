<?php

namespace App\Models;

use App\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplateTranslation extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'notifications_templates_translations';

    /**
     * @var string[]
     */
    protected $fillable = [
        'notification_template_id',
        'locale',
        'subject',
        'body',
        'short_message',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }
}