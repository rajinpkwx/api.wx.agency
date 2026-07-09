<?php

namespace App\Service\Icounter;

use App\Models\Icounter\LumaRegistration;
use Illuminate\Support\Facades\Log;

/**
 * Pushes local registrations to HubSpot synchronously (real-time, not
 * queued) so the caller gets a full per-record result back in the same
 * HTTP response. Reuses LumaHubspotSyncService directly — the exact same
 * dedupe logic as the live webhook path: contacts are always searched-by-
 * email before create, and Marketing Event upsert is idempotent on
 * externalEventId — so pushing an event that was partially synced before
 * is always safe to re-run and never creates duplicates.
 *
 * One row's failure never aborts the batch — every row gets its own
 * try/catch so a bad record (missing email, HubSpot rejecting one field)
 * doesn't block the rest from syncing.
 */
class LumaPushService
{
    private LumaHubspotSyncService $syncService;

    public function __construct(LumaHubspotSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * @return array{
     *     total: int, synced: int, failed: int,
     *     results: array<int, array{
     *         id: int, email: ?string, luma_event_name: ?string,
     *         status: string, hubspot_contact_id: ?string, error: ?string
     *     }>
     * }
     */
    public function pushPending(?string $eventId = null, bool $force = false): array
    {
        $query = LumaRegistration::whereNotNull('email');

        if (!$force) {
            $query->where('sync_state', '!=', 'synced');
        }

        if ($eventId) {
            $query->where('luma_event_id', $eventId);
        }

        $results = [];
        $synced  = 0;
        $failed  = 0;

        foreach ($query->orderBy('id')->cursor() as $registration) {
            try {
                $this->syncService->sync($registration);
                $registration->refresh();

                $results[] = [
                    'id'                  => $registration->id,
                    'email'               => $registration->email,
                    'luma_event_name'     => $registration->luma_event_name,
                    'status'              => 'synced',
                    'hubspot_contact_id'  => $registration->hubspot_contact_id,
                    'error'               => null,
                ];
                $synced++;
            } catch (\Throwable $e) {
                $registration->update([
                    'sync_state' => 'failed',
                    'last_error' => $e->getMessage(),
                ]);

                Log::error('Icounter Luma push: sync failed for one record', [
                    'registration_id' => $registration->id,
                    'error'           => $e->getMessage(),
                ]);

                $results[] = [
                    'id'                 => $registration->id,
                    'email'              => $registration->email,
                    'luma_event_name'    => $registration->luma_event_name,
                    'status'             => 'failed',
                    'hubspot_contact_id' => null,
                    'error'              => $e->getMessage(),
                ];
                $failed++;
            }
        }

        return [
            'total'   => count($results),
            'synced'  => $synced,
            'failed'  => $failed,
            'results' => $results,
        ];
    }
}
