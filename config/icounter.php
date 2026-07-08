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
        // Luma has no header/HMAC signing for webhooks, so the secret is
        // embedded in the webhook URL path instead (see routes/web.php).
        'webhook_secret' => env('ICOUNTER_LUMA_WEBHOOK_SECRET'),
    ],

    'hubspot' => [
        'token' => env('ICOUNTER_HUBSPOT_TOKEN'),
        // Arbitrary identifier for this integration's events namespace in
        // HubSpot's Marketing Events object — not a real HubSpot account id.
        'marketing_events_account_id' => env('ICOUNTER_HUBSPOT_MARKETING_EVENTS_ACCOUNT_ID', 'icounter-luma'),
    ],

];
