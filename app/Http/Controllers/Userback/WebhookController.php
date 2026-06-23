<?php

namespace App\Http\Controllers\Userback;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class WebhookController extends Controller
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . env('HUBSPOT_TOKEN_JJ'),
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    public function receive(Request $request)
    {
        try {
            $data = $request->input('data', []);

            $email = $data['email'] ?? null;
            $name  = $data['name']  ?? '';

            if (!$email) {
                return response()->json(['status' => false, 'message' => 'No email in payload'], 422);
            }

            $contactId = $this->upsertContact($email, $name);
            $this->createTask($contactId, $data, $request->input('url'));

            return response()->json(['status' => true, 'message' => 'Task created in HubSpot']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function upsertContact(string $email, string $name): string
    {
        $nameParts = explode(' ', trim($name), 2);

        $response = $this->client->post('https://api.hubapi.com/crm/v3/objects/contacts/upsert', [
            'json' => [
                'inputs' => [[
                    'idProperty' => 'email',
                    'properties' => array_filter([
                        'email'     => $email,
                        'firstname' => $nameParts[0] ?? '',
                        'lastname'  => $nameParts[1] ?? '',
                    ]),
                ]],
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        return $result['results'][0]['id'];
    }

    private function createTask(string $contactId, array $data, ?string $shareUrl): void
    {
        $feedbackType = $data['feedback_type'] ?? 'Feedback';
        $project      = $data['project']       ?? '';
        $description  = $data['description']   ?? '';
        $page         = $data['page']          ?? '';
        $browser      = $data['browser']       ?? '';
        $location     = $data['location']      ?? '';
        $priority     = $data['priority']      ?? '';
        $screenshot   = $data['screenshot'][0]['url'] ?? null;

        $subject = "[{$feedbackType}] {$project} — " . ($data['name'] ?? $data['email']);

        $body = implode("\n", array_filter([
            'Description: ' . $description,
            'Page: '        . $page,
            'Browser: '     . $browser,
            'Location: '    . $location,
            'Priority: '    . $priority,
            $screenshot ? 'Screenshot: ' . $screenshot : null,
            $shareUrl   ? 'View in Userback: ' . $shareUrl : null,
        ]));

        $taskResponse = $this->client->post('https://api.hubapi.com/crm/v3/objects/tasks', [
            'json' => [
                'properties' => [
                    'hs_task_subject'  => $subject,
                    'hs_task_body'     => $body,
                    'hs_task_status'   => 'NOT_STARTED',
                    'hs_task_priority' => $this->mapPriority($priority),
                    'hs_task_type'     => 'TODO',
                    'hs_timestamp'     => now()->addDays(3)->toIso8601String(),
                ],
                'associations' => [[
                    'to'    => ['id' => $contactId],
                    'types' => [[
                        'associationCategory' => 'HUBSPOT_DEFINED',
                        'associationTypeId'   => 204,
                    ]],
                ]],
            ],
        ]);
    }

    private function mapPriority(string $priority): string
    {
        return match (strtolower($priority)) {
            'high', 'urgent'  => 'HIGH',
            'low'             => 'LOW',
            default           => 'MEDIUM',
        };
    }
}
