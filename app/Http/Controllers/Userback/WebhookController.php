<?php

namespace App\Http\Controllers\Userback;

use App\Http\Controllers\Controller;
use App\Models\UserbackWebhook;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class WebhookController extends Controller
{
    private Client $hsClient;
    private Client $hsProjectClient;

    public function __construct()
    {
        $this->hsClient = new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . env('HUBSPOT_TOKEN_JJ'),
                'Content-Type'  => 'application/json',
            ],
        ]);

        $this->hsProjectClient = new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . env('USER_BACK_HUBSPOT_SERVICE_KEY'),
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    public function receive(Request $request)
    {
        $rawJson = $request->getContent();
        $payload = json_decode($rawJson, true) ?? $request->all();
        $data    = $payload['data'] ?? [];
        $email   = $data['email'] ?? null;

        if (!$email) {
            return response()->json(['status' => false, 'message' => 'No email in payload'], 422);
        }

        // Prevent duplicate if Userback retries
        if (UserbackWebhook::where('userback_id', $data['id'])->exists()) {
            return response()->json(['status' => true, 'message' => 'Already received']);
        }

        try {
            $record = UserbackWebhook::create([
                'userback_id'       => (string) $data['id'],
                'action'            => $payload['action'] ?? '',
                'type'              => $payload['type'] ?? '',
                'project'           => $data['project'] ?? null,
                'feedback_type'     => $data['feedback_type'] ?? null,
                'email'             => $email,
                'name'              => $data['name'] ?? null,
                'page'              => $data['page'] ?? null,
                'priority'          => $data['priority'] ?? null,
                'browser'           => $data['browser'] ?? null,
                'location'          => $data['location'] ?? null,
                'description'       => $data['description'] ?? null,
                'screenshot_url'    => $data['screenshot'][0]['url'] ?? null,
                'share_url'         => $payload['url'] ?? null,
                'raw_payload'       => $payload,
                'raw'               => $rawJson,
                'pushed_to_hubspot' => false,
            ]);
        } catch (\Exception $e) {
            \Log::error('Userback DB save failed: ' . $e->getMessage(), ['payload' => $rawJson]);
            return response()->json(['status' => false, 'message' => 'Failed to save'], 500);
        }

        // Push to HubSpot — failure won't affect 200 response
        try {
            $this->pushToHubspot($record);
        } catch (\Exception $e) {
            \Log::error('Userback HubSpot push failed: ' . $e->getMessage(), ['id' => $record->id]);
        }

        return response()->json(['status' => true, 'message' => 'Received']);
    }

    public function testPush($id)
    {
        try {
            $record    = UserbackWebhook::findOrFail($id);
            $projectId = $record->project ? $this->findHubspotProject($record->project) : null;

            if (!$projectId) {
                return response()->json([
                    'status'  => false,
                    'message' => "No HubSpot project found matching userback_project_name = '{$record->project}'",
                ], 404);
            }

            $this->pushToHubspot($record);
            return response()->json(['status' => true, 'message' => "Record #{$id} pushed to HubSpot project {$projectId}"]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function pushToHubspot(UserbackWebhook $record): void
    {
        $projectId = $record->project ? $this->findHubspotProject($record->project) : null;

        if (!$projectId) {
            return;
        }

        $contactId = $this->upsertContact($record->email, $record->name ?? '');
        $this->createTask($contactId, $projectId, $record);

        $record->update([
            'pushed_to_hubspot' => true,
            'hubspot_pushed_at' => now(),
        ]);
    }

    private function findHubspotProject(string $projectName): ?string
    {
        $response = $this->hsProjectClient->post('https://api.hubapi.com/crm/v3/objects/PROJECT/search', [
            'json' => [
                'filterGroups' => [[
                    'filters' => [[
                        'propertyName' => 'userback_project_id',
                        'operator'     => 'EQ',
                        'value'        => $projectName,
                    ]],
                ]],
                'properties' => ['userback_project_id', 'hs_name'],
                'limit'      => 1,
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        return $result['results'][0]['id'] ?? null;
    }

    private function upsertContact(string $email, string $name): string
    {
        $nameParts = explode(' ', trim($name), 2);

        // Try get existing contact first
        try {
            $response = $this->hsProjectClient->get("https://api.hubapi.com/crm/v3/objects/contacts/{$email}?idProperty=email");
            $result   = json_decode($response->getBody()->getContents(), true);
            return $result['id'];
        } catch (\Exception $e) {
            // Contact doesn't exist — create it
        }

        $response = $this->hsProjectClient->post('https://api.hubapi.com/crm/v3/objects/contacts', [
            'json' => [
                'properties' => array_filter([
                    'email'     => $email,
                    'firstname' => $nameParts[0] ?? '',
                    'lastname'  => $nameParts[1] ?? '',
                ]),
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        return $result['id'];
    }

    private function findOwnerIdByEmail(string $email): ?string
    {
        $response = $this->hsProjectClient->get('https://api.hubapi.com/crm/v3/owners?limit=100');
        $result   = json_decode($response->getBody()->getContents(), true);

        foreach ($result['results'] ?? [] as $owner) {
            if (strtolower($owner['email'] ?? '') === strtolower($email)) {
                return (string) $owner['id'];
            }
        }

        return null;
    }

    private function createTask(string $contactId, ?string $projectId, UserbackWebhook $record): void
    {
        $body = implode("\n", array_filter([
            'Page: '     . $record->page,
            'Browser: '  . $record->browser,
            'Location: ' . $record->location,
            'Priority: ' . $record->priority,
            $record->screenshot_url ? 'Screenshot: ' . $record->screenshot_url : null,
            $record->share_url      ? 'View in Userback: ' . $record->share_url : null,
        ]));

        $properties = [
            'hs_task_subject'  => '[User Feedback (Website)] ' . $record->description,
            'hs_task_body'     => $body,
            'hs_task_status'   => 'NOT_STARTED',
            'hs_task_priority' => $this->mapPriority($record->priority ?? ''),
            'hs_task_type'     => 'TODO',
            'hs_timestamp'     => now()->addDays(3)->toIso8601String(),
            'sprint_name'      => 'Userback- Feedbacks',
        ];

        $ownerId = $this->findOwnerIdByEmail($record->email);
        if ($ownerId) {
            $properties['hubspot_owner_id'] = $ownerId;
        }

        $associations = [
            [
                'to'    => ['id' => $contactId],
                'types' => [[
                    'associationCategory' => 'HUBSPOT_DEFINED',
                    'associationTypeId'   => 204,
                ]],
            ],
        ];

        if ($projectId) {
            $associations[] = [
                'to'    => ['id' => $projectId],
                'types' => [[
                    'associationCategory' => 'HUBSPOT_DEFINED',
                    'associationTypeId'   => 1247,
                ]],
            ];
        }

        $this->hsProjectClient->post('https://api.hubapi.com/crm/v3/objects/tasks', [
            'json' => [
                'properties'   => $properties,
                'associations' => $associations,
            ],
        ]);
    }

    private function mapPriority(string $priority): string
    {
        $p = strtolower($priority);
        if (in_array($p, ['high', 'urgent'])) return 'HIGH';
        if ($p === 'low') return 'LOW';
        return 'MEDIUM';
    }
}
