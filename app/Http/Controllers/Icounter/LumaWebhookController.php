<?php

namespace App\Http\Controllers\Icounter;

use App\Http\Controllers\Controller;
use App\Jobs\Icounter\SyncLumaRegistrationToHubspot;
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

        $signatureValid = $this->verifySignature($request, $rawBody);

        if ($signatureValid === false) {
            Log::warning('Icounter Luma webhook: signature verification failed', [
                'ip' => $request->ip(),
            ]);

            // Do not store — an invalid signature means this wasn't a
            // genuine Luma delivery, no point logging junk/replay traffic.
            return response()->json(['status' => false, 'message' => 'Invalid signature'], 401);
        }

        $payload = json_decode($rawBody, true) ?? [];

        $record = $this->storeRaw($request, $rawBody, $payload, $signatureValid);

        // Dispatch async so Luma gets a fast 200 regardless of HubSpot's
        // response time; the job handles its own retry/backoff on failure.
        if ($record->email) {
            SyncLumaRegistrationToHubspot::dispatch($record->id);
        }

        return response()->json(['status' => true, 'id' => $record->id]);
    }

    /**
     * Luma signs webhooks per the Standard Webhooks spec: headers
     * webhook-id, webhook-timestamp, webhook-signature; signed content is
     * "{id}.{timestamp}.{body}"; secret is base64 after stripping the
     * "whsec_" prefix.
     * Returns true/false when a secret is configured, null when no secret
     * is configured yet (unblocked for initial setup, still fully logged).
     */
    private function verifySignature(Request $request, string $rawBody): ?bool
    {
        $secret = config('icounter.luma.webhook_secret');

        if (!$secret) {
            return null;
        }

        $webhookSignature = $request->header('webhook-signature');

        if (!$webhookSignature) {
            \Log::warning('Icounter Luma webhook: missing webhook-signature header', [
                'all_headers' => $request->headers->all(),
            ]);
            return false;
        }

        // Per Luma's docs: header is "t=<timestamp>,v1=<hexsig>". Signed
        // payload is "{timestamp}.{raw_body}" — HMAC-SHA256 using the full
        // whsec_ secret string as-is (not stripped, not base64-decoded).
        $parts = [];
        foreach (explode(',', $webhookSignature) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, null);
            $parts[$key] = $value;
        }

        $timestamp = $parts['t'] ?? null;
        $received  = $parts['v1'] ?? null;

        if (!$timestamp || !$received) {
            return false;
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$rawBody}", $secret);

        if (hash_equals($expected, $received)) {
            return true;
        }

        \Log::warning('Icounter Luma webhook: signature mismatch', [
            'timestamp'        => $timestamp,
            'received_sig'     => $received,
            'expected_sig'     => $expected,
            'raw_body_length'  => strlen($rawBody),
        ]);

        return false;
    }

    /**
     * Persist the webhook exactly as received (raw + basic parsed details),
     * using luma_guest_id as the idempotency key so retried deliveries from
     * Luma don't create duplicate rows.
     */
    private function storeRaw(Request $request, string $rawBody, array $payload, ?bool $signatureValid): LumaRegistration
    {
        $data  = $payload['data'] ?? $payload;
        $event = $data['event'] ?? [];

        $guestId = $data['api_id'] ?? $data['id'] ?? null;

        $attributes = [
            'luma_event_id'       => $event['api_id'] ?? $event['id'] ?? null,
            'luma_event_name'     => $event['name'] ?? null,
            'webhook_event_type'  => $payload['type'] ?? null,
            'signature_valid'     => $signatureValid,
            'source_ip'           => $request->ip(),
            'email'               => $data['user_email'] ?? null,
            'first_name'          => ($data['user_first_name'] ?? null) ?: null,
            'last_name'           => ($data['user_last_name'] ?? null) ?: null,
            'company'             => $this->answer($data, 'company'),
            'job_title'           => $this->answer($data, 'job title') ?? $this->answer($data, 'title'),
            'phone'               => $data['phone_number'] ?? null,
            'status'              => $this->resolveStatus($data),
            'registration_date'   => $data['registered_at'] ?? $data['created_at'] ?? null,
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

    /**
     * Luma's registration_status/type/checked_in_at combo maps to our
     * normalized status: registered, attended, cancelled, no_show.
     */
    private function resolveStatus(array $data): ?string
    {
        if (!empty($data['checked_in_at'])) {
            return 'attended';
        }

        $approval = $data['approval_status'] ?? null;

        if ($approval === 'declined') {
            return 'cancelled';
        }

        if ($approval === 'approved' || $approval === 'pending_approval') {
            return 'registered';
        }

        return $approval;
    }

    /**
     * Custom questions come back in registration_answers as a list of
     * {question/label, answer} pairs — Luma has no dedicated company/job
     * title fields, so pull them from there when the host has that question
     * enabled on the event.
     */
    private function answer(array $data, string $labelContains): ?string
    {
        foreach ($data['registration_answers'] ?? [] as $item) {
            $label = strtolower($item['question'] ?? $item['label'] ?? '');
            if (strpos($label, $labelContains) !== false) {
                return $item['answer'] ?? null;
            }
        }

        return null;
    }
}
