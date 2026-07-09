<?php

namespace App\Service\Icounter;

use App\Models\Icounter\LumaRegistration;

/**
 * Backfills existing Luma registrants into our local database, using the
 * exact same field mapping as the live webhook (via LumaGuestMapper) so
 * imported and webhook-synced rows are indistinguishable. This step only
 * writes to our own database — it never talks to HubSpot. Pushing to
 * HubSpot is a separate, explicit step (LumaPushService) so a large import
 * can be reviewed/retried without hammering HubSpot mid-import.
 */
class LumaImportService
{
    private LumaApiClient $luma;

    public function __construct(LumaApiClient $luma)
    {
        $this->luma = $luma;
    }

    /**
     * @return array{event_id: string, event_name: ?string, fetched: int, created: int, updated: int}
     */
    public function importEvent(string $eventId): array
    {
        $event = $this->luma->getEvent($eventId);

        // Normalize to the shape LumaGuestMapper expects (api_id + name).
        $eventForMapper = [
            'api_id' => $event['id'] ?? $eventId,
            'name'   => $event['name'] ?? null,
        ];

        return $this->importGuestsForEvent($eventForMapper);
    }

    /**
     * Discover every event under this API key's calendar and import all of
     * their registrants. Safe to re-run — same per-guest idempotency as a
     * single-event import.
     * @return array{events: int, fetched: int, created: int, updated: int, per_event: array}
     */
    public function importAllEvents(): array
    {
        $events = $this->luma->listAllEvents();

        $totals = ['events' => 0, 'fetched' => 0, 'created' => 0, 'updated' => 0, 'per_event' => []];

        foreach ($events as $event) {
            // The calendar list endpoint already returns full event objects
            // (id, name, ...) — no need for a separate getEvent() call.
            $eventForMapper = [
                'api_id' => $event['id'] ?? null,
                'name'   => $event['name'] ?? null,
            ];

            if (!$eventForMapper['api_id']) {
                continue;
            }

            $result = $this->importGuestsForEvent($eventForMapper);

            $totals['events']++;
            $totals['fetched'] += $result['fetched'];
            $totals['created'] += $result['created'];
            $totals['updated'] += $result['updated'];
            $totals['per_event'][] = $result;
        }

        return $totals;
    }

    /**
     * @return array{event_id: string, event_name: ?string, fetched: int, created: int, updated: int}
     */
    private function importGuestsForEvent(array $eventForMapper): array
    {
        $guests = $this->luma->listAllGuests($eventForMapper['api_id']);

        $created = 0;
        $updated = 0;

        foreach ($guests as $guest) {
            $guestId = LumaGuestMapper::guestId($guest);

            if (!$guestId) {
                continue;
            }

            $existing = LumaRegistration::where('luma_guest_id', $guestId)->first();

            $attributes = LumaGuestMapper::toAttributes($guest, $eventForMapper) + [
                'raw_payload' => json_encode($guest),
                'raw'         => json_encode($guest),
            ];

            // Don't clobber webhook-sourced metadata on an update — import
            // only fills these in for genuinely new rows.
            if (!$existing) {
                $attributes['webhook_event_type'] = 'import';
                $attributes['signature_valid']    = null;
                $attributes['source_ip']          = null;
            }

            LumaRegistration::updateOrCreate(
                ['luma_guest_id' => $guestId],
                $attributes
            );

            $existing ? $updated++ : $created++;
        }

        return [
            'event_id'   => $eventForMapper['api_id'],
            'event_name' => $eventForMapper['name'],
            'fetched'    => count($guests),
            'created'    => $created,
            'updated'    => $updated,
        ];
    }
}
