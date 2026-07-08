<?php

namespace App\Models\Icounter;

use Illuminate\Database\Eloquent\Model;

class LumaRegistration extends Model
{
    protected $table = 'luma_registrations';

    protected $fillable = [
        'luma_event_id',
        'luma_event_name',
        'luma_guest_id',
        'webhook_event_type',
        'signature_valid',
        'source_ip',
        'email',
        'first_name',
        'last_name',
        'company',
        'job_title',
        'phone',
        'status',
        'registration_date',
        'hubspot_contact_id',
        'hubspot_marketing_event_synced_at',
        'last_status_synced',
        'sync_state',
        'attempts',
        'last_error',
        'raw_payload',
        'raw',
    ];

    protected $casts = [
        'registration_date'                 => 'datetime',
        'hubspot_marketing_event_synced_at' => 'datetime',
        'attempts'                          => 'integer',
        'signature_valid'                   => 'boolean',
    ];
}
