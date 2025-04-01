<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplateVariable extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'notifications_templates_variables';

    /**
     * @var string[]
     */
    protected $fillable = [
        'notification_template_id',
        'key',
        'description',
        'example',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class);
    }
}