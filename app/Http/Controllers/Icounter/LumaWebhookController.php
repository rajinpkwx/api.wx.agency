<?php

namespace App\Http\Controllers\Icounter;

use App\Http\Controllers\Controller;
use App\Models\Icounter\LumaRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LumaWebhookController extends Controller
{
    /**
     * Receive a Luma webhook, log it raw + parsed, and store it for
     * downstream HubSpot sync processing. This endpoint always returns
     * fast — every request is persisted first, regardless of whether we
     * can fully parse it, so nothing from Luma is ever silently lost.
     */
    public function receive(Request $request)
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true) ?? [];

        $signatureValid = $this->verifySignature($request, $rawBody);

        if ($signatureValid === false) {
            Log::warning('Icounter Luma webhook: signature verification failed', [
                'ip' => $request->ip(),
            ]);

            // Still log it for audit purposes, but reject processing.
            $this->storeRaw($request, $rawBody, $payload, $signatureValid);

            return response()->json(['status' => false, 'message' => 'Invalid signature'], 401);
        }

        $record = $this->storeRaw($request, $rawBody, $payload, $signatureValid);

        return response()->json(['status' => true, 'id' => $record->id]);
    }

    /**
     * Verify Luma's webhook signature.
     * Returns true/false when a secret is configured, null when signature
     * checking is not configured (so it doesn't hard-block early testing).
     */
    private function verifySignature(Request $request, string $rawBody): ?bool
    {
        $secret = config('icounter.luma.webhook_secret');

        if (!$secret) {
            return null;
        }

        $headerName = config('icounter.luma.signature_header', 'X-Luma-Signature');
        $signature  = $request->header($headerName);

        if (!$signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Persist the webhook exactly as received (raw + basic parsed details),
     * using luma_guest_id as the idempotency key so retried deliveries from
     * Luma don't create duplicate rows.
     */
    private function storeRaw(Request $request, string $rawBody, array $payload, ?bool $signatureValid): LumaRegistration
    {
        $data = $payload['data'] ?? $payload;

        $guestId = $data['api_id']
            ?? $data['guest_id']
            ?? $data['person_id']
            ?? null;

        $attributes = [
            'luma_event_id'       => $data['event_api_id'] ?? $data['event']['api_id'] ?? null,
            'luma_event_name'     => $data['event']['name'] ?? null,
            'webhook_event_type'  => $payload['event'] ?? $payload['type'] ?? null,
            'signature_valid'     => $signatureValid,
            'source_ip'           => $request->ip(),
            'email'               => $data['email'] ?? $data['guest']['email'] ?? null,
            'first_name'          => $data['first_name'] ?? null,
            'last_name'           => $data['last_name'] ?? null,
            'company'             => $data['company'] ?? null,
            'job_title'           => $data['job_title'] ?? null,
            'phone'               => $data['phone_number'] ?? $data['phone'] ?? null,
            'status'              => $data['approval_status'] ?? $data['status'] ?? null,
            'registration_date'   => $data['created_at'] ?? $data['registered_at'] ?? null,
            'raw_payload'         => json_encode($payload),
            'raw'                 => $rawBody,
        ];

        // No guest id (malformed/unexpected payload) — still log it, just
        // without the idempotency key, so nothing from Luma is dropped.
        if (!$guestId) {
            $attributes['luma_guest_id'] = null;
            return LumaRegistration::create($attributes);
        }

        return LumaRegistration::updateOrCreate(
            ['luma_guest_id' => $guestId],
            $attributes
        );
    }
}
