<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserbackWebhook extends Model
{
    protected $fillable = [
        'userback_id',
        'action',
        'type',
        'project',
        'feedback_type',
        'email',
        'name',
        'page',
        'priority',
        'browser',
        'location',
        'description',
        'screenshot_url',
        'share_url',
        'raw_payload',
        'raw',
        'pushed_to_hubspot',
        'hubspot_pushed_at',
    ];

    protected $casts = [
        'raw_payload'       => 'array',
        'pushed_to_hubspot' => 'boolean',
        'hubspot_pushed_at' => 'datetime',
    ];
}
