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
        // Used to call Luma's API (event lookup, guest list backfill).
        'api_key'        => env('ICOUNTER_LUMA_API_KEY'),
        // Verifies the webhook-signature header on inbound Luma webhooks.
        'webhook_secret' => env('ICOUNTER_LUMA_WEBHOOK_SECRET'),
    ],

    'hubspot' => [
        'token' => env('ICOUNTER_HUBSPOT_TOKEN'),
        // Arbitrary identifier for this integration's events namespace in
        // HubSpot's Marketing Events object — not a real HubSpot account id.
        'marketing_events_account_id' => env('ICOUNTER_HUBSPOT_MARKETING_EVENTS_ACCOUNT_ID', 'icounter-luma'),
    ],

    // Shared secret required in the X-Icounter-Admin-Token header for the
    // manual import/push endpoints (LumaAdminController). Not used by the
    // live Luma webhook, which has its own signature verification.
    'admin_token' => env('ICOUNTER_ADMIN_TOKEN'),

];
