# iCounter — Luma → HubSpot Contact Sync — Implementation Plan

Client: **icounter**
Goal: real-time sync of Luma event registrations into HubSpot contacts, with event tracking, registration-status updates, and workflow-trigger support.

> Isolation rule: this feature lives in its own namespace/folder/table/routes/env vars. It must not touch, import, or modify any existing integration (`Gx`, `Userback`, `Kirim`, etc). No shared services, no shared models, no shared config keys.

---

## 1. How registration events reach us

Luma has no outbound webhook product for third-party apps (as of now, Luma webhooks are limited/invite-only for API partners). Two options:

- **Option A — Luma webhook (if client's Luma plan has API/webhook access)**: Luma POSTs event on registration. Confirm with client whether their Luma plan exposes `event.person_added` / registration webhooks and an API key.
- **Option B — Polling fallback**: scheduled command polls Luma API (`GET /public/v1/event/get-guests` or equivalent) per tracked event every N minutes, diffs against stored state, and processes new/changed registrations.

**Plan:** build for Option A (webhook) as the primary path since requirement says "real time," but implement the sync logic as a standalone service class independent of the entry point, so Option B (a scheduled command reusing the same service) can be added later with zero rework if Luma webhook access isn't available. Confirm with client which applies before building the receiver.

---

## 2. Namespace / file layout (fully isolated)

```
app/Http/Controllers/Icounter/LumaWebhookController.php
app/Service/Icounter/LumaHubspotSyncService.php   (core sync logic, testable, no HTTP concerns)
app/Service/Icounter/HubspotClient.php            (thin wrapper: upsert contact, marketing event, workflow-safe property writes)
app/Models/Icounter/LumaRegistration.php          (local record of every registration + sync state)
app/Models/Icounter/LumaSyncLog.php                (structured error/attempt log, separate from Laravel log files)
app/Console/Commands/Icounter/SyncLumaRegistrations.php (optional polling fallback / retry sweeper)
database/migrations/..._create_luma_registrations_table.php
database/migrations/..._create_luma_sync_logs_table.php
config/icounter.php                                (all keys read from env, nothing hardcoded)
routes/web.php  -> new isolated block, same pattern as "Userback" section
```

No changes to `app/Service/Gx/*`, `app/Http/Controllers/Gx/*`, `app/Models/Gx/*`, or the Userback files.

---

## 3. Data model

### `luma_registrations` (system of record / idempotency + status tracking)

| column | notes |
|---|---|
| id | pk |
| luma_event_id | Luma event id (indexed) |
| luma_event_name | snapshot at sync time |
| luma_guest_id | Luma registration/guest id — **unique**, used for idempotency |
| email | indexed |
| first_name, last_name | |
| company, job_title, phone | nullable |
| status | enum: registered, attended, cancelled, no_show |
| registration_date | timestamp from Luma payload |
| hubspot_contact_id | nullable until first successful sync |
| hubspot_marketing_event_synced_at | nullable |
| last_status_synced | mirrors `status` at last successful HubSpot push, used to detect changes |
| sync_state | enum: pending, synced, failed |
| attempts | int, default 0 |
| last_error | text, nullable |
| raw_payload | json, full Luma payload for audit/replay |
| timestamps | |

Unique constraint on `luma_guest_id` — this is the primary duplicate-prevention mechanism (not email, since one person can register for multiple events; email alone must never be the create/update key at the registration-record level, only at the HubSpot-contact level).

### `luma_sync_logs`
Append-only audit trail: registration_id, action (create/update/status_change/retry), request/response snapshot, http_status, error_message, created_at. Used for "surface meaningful errors, easy to investigate" requirement — queryable, not just log files.

---

## 4. Sync flow

1. **Receive** — webhook controller validates signature (see §6), decodes payload, extracts guest/event fields.
2. **Idempotent upsert of local record** — `LumaRegistration::updateOrCreate(['luma_guest_id' => ...], [...])`. This alone prevents duplicate processing even if Luma retries the webhook.
3. **Dispatch to queued job** (`SyncLumaRegistrationToHubspot`) — webhook returns 200 immediately; actual HubSpot call happens async via Laravel queue. This satisfies "real time" without blocking the webhook response and gives us Laravel's built-in retry/backoff.
4. **Job logic**:
   - Search HubSpot contact by email (`GET /crm/v3/objects/contacts/search` on `email`).
   - If found → `PATCH` update (never create a second contact for same email).
   - If not found → `POST` create.
   - Store returned `hubspot_contact_id` on `LumaRegistration`.
   - Write/refresh the **event association** (§5).
   - Update registration-status properties (§5).
   - On success: `sync_state = synced`, clear `last_error`.
   - On failure: log to `luma_sync_logs`, increment `attempts`, `sync_state = failed`, let queue's automatic retry (exponential backoff, e.g. 3 tries: 30s/2m/10m) handle retry; after max attempts, stays `failed` for manual/alerted follow-up.
5. **Status-change events** (attended / cancelled / no-show) reuse the same job — they arrive as a different Luma webhook event type or polling diff, keyed by the same `luma_guest_id`, and only update status fields + push corresponding HubSpot properties — never create a duplicate contact.

---

## 5. HubSpot data mapping

### Contact properties (standard + custom)
| Luma field | HubSpot property |
|---|---|
| First Name | `firstname` |
| Last Name | `lastname` |
| Email | `email` |
| Company | `company` |
| Job Title | `jobtitle` |
| Phone | `phone` |

### Custom contact properties (per-registration snapshot — last event touched)
Create these as custom HubSpot properties (one-time setup, documented in §8):
- `luma_last_event_name` (single-line text)
- `luma_last_event_id` (single-line text)
- `luma_last_registration_date` (date)
- `luma_last_registration_status` (dropdown: Registered/Attended/Cancelled/No-show)

### Event-level tracking — recommended approach: **Marketing Events object**
Use HubSpot's native **Marketing Events API** (`/marketing/v3/marketing-events`):
1. On first registration for a given `luma_event_id`, `POST` (or upsert) a Marketing Event in HubSpot keyed by `externalEventId = luma_event_id`.
2. For every registration, call `POST /marketing/v3/marketing-events/{externalEventId}/attendance/{subscriberState}` (or the "email" upsert attendance endpoint) to associate the contact with that event with state `REGISTERED`, `ATTENDED`, `CANCELLED`.
3. This is HubSpot's purpose-built object for "which event did this contact register for," gives native reporting/segmentation ("Marketing Event" filters in lists/workflows), and scales cleanly to "next event and the next" — each new Luma event just becomes a new Marketing Event record, no schema change needed.
4. **Combine with** the custom contact properties above — properties give workflow-enrollment triggers *and* simple list filters without needing marketing-event-specific permissions/subscriptions; the Marketing Event object gives proper per-event attendee reporting. Belt-and-suspenders, both cheap to maintain from the same job.

### Workflow automation
Because we write standard contact property changes (`luma_last_registration_status`, `luma_last_event_id`) and Marketing Event attendance, HubSpot workflows can enroll on:
- "Contact property `luma_last_registration_status` is Registered" → registration workflow
- "... is Attended" → attendance workflow
- "... is Cancelled" → cancellation workflow
- "Marketing Event `luma_last_event_id` equals X" → per-event workflow
No custom workflow-trigger code needed — native HubSpot enrollment triggers on property change / marketing event membership. Document exact property names for the client's HubSpot admin to build workflows on top of.

---

## 6. Security

- **Webhook authenticity**: verify Luma's webhook signature header (HMAC) if Luma provides one; if not, require a secret token in the URL path or a custom header (`X-Icounter-Luma-Secret`) compared with `hash_equals()` against `config('icounter.luma_webhook_secret')`. Reject with 401 on mismatch — do not process or log the payload before validation.
- **Secrets**: `HUBSPOT_ICOUNTER_TOKEN`, `LUMA_ICOUNTER_API_KEY`, `LUMA_ICOUNTER_WEBHOOK_SECRET` — new, dedicated env vars in `config/icounter.php`, never reuse `HUBSPOT_TOKEN_JJ` or other integration's keys.
- **Transport**: HTTPS only (enforced by hosting), Guzzle client with explicit timeout, no verify=false.
- **Validation**: strict payload validation (email format, required guest id) before DB write; reject/log malformed payloads with 422, don't silently drop.
- **Rate/abuse**: route not behind `auth:sanctum` (external webhook) but protected by signature check + optional IP allowlist if Luma publishes static egress IPs; consider Laravel `throttle` middleware as a backstop.
- **PII in logs**: log emails/names to the dedicated `luma_sync_logs` table (access-controlled DB) rather than plaintext application log files where feasible; avoid logging phone numbers in error traces beyond what's needed to diagnose.
- **HubSpot token storage**: env-only, never committed, never returned in any API response.

---

## 7. Error handling & observability

- Queue job retries: 3 attempts, exponential backoff, then marked `failed` + row stays queryable.
- Every attempt (success or failure) writes a `luma_sync_logs` row — request summary, response status, error message.
- Failed rows surfaced via a simple internal endpoint/artisan command (`icounter:luma-failed-syncs`) client/dev can check — no dashboard build unless requested.
- Optional: Laravel `Log::channel('icounter')` — dedicated log channel/file (`storage/logs/icounter.log`) separate from `laravel.log`, so this integration's noise never mixes with others.
- Duplicate prevention double-layer: (1) unique `luma_guest_id` at DB level, (2) HubSpot email-search-before-create at API level.

---

## 8. One-time HubSpot setup (manual, documented for client)

- Create custom contact properties listed in §5.
- Confirm Marketing Events feature is enabled on their HubSpot subscription (Marketing Hub Pro+ requirement — **flag to client early**, this gates the recommended approach).
- Create a private app / token scoped to: `crm.objects.contacts.read/write`, `marketing-events.read/write`.

---

## 9. Open questions for client before build starts

1. Does their Luma plan support outbound webhooks / API access? Which plan tier?
2. Is HubSpot Marketing Events available on their subscription (Pro tier+)? If not, fall back to contact-properties-only approach for event tracking (still fully workflow-triggerable, just no native per-event attendee report).
3. Any existing custom HubSpot properties they want reused instead of new `luma_*` ones?
4. Expected registration volume (for queue sizing) and how many concurrent Luma events they run.

---

## 10. Build order

1. Migrations + models (`Icounter\LumaRegistration`, `LumaSyncLog`).
2. `config/icounter.php` + `.env` keys.
3. `HubspotClient` service (search/create/update contact, marketing event upsert, attendance upsert) — unit-testable, mockable.
4. `LumaHubspotSyncService` (orchestrates: idempotent local upsert → job dispatch).
5. Queued job `SyncLumaRegistrationToHubspot` with retry/backoff + logging.
6. `LumaWebhookController` + signature verification + isolated route block in `routes/web.php`.
7. Optional polling command if Option A unavailable.
8. Tests: signature rejection, duplicate webhook delivery, create-vs-update contact branching, status-change flow, failure/retry logging.
9. Manual HubSpot setup per §8, then end-to-end test against Luma sandbox/test event.
