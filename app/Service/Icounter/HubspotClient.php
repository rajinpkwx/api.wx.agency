<?php

namespace App\Service\Icounter;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class HubspotClient
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.hubapi.com',
            'timeout'  => 15,
            'headers'  => [
                'Authorization' => 'Bearer ' . config('icounter.hubspot.token'),
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    /**
     * Find a contact by email. Returns the HubSpot contact id or null.
     */
    public function findContactByEmail(string $email): ?string
    {
        $response = $this->client->post('/crm/v3/objects/contacts/search', [
            'json' => [
                'filterGroups' => [[
                    'filters' => [[
                        'propertyName' => 'email',
                        'operator'     => 'EQ',
                        'value'        => $email,
                    ]],
                ]],
                'properties' => ['email'],
                'limit'      => 1,
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        return $result['results'][0]['id'] ?? null;
    }

    public function createContact(array $properties): string
    {
        $response = $this->client->post('/crm/v3/objects/contacts', [
            'json' => ['properties' => array_filter($properties, fn ($v) => $v !== null)],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        return $result['id'];
    }

    public function updateContact(string $contactId, array $properties): void
    {
        $this->client->patch("/crm/v3/objects/contacts/{$contactId}", [
            'json' => ['properties' => array_filter($properties, fn ($v) => $v !== null)],
        ]);
    }

    /**
     * Create the contact if none exists for this email, otherwise update it.
     * This is the single duplicate-prevention choke point for HubSpot writes.
     */
    public function upsertContactByEmail(string $email, array $properties): string
    {
        $existingId = $this->findContactByEmail($email);

        if ($existingId) {
            $this->updateContact($existingId, $properties);
            return $existingId;
        }

        $properties['email'] = $email;
        return $this->createContact($properties);
    }

    /**
     * Best-effort Marketing Event upsert. Requires the "Marketing Events"
     * feature on the HubSpot subscription (Marketing Hub Pro+) and the
     * crm.objects.marketing_events.write scope on the private app.
     * Callers must treat failures here as non-fatal — contact sync must
     * still succeed even if this is unavailable.
     */
    public function upsertMarketingEvent(string $externalEventId, string $eventName, ?string $startDateTime = null): void
    {
        $externalAccountId = config('icounter.hubspot.marketing_events_account_id');

        $body = array_filter([
            'eventName'       => $eventName,
            'eventOrganizer'  => 'iCounter',
            'startDateTime'   => $startDateTime,
        ]);

        try {
            $this->client->post('/marketing/v3/marketing-events/events', [
                'json' => $body + [
                    'externalEventId'   => $externalEventId,
                    'externalAccountId' => $externalAccountId,
                ],
            ]);
        } catch (ClientException $e) {
            $error = json_decode($e->getResponse()->getBody()->getContents(), true);

            // Event already exists for this externalEventId — update it
            // instead. Any other error should surface to the caller.
            if (($error['errorType'] ?? null) !== 'UNIQUE_VALUE_CONFLICT') {
                throw $e;
            }

            $this->client->patch("/marketing/v3/marketing-events/events/{$externalEventId}", [
                'query' => [
                    'idProperty'        => 'externalEventId',
                    'externalAccountId' => $externalAccountId,
                ],
                'json' => $body,
            ]);
        }
    }

    /**
     * Record a contact's participation state for a marketing event by email.
     * $subscriberState is one of: register, attend, cancel.
     */
    public function upsertMarketingEventAttendance(string $externalEventId, string $email, string $subscriberState): void
    {
        $this->client->post("/marketing/v3/marketing-events/attendance/{$externalEventId}/{$subscriberState}/email-create", [
            'query' => ['externalAccountId' => config('icounter.hubspot.marketing_events_account_id')],
            'json'  => [
                'inputs' => [
                    [
                        'email'                => $email,
                        'interactionDateTime'  => now()->getTimestampMs(),
                    ],
                ],
            ],
        ]);
    }
}
