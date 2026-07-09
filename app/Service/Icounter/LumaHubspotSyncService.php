<?php

namespace App\Service\Icounter;

use App\Models\Icounter\LumaRegistration;
use Illuminate\Support\Facades\Log;

class LumaHubspotSyncService
{
    private HubspotClient $hubspot;

    public function __construct(HubspotClient $hubspot)
    {
        $this->hubspot = $hubspot;
    }

    public function sync(LumaRegistration $registration): void
    {
        if (!$registration->email) {
            $registration->update([
                'sync_state' => 'failed',
                'last_error' => 'No email present on registration — cannot sync to HubSpot.',
            ]);
            return;
        }

        $contactId = $this->hubspot->upsertContactByEmail($registration->email, $this->contactProperties($registration));

        // Marketing Event tracking is best-effort — a client subscription
        // without the Marketing Events feature must not fail the sync.
        if ($registration->luma_event_id) {
            try {
                $this->hubspot->upsertMarketingEvent(
                    $registration->luma_event_id,
                    $registration->luma_event_name ?? $registration->luma_event_id,
                    optional($registration->registration_date)->toIso8601String()
                );

                // The attendance endpoint is append-only, not idempotent
                // like the contact upsert — calling it again with the same
                // state creates a second "registration" entry for the same
                // person in HubSpot's UI. Only record it when the status
                // has actually changed since the last successful sync.
                if ($registration->last_status_synced !== $registration->status) {
                    $this->hubspot->upsertMarketingEventAttendance(
                        $registration->luma_event_id,
                        $registration->email,
                        $this->marketingEventState($registration->status)
                    );
                }

                $registration->hubspot_marketing_event_synced_at = now();
            } catch (\Throwable $e) {
                Log::warning('Icounter: Marketing Event sync failed (non-fatal, contact sync still succeeded)', [
                    'registration_id' => $registration->id,
                    'error'           => $e->getMessage(),
                ]);
            }
        }

        $registration->hubspot_contact_id = $contactId;
        $registration->last_status_synced = $registration->status;
        $registration->sync_state         = 'synced';
        $registration->last_error         = null;
        $registration->save();
    }

    private function contactProperties(LumaRegistration $registration): array
    {
        return [
            'firstname'                  => $registration->first_name,
            'lastname'                   => $registration->last_name,
            'company'                    => $registration->company,
            'jobtitle'                   => $registration->job_title,
            'phone'                      => $registration->phone,
            'event_name'                 => $registration->luma_event_name,
            'event_id'                   => $registration->luma_event_id,
            // HubSpot's "Date picker" property type requires midnight UTC —
            // it rejects a value with a time-of-day component.
            'event_registration_date'    => $registration->registration_date
                ? $registration->registration_date->copy()->startOfDay()->format('Y-m-d\TH:i:s.v\Z')
                : null,
            'event_attendance_status'    => $this->attendanceLabel($registration->status),
            'event_cancellation_status'  => $registration->status === 'cancelled' ? 'Cancelled' : 'Not Cancelled',
        ];
    }

    private function attendanceLabel(?string $status): ?string
    {
        switch ($status) {
            case 'registered':
                return 'Registered';
            case 'attended':
                return 'Attended';
            case 'no_show':
                return 'No-show';
            case 'cancelled':
                return 'Registered'; // cancellation tracked separately
            default:
                return null;
        }
    }

    private function marketingEventState(?string $status): string
    {
        switch ($status) {
            case 'attended':
                return 'attend';
            case 'cancelled':
                return 'cancel';
            default:
                return 'register';
        }
    }
}
