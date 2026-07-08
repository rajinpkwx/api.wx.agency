<?php

return [

    /*
    |--------------------------------------------------------------------------
    | iCounter — Luma → HubSpot Integration
    |--------------------------------------------------------------------------
    | Fully isolated config for this client's integration. Do not reuse these
    | keys, or keys from other integrations, across features.
    */

    'luma' => [
        'api_key'        => env('ICOUNTER_LUMA_API_KEY'),
        'webhook_secret' => env('ICOUNTER_LUMA_WEBHOOK_SECRET'),
        'signature_header' => env('ICOUNTER_LUMA_SIGNATURE_HEADER', 'X-Luma-Signature'),
    ],

    'hubspot' => [
        'token' => env('ICOUNTER_HUBSPOT_TOKEN'),
    ],

];
