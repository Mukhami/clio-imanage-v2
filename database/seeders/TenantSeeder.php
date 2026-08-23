<?php

namespace Database\Seeders;

use App\Enums\ProcessingStage;
use App\Enums\TenantStatus;
use App\Models\ClioLocation;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Models\WebhookRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $clioLocation = ClioLocation::first();

        // Create tenant
        $tenant = Tenant::create([
            'name'           => 'Demo Law Firm',
            'slug'           => 'demo-law-firm',
            'reference'      => Str::uuid()->toString(),
            'status'         => TenantStatus::Active,
            'clio_location_id' => $clioLocation?->id,
            'onboarded_at'   => now()->subDays(30),
        ]);

        // Active subscription
        TenantSubscription::create([
            'tenant_id'  => $tenant->id,
            'reference'  => 'SUB-' . strtoupper(Str::random(8)),
            'status'     => 'active',
            'plan_type'  => 'Professional',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date'   => now()->addDays(335)->toDateString(),
            'clio_users_at_start' => 12,
        ]);

        // Tenant Admin user
        $admin = User::create([
            'name'              => 'Demo Admin',
            'email'             => 'tenantadmin@clio-imanage.test',
            'password'          => Hash::make('password'),
            'tenant_id'         => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Tenant Admin');
        $tenant->update(['owner_id' => $admin->id]);

        // Tenant Viewer user
        $viewer = User::create([
            'name'              => 'Demo Viewer',
            'email'             => 'tenantviewer@clio-imanage.test',
            'password'          => Hash::make('password'),
            'tenant_id'         => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $viewer->assignRole('Tenant Viewer');

        // Sample webhook requests across various stages
        $samplePayload = [
            'data' => [
                'id'     => 12345,
                'etag'   => '"abc123def456"',
                'object' => [
                    'id'     => 67890,
                    'number' => '2024-001',
                    'display_number' => '2024-001',
                    'status' => 'open',
                    'client' => [
                        'id'   => 111,
                        'name' => 'Acme Corp',
                        'type' => 'Company',
                    ],
                    'practice_area' => [
                        'id'   => 5,
                        'name' => 'Corporate Law',
                    ],
                    'responsible_attorney' => [
                        'id'   => 42,
                        'name' => 'Jane Smith',
                    ],
                    'open_date'  => '2024-01-15',
                    'close_date' => null,
                ],
            ],
            'meta' => [
                'uuid'           => Str::uuid()->toString(),
                'etag'           => '"abc123def456"',
                'type'           => 'matter',
                'data'           => [],
                'timestamp'      => now()->toISOString(),
                'webhook_id'     => 9001,
                'event'          => 'updated',
                'version'        => '4',
                'url'            => 'https://app.clio.com/api/v4/matters/67890.json',
            ],
        ];

        $stages = [
            [ProcessingStage::Completed, 'completed', now()->subHours(2), true],
            [ProcessingStage::Completed, 'completed', now()->subHours(5), true],
            [ProcessingStage::Failed, 'failed', now()->subHours(1), false],
            [ProcessingStage::Skipped, 'skipped', now()->subHours(3), false],
            [ProcessingStage::Processing, 'processing', now()->subMinutes(10), false],
            [ProcessingStage::Enqueued, 'enqueued', now()->subMinutes(5), false],
            [ProcessingStage::Completed, 'completed', now()->subDays(1), true],
            [ProcessingStage::Failed, 'failed', now()->subDays(1), false],
            [ProcessingStage::Completed, 'completed', now()->subDays(2), true],
            [ProcessingStage::Completed, 'completed', now()->subDays(2), true],
        ];

        foreach ($stages as [$stage, $stageValue, $createdAt, $isCompleted]) {
            $clientId = rand(100, 999);
            $matterId = rand(10000, 99999);

            $payload = array_merge($samplePayload, []);
            $payload['data']['object']['client']['id'] = $clientId;
            $payload['data']['object']['id'] = $matterId;
            $payload['meta']['uuid'] = Str::uuid()->toString();

            WebhookRequest::create([
                'tenant_id'                         => $tenant->id,
                'webhook_id'                        => null,
                'url'                               => 'https://matterlynk.app/webhook/' . $tenant->reference,
                'headers'                           => [
                    'content-type'        => ['application/json'],
                    'x-clio-signature'    => ['sha256=' . hash('sha256', json_encode($payload))],
                    'x-clio-webhook-id'   => ['9001'],
                ],
                'body'                              => $payload,
                'correlation_id'                    => Str::uuid()->toString(),
                'processing_stage'                  => $stage->value,
                'retrieved_client_id'               => $isCompleted ? (string) $clientId : null,
                'retrieved_matter_id'               => $isCompleted ? (string) $matterId : null,
                'client_activity_complete'          => $isCompleted,
                'matter_activity_complete'          => $isCompleted,
                'workspace_activity_complete'       => $isCompleted,
                'folder_activity_complete'          => $isCompleted,
                'security_activity_complete'        => $isCompleted,
                'workspace_link_custom_field_populated' => $isCompleted,
                'error_message'                     => $stageValue === 'failed' ? 'iManage workspace creation failed: 403 Forbidden — insufficient permissions on library ACTIVE.' : null,
                'error_count'                       => $stageValue === 'failed' ? 1 : 0,
                'started_at'                        => $isCompleted || in_array($stageValue, ['failed', 'processing']) ? $createdAt->copy()->addSeconds(2) : null,
                'completed_at'                      => $isCompleted ? $createdAt->copy()->addSeconds(rand(4, 30)) : null,
                'created_at'                        => $createdAt,
                'updated_at'                        => $createdAt,
            ]);
        }

        $this->command->info('TenantSeeder: Created tenant "Demo Law Firm" with admin and viewer users.');
        $this->command->info('  Tenant Admin: tenantadmin@clio-imanage.test / password');
        $this->command->info('  Tenant Viewer: tenantviewer@clio-imanage.test / password');
    }
}
