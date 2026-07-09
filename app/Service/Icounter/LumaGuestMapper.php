<?php

namespace App\Service\Icounter;

/**
 * Normalizes a Luma "guest" object — whether it comes from a webhook
 * payload's `data` key or from the /v1/events/guests/list API — into the
 * flat attribute shape stored on LumaRegistration. Both the webhook
 * receiver and the bulk importer must use this so the two paths can never
 * drift out of sync on field mapping again.
 */
class LumaGuestMapper
{
    /**
     * @param array $guest Luma guest object (webhook `data` or a list `entries[]` item)
     * @param array $event Luma event object with at least api_id/id + name, or [] if unknown
     */
    public static function toAttributes(array $guest, array $event = []): array
    {
        return [
            'luma_event_id'      => $event['api_id'] ?? $event['id'] ?? null,
            'luma_event_name'    => $event['name'] ?? null,
            'email'              => $guest['user_email'] ?? null,
            'first_name'         => ($guest['user_first_name'] ?? null) ?: null,
            'last_name'          => ($guest['user_last_name'] ?? null) ?: null,
            'company'            => self::answer($guest, 'company'),
            'job_title'          => self::answer($guest, 'job title') ?? self::answer($guest, 'title'),
            'phone'              => $guest['phone_number'] ?? null,
            'status'             => self::resolveStatus($guest),
            'registration_date'  => $guest['registered_at'] ?? $guest['created_at'] ?? null,
        ];
    }

    public static function guestId(array $guest): ?string
    {
        return $guest['api_id'] ?? $guest['id'] ?? null;
    }

    /**
     * Luma's approval_status/checked_in_at combo maps to our normalized
     * status: registered, attended, cancelled. ("no_show" is derived later
     * by a scheduled sweep, not from any single Luma field.)
     */
    public static function resolveStatus(array $guest): ?string
    {
        if (!empty($guest['checked_in_at'])) {
            return 'attended';
        }

        $approval = $guest['approval_status'] ?? null;

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
    public static function answer(array $guest, string $labelContains): ?string
    {
        foreach ($guest['registration_answers'] ?? [] as $item) {
            $label = strtolower($item['question'] ?? $item['label'] ?? '');
            if (strpos($label, $labelContains) !== false) {
                return $item['answer'] ?? null;
            }
        }

        return null;
    }
}
