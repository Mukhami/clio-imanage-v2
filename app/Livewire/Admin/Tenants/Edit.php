<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tenants;

use App\Integrations\Imanage\ImanageAuthConnector;
use App\Integrations\Imanage\ImanageDiscoveryConnector;
use App\Integrations\Imanage\Requests\GetDiscovery;
use App\Integrations\Imanage\Requests\GetOAuthToken;
use App\Models\ClioLocation;
use App\Models\Tenant;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Edit extends Component
{
    public Tenant $tenant;

    public string $name = '';

    public string $slug = '';

    public string $reference = '';

    public string $status = 'pending';

    public string $clioLocationId = '';

    public string $clioAppId = '';

    public string $clioAppSecret = '';

    public string $imanageCloudUrl = '';

    public string $imanageCustomerId = '';

    public string $imanageAppId = '';

    public string $imanageAppSecret = '';

    public string $imanageUsername = '';

    public string $imanagePassword = '';

    public bool $passwordAuthentication = false;

    public bool $hasGroupSecurityMapping = false;

    public bool $enableWorkspaceLinkCustomField = false;

    // Credential test feedback
    public ?string $credentialTestStatus = null;

    public string $credentialTestMessage = '';

    public function mount(int $id): void
    {
        $this->tenant = Tenant::with('clioLocation')->findOrFail($id);

        $this->name            = $this->tenant->name;
        $this->slug            = $this->tenant->slug;
        $this->reference       = $this->tenant->reference ?? '';
        $this->status          = $this->tenant->status->value;
        $this->clioLocationId  = (string) ($this->tenant->clio_location_id ?? '');
        $this->imanageCloudUrl   = $this->tenant->imanage_cloud_url
            ?? (string) config('services.imanage.api_url', '');
        $this->imanageCustomerId = $this->tenant->imanage_customer_id ?? '';

        // Encrypted fields — decrypted transparently by the model cast
        $this->clioAppId        = (string) ($this->tenant->clio_app_id ?? '');
        $this->clioAppSecret    = (string) ($this->tenant->clio_app_secret ?? '');
        $this->imanageAppId     = (string) ($this->tenant->imanage_app_id ?? '');
        $this->imanageAppSecret = (string) ($this->tenant->imanage_app_secret ?? '');
        $this->imanageUsername  = (string) ($this->tenant->imanage_username ?? '');
        $this->imanagePassword  = (string) ($this->tenant->imanage_password ?? '');

        $this->passwordAuthentication          = (bool) $this->tenant->password_authentication;
        $this->hasGroupSecurityMapping         = (bool) $this->tenant->has_group_security_mapping;
        $this->enableWorkspaceLinkCustomField  = (bool) $this->tenant->enable_workspace_link_custom_field;
    }

    public function testImanageCredentials(): void
    {
        $this->validate([
            'imanageCloudUrl'  => 'required|url',
            'imanageAppId'     => 'required|string',
            'imanageAppSecret' => 'required|string',
            'imanageUsername'  => 'required|string',
            'imanagePassword'  => 'required|string',
        ]);

        $this->credentialTestStatus  = null;
        $this->credentialTestMessage = '';

        try {
            $authConnector = new ImanageAuthConnector($this->imanageCloudUrl);
            $tokenResponse = $authConnector->send(new GetOAuthToken(
                $this->imanageUsername,
                $this->imanagePassword,
                $this->imanageAppId,
                $this->imanageAppSecret,
            ));

            if ($tokenResponse->failed()) {
                $this->credentialTestStatus  = 'error';
                $this->credentialTestMessage = 'Authentication failed: '.$this->extractSaloonError($tokenResponse);

                return;
            }

            $accessToken = $tokenResponse->json('access_token');

            if (! is_string($accessToken) || $accessToken === '') {
                $this->credentialTestStatus  = 'error';
                $this->credentialTestMessage = 'Authentication succeeded but no access token was returned.';

                return;
            }

            $discoveryConnector = new ImanageDiscoveryConnector($this->imanageCloudUrl, $accessToken);
            $discoveryResponse  = $discoveryConnector->send(new GetDiscovery());

            if ($discoveryResponse->failed()) {
                $this->credentialTestStatus  = 'warning';
                $this->credentialTestMessage = 'Authenticated successfully, but discovery request failed.';

                return;
            }

            $customerId = $discoveryResponse->json('data.user.customer_id');

            if ($customerId) {
                $this->imanageCustomerId     = (string) $customerId;
                $this->credentialTestStatus  = 'success';
                $this->credentialTestMessage = "Connected successfully. Customer ID \"{$customerId}\" has been populated.";
            } else {
                $this->credentialTestStatus  = 'warning';
                $this->credentialTestMessage = 'Authenticated successfully, but no customer ID found in the discovery response.';
            }
        } catch (\Throwable $e) {
            $this->credentialTestStatus  = 'error';
            $this->credentialTestMessage = 'Connection error: '.$e->getMessage();
        }
    }

    public function save(): void
    {
        $this->validate([
            'name'              => 'required|string|max:255',
            'slug'              => 'required|string|max:255|unique:tenants,slug,' . $this->tenant->id,
            'reference'         => 'nullable|uuid|unique:tenants,reference,' . $this->tenant->id,
            'status'            => 'required|in:active,pending,suspended,archived',
            'clioLocationId'    => 'required|exists:clio_locations,id',
            'imanageCloudUrl'   => 'nullable|url',
            'clioAppId'         => 'nullable|string',
            'clioAppSecret'     => 'nullable|string',
            'imanageCustomerId' => 'nullable|string',
            'imanageAppId'      => 'nullable|string',
            'imanageAppSecret'  => 'nullable|string',
            'imanageUsername'   => 'nullable|string',
            'imanagePassword'   => 'nullable|string',
        ]);

        $data = [
            'name'                             => $this->name,
            'slug'                             => $this->slug,
            // keep existing reference if field was cleared — column is NOT NULL
            'reference'                        => $this->reference ?: $this->tenant->reference,
            'status'                           => $this->status,
            'clio_location_id'                 => $this->clioLocationId,
            'imanage_cloud_url'                => $this->imanageCloudUrl ?: null,
            'imanage_customer_id'              => $this->imanageCustomerId ?: null,
            'password_authentication'          => $this->passwordAuthentication,
            'has_group_security_mapping'       => $this->hasGroupSecurityMapping,
            'enable_workspace_link_custom_field' => $this->enableWorkspaceLinkCustomField,
        ];

        $data['clio_app_id']        = $this->clioAppId ?: null;
        $data['clio_app_secret']    = $this->clioAppSecret ?: null;
        $data['imanage_app_id']     = $this->imanageAppId ?: null;
        $data['imanage_app_secret'] = $this->imanageAppSecret ?: null;
        $data['imanage_username']   = $this->imanageUsername ?: null;
        $data['imanage_password']   = $this->imanagePassword ?: null;

        $this->tenant->update($data);

        Flux::toast(text: 'Tenant updated successfully.', variant: 'success');

        $this->redirect(route('admin.tenants.show', $this->tenant->id), navigate: true);
    }

    #[Computed]
    public function clioLocations()
    {
        return ClioLocation::orderBy('name')->get();
    }

    private function extractSaloonError(\Saloon\Http\Response $response): string
    {
        $raw = $response->json('error_description') ?? $response->json('error') ?? "HTTP {$response->status()}";

        if (is_array($raw)) {
            return implode('; ', array_map('strval', $raw));
        }

        return (string) $raw;
    }

    public function render(): View
    {
        return view('livewire.admin.tenants.edit');
    }
}
