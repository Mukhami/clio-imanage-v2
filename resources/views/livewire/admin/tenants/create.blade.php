<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('admin.tenants.index') }}" wire:navigate>Tenants</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>New Tenant</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Register New Tenant</flux:heading>

    <form wire:submit="save">
        {{-- Basic Information --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Basic Information</flux:heading>
            </div>
            <div class="p-6 space-y-4">
                <flux:field>
                    <flux:label>Name</flux:label>
                    <flux:input wire:model.live="name" placeholder="Firm name" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Slug</flux:label>
                    <flux:input wire:model="slug" placeholder="firm-name" />
                    <flux:description>Auto-generated from name. Can be overridden.</flux:description>
                    <flux:error name="slug" />
                </flux:field>

                <flux:field>
                    <flux:label>Reference <flux:badge size="sm">Optional</flux:badge></flux:label>
                    <flux:input wire:model="reference" placeholder="Leave blank to auto-generate" />
                    <flux:description>UUID used in the webhook URL. Auto-generated if left blank.</flux:description>
                    <flux:error name="reference" />
                </flux:field>

                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model="status">
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="suspended">Suspended</option>
                        <option value="archived">Archived</option>
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>

                <flux:field>
                    <flux:label>Clio Location</flux:label>
                    <flux:select wire:model="clioLocationId">
                        <option value="">Choose a location...</option>
                        @foreach ($this->clioLocations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }} ({{ $location->region->value ?? $location->region }})</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="clioLocationId" />
                </flux:field>
            </div>
        </div>

        {{-- Clio Credentials --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Clio Credentials</flux:heading>
            </div>
            <div class="p-6 space-y-4">
                <flux:text class="text-xs text-zinc-500">These are stored encrypted.</flux:text>

                <flux:field>
                    <flux:label>Clio App ID</flux:label>
                    <flux:input wire:model="clioAppId" type="password" placeholder="••••••••" />
                    <flux:error name="clioAppId" />
                </flux:field>

                <flux:field>
                    <flux:label>Clio App Secret</flux:label>
                    <flux:input wire:model="clioAppSecret" type="password" placeholder="••••••••" />
                    <flux:error name="clioAppSecret" />
                </flux:field>
            </div>
        </div>

        {{-- iManage Credentials --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">iManage Credentials</flux:heading>
            </div>
            <div class="p-6 space-y-4">
                <flux:field>
                    <flux:label>iManage Cloud URL</flux:label>
                    <flux:input wire:model="imanageCloudUrl" placeholder="https://..." />
                    <flux:error name="imanageCloudUrl" />
                </flux:field>

                <flux:field>
                    <flux:label>iManage Customer ID</flux:label>
                    <flux:input wire:model="imanageCustomerId" placeholder="Customer ID" />
                    <flux:error name="imanageCustomerId" />
                </flux:field>

                <flux:field>
                    <flux:label>iManage App ID</flux:label>
                    <flux:input wire:model="imanageAppId" type="password" placeholder="••••••••" />
                    <flux:error name="imanageAppId" />
                </flux:field>

                <flux:field>
                    <flux:label>iManage App Secret</flux:label>
                    <flux:input wire:model="imanageAppSecret" type="password" placeholder="••••••••" />
                    <flux:error name="imanageAppSecret" />
                </flux:field>

                <flux:field>
                    <flux:label>iManage Username</flux:label>
                    <flux:input wire:model="imanageUsername" type="password" placeholder="••••••••" />
                    <flux:error name="imanageUsername" />
                </flux:field>

                <flux:field>
                    <flux:label>iManage Password</flux:label>
                    <flux:input wire:model="imanagePassword" type="password" placeholder="••••••••" />
                    <flux:error name="imanagePassword" />
                </flux:field>

                <flux:checkbox wire:model="passwordAuthentication" label="Password Authentication" />
            </div>
        </div>

        {{-- Feature Flags --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Feature Flags</flux:heading>
            </div>
            <div class="p-6 space-y-4">
                <flux:checkbox wire:model="hasGroupSecurityMapping" label="Group Security Mapping" />
                <flux:checkbox wire:model="enableWorkspaceLinkCustomField" label="Enable Workspace Link Custom Field" />
            </div>
        </div>

        {{-- Initial Tenant Admin --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Initial Tenant Admin</flux:heading>
                <flux:badge size="sm" color="zinc">Optional</flux:badge>
            </div>
            <div class="p-6 space-y-4">
                <flux:checkbox wire:model.live="createAdmin" label="Create an initial Tenant Admin user for this tenant" />

                @if ($createAdmin)
                    <flux:callout variant="info" icon="information-circle">
                        An invitation email will be sent to this user with a link to set their password.
                    </flux:callout>

                    <flux:field>
                        <flux:label>Admin Name</flux:label>
                        <flux:input wire:model="adminName" placeholder="Full name" />
                        <flux:error name="adminName" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Admin Email</flux:label>
                        <flux:input wire:model="adminEmail" type="email" placeholder="admin@example.com" />
                        <flux:error name="adminEmail" />
                    </flux:field>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">Register Tenant</flux:button>
            <flux:button variant="ghost" href="{{ route('admin.tenants.index') }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>
