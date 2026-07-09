<?php

namespace App\Service\Icounter;

use GuzzleHttp\Client;

class LumaApiClient
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://public-api.luma.com',
            'timeout'  => 15,
            'headers'  => [
                'x-luma-api-key' => config('icounter.luma.api_key'),
                'Accept'         => 'application/json',
            ],
        ]);
    }

    public function getEvent(string $eventId): array
    {
        $response = $this->client->get('/v1/events/get', [
            'query' => ['event_id' => $eventId],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * One page of registered guests for an event.
     * Returns ['entries' => [...], 'has_more' => bool, 'next_cursor' => ?string].
     */
    public function listGuests(string $eventId, ?string $cursor = null, int $limit = 50): array
    {
        $response = $this->client->get('/v1/events/guests/list', [
            'query' => array_filter([
                'event_id'          => $eventId,
                'pagination_cursor' => $cursor,
                'pagination_limit'  => $limit,
            ]),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Fetch every guest for an event, walking pagination automatically.
     * @return array<int, array>
     */
    public function listAllGuests(string $eventId): array
    {
        $all    = [];
        $cursor = null;

        do {
            $page = $this->listGuests($eventId, $cursor);
            $all  = array_merge($all, $page['entries'] ?? []);
            $cursor = $page['next_cursor'] ?? null;
        } while (!empty($page['has_more']) && $cursor);

        return $all;
    }

    /**
     * Every event under this API key's calendar, walking pagination
     * automatically. Includes both upcoming and past events by default
     * (Luma's list endpoint doesn't time-limit unless before/after is set).
     * @return array<int, array>
     */
    public function listAllEvents(): array
    {
        $all    = [];
        $cursor = null;

        do {
            $response = $this->client->get('/v1/calendars/events/list', [
                'query' => array_filter([
                    'pagination_cursor' => $cursor,
                    'pagination_limit'  => 50,
                ]),
            ]);

            $page = json_decode($response->getBody()->getContents(), true);
            $all  = array_merge($all, $page['entries'] ?? []);
            $cursor = $page['next_cursor'] ?? null;
        } while (!empty($page['has_more']) && $cursor);

        return $all;
    }
}
