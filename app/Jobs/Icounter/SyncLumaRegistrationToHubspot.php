<?php

namespace App\Jobs\Icounter;

use App\Models\Icounter\LumaRegistration;
use App\Service\Icounter\LumaHubspotSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncLumaRegistrationToHubspot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 600];

    private int $registrationId;

    public function __construct(int $registrationId)
    {
        $this->registrationId = $registrationId;
    }

    public function handle(LumaHubspotSyncService $syncService): void
    {
        $registration = LumaRegistration::find($this->registrationId);

        if (!$registration) {
            return;
        }

        $registration->increment('attempts');

        $syncService->sync($registration);
    }

    public function failed(\Throwable $exception): void
    {
        $registration = LumaRegistration::find($this->registrationId);

        if (!$registration) {
            return;
        }

        $registration->update([
            'sync_state' => 'failed',
            'last_error' => $exception->getMessage(),
        ]);

        Log::error('Icounter: Luma -> HubSpot sync failed after all retries', [
            'registration_id' => $this->registrationId,
            'error'            => $exception->getMessage(),
        ]);
    }
}
