<?php

namespace App\Http\Controllers\Icounter;

use App\Http\Controllers\Controller;
use App\Service\Icounter\LumaImportService;
use App\Service\Icounter\LumaPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Internal, token-protected endpoints for backfilling existing Luma
 * registrants — not called by Luma itself. Deliberately split into two
 * steps per the requirement: import into our own database first, review/
 * retry safely, then push to HubSpot as an explicit second action.
 */
class LumaAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $token = $request->header('X-Icounter-Admin-Token');
            $expected = config('icounter.admin_token');

            if (!$expected || !$token || !hash_equals($expected, $token)) {
                Log::warning('Icounter admin endpoint: unauthorized attempt', ['ip' => $request->ip()]);
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            return $next($request);
        });
    }

    /**
     * Step 1 — pull every registrant for a Luma event into our database.
     * Does not touch HubSpot. Safe to re-run any time (upserts by
     * luma_guest_id, never creates duplicate rows).
     */
    public function import(Request $request, LumaImportService $importer)
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|string|starts_with:evt-',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $importer->importEvent($request->input('event_id'));
        } catch (\Throwable $e) {
            Log::error('Icounter Luma import failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => $e->getMessage()], 502);
        }

        return response()->json(['status' => true] + $result);
    }

    /**
     * Same as import(), but discovers every event under this API key's
     * calendar and imports all of their registrants in one call. Can take
     * a while for many/large events — safe to re-run if it times out
     * partway, already-imported guests are just updated again.
     */
    public function importAll(LumaImportService $importer)
    {
        try {
            $result = $importer->importAllEvents();
        } catch (\Throwable $e) {
            Log::error('Icounter Luma import-all failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => $e->getMessage()], 502);
        }

        return response()->json(['status' => true] + $result);
    }

    /**
     * Step 2 — push local registrations to HubSpot in real time (not
     * queued) — the response includes a per-record result so the caller
     * sees exactly what synced. Optionally scoped to one event_id;
     * optionally force-resync already-synced rows. Safe to re-run — every
     * write on the HubSpot side is search-then-create-or-update, so this
     * never produces duplicate contacts or duplicate Marketing Events.
     */
    public function push(Request $request, LumaPushService $pusher)
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'nullable|string|starts_with:evt-',
            'force'    => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        // This runs synchronously across potentially hundreds of records,
        // each making several HubSpot API calls — the default PHP/web
        // server timeout is nowhere near enough for a real backfill.
        set_time_limit(0);

        $result = $pusher->pushPending(
            $request->input('event_id'),
            (bool) $request->input('force', false)
        );

        return response()->json(['status' => true] + $result);
    }
}
