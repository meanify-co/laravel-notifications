<?php

namespace App\Models;

use App\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailLayout extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'emails_layouts';

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'key',
        'metadata',
        'blade_template',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'metadata' => 'array',
    ];
}