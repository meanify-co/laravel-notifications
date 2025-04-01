<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'notifications_templates';

    /**
     * @var string[]
     */
    protected $fillable = [
        'email_layout_id',
        'key',
        'available_channels',
        'force_channels',
        'active',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'available_channels' => 'array',
        'force_channels' => 'array',
        'active' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function layout()
    {
        return $this->belongsTo(EmailLayout::class, 'email_layout_id');
    }

    public function translations()
    {
        return $this->hasMany(NotificationTemplateTranslation::class);
    }

    public function variables()
    {
        return $this->hasMany(NotificationTemplateVariable::class);
    }
}