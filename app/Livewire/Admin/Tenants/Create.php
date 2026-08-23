<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tenants;

use App\Models\ClioLocation;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\NewTenantRegistered;
use App\Notifications\UserInvited;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Create extends Component
{
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

    // Initial Tenant Admin
    public bool $createAdmin = false;

    public string $adminName = '';

    public string $adminEmail = '';

    public function mount(): void
    {
        $this->clioAppId        = (string) config('services.clio.key', '');
        $this->clioAppSecret    = (string) config('services.clio.secret', '');
        $this->imanageCloudUrl  = (string) config('services.imanage.api_url', '');
        $this->imanageAppId     = (string) config('services.imanage.app_key', '');
        $this->imanageAppSecret = (string) config('services.imanage.app_secret', '');
    }

    public function updatingName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    public function testImanageCredentials(): void
    {
        $this->validate([
            'imanageCloudUrl' => 'required|url',
            'imanageUsername' => 'required|string',
            'imanagePassword' => 'required|string',
        ]);

        $this->credentialTestStatus  = null;
        $this->credentialTestMessage = '';

        try {
            $baseUrl = rtrim($this->imanageCloudUrl, '/').'/work/api/v2';

            $authResponse = Http::post("{$baseUrl}/session", [
                'username' => $this->imanageUsername,
                'password' => $this->imanagePassword,
            ]);

            if (! $authResponse->successful()) {
                $this->credentialTestStatus  = 'error';
                $this->credentialTestMessage = 'Authentication failed: '.($authResponse->json('error_description') ?? $authResponse->json('error') ?? "HTTP {$authResponse->status()}");

                return;
            }

            $token = $authResponse->json('data.token');

            $customersResponse = Http::withHeaders([
                'X-Auth-Token' => $token,
                'Accept'       => 'application/json',
            ])->get("{$baseUrl}/customers");

            if (! $customersResponse->successful()) {
                $this->credentialTestStatus  = 'warning';
                $this->credentialTestMessage = 'Authenticated successfully, but could not retrieve customer list.';

                return;
            }

            $customers  = $customersResponse->json('data');
            $customerId = is_array($customers) ? ($customers[0]['id'] ?? null) : null;

            if ($customerId) {
                $this->imanageCustomerId     = (string) $customerId;
                $this->credentialTestStatus  = 'success';
                $this->credentialTestMessage = "Connected successfully. Customer ID \"{$customerId}\" has been populated.";
            } else {
                $this->credentialTestStatus  = 'warning';
                $this->credentialTestMessage = 'Authenticated successfully, but no customer ID was found in the response.';
            }
        } catch (\Throwable $e) {
            $this->credentialTestStatus  = 'error';
            $this->credentialTestMessage = 'Connection error: '.$e->getMessage();
        }
    }

    public function save(): void
    {
        $rules = [
            'name'              => 'required|string|max:255',
            'slug'              => 'required|string|max:255|unique:tenants',
            'reference'         => 'nullable|uuid|unique:tenants',
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
        ];

        if ($this->createAdmin) {
            $rules['adminName']  = 'required|string|max:255';
            $rules['adminEmail'] = 'required|email|unique:users,email';
        }

        $this->validate($rules);

        // reference is NOT NULL in the DB — auto-generate if not provided
        $reference = $this->reference ?: Str::uuid()->toString();

        $tenant = Tenant::create([
            'name'                             => $this->name,
            'slug'                             => $this->slug,
            'reference'                        => $reference,
            'status'                           => $this->status,
            'clio_location_id'                 => $this->clioLocationId,
            'clio_app_id'                      => $this->clioAppId ?: null,
            'clio_app_secret'                  => $this->clioAppSecret ?: null,
            'imanage_cloud_url'                => $this->imanageCloudUrl ?: null,
            'imanage_customer_id'              => $this->imanageCustomerId ?: null,
            'imanage_app_id'                   => $this->imanageAppId ?: null,
            'imanage_app_secret'               => $this->imanageAppSecret ?: null,
            'imanage_username'                 => $this->imanageUsername ?: null,
            'imanage_password'                 => $this->imanagePassword ?: null,
            'password_authentication'          => $this->passwordAuthentication,
            'has_group_security_mapping'       => $this->hasGroupSecurityMapping,
            'enable_workspace_link_custom_field' => $this->enableWorkspaceLinkCustomField,
        ]);

        // Notify Super Admin + Admin users
        $admins = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Super Admin', 'Admin']))->get();

        foreach ($admins as $admin) {
            $admin->notify(new NewTenantRegistered($tenant));
        }

        // Optionally create an initial Tenant Admin user
        if ($this->createAdmin && $this->adminEmail) {
            $tenantAdmin = User::create([
                'name'              => $this->adminName,
                'email'             => $this->adminEmail,
                'password'          => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
                'tenant_id'         => $tenant->id,
            ]);

            $tenantAdmin->assignRole('Tenant Admin');

            $token    = Password::broker()->createToken($tenantAdmin);
            $resetUrl = route('password.reset', ['token' => $token, 'email' => $tenantAdmin->email]);

            $tenantAdmin->notify(new UserInvited($tenant, $resetUrl));
        }

        session()->flash('success', 'Tenant created successfully.');

        $this->redirect(route('admin.tenants.show', $tenant->id), navigate: true);
    }

    #[Computed]
    public function clioLocations()
    {
        return ClioLocation::orderBy('name')->get();
    }

    public function render(): View
    {
        return view('livewire.admin.tenants.create');
    }
}
