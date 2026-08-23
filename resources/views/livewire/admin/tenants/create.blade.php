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
            <div class="p-6">
                <div class="grid grid-cols-3 gap-x-6 gap-y-5 items-start">
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input wire:model.live="name" placeholder="Firm name" />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model="slug" placeholder="firm-name" readonly class="bg-zinc-50 dark:bg-zinc-800 cursor-not-allowed" />
                        <flux:description>Auto-generated from the name.</flux:description>
                        <flux:error name="slug" />
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
        </div>

        {{-- Clio Credentials --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Clio Credentials</flux:heading>
            </div>
            <div class="p-6">
                <flux:callout variant="info" icon="information-circle" class="mb-5 text-xs">
                    Defaulted from the system <code>CLIO_APP_KEY</code> / <code>CLIO_APP_SECRET</code> environment variables. Override below if this tenant uses different credentials.
                </flux:callout>
                <div class="grid grid-cols-3 gap-x-6 gap-y-5 items-start">
                    <flux:field>
                        <flux:label>Clio App ID</flux:label>
                        <flux:input wire:model="clioAppId" type="password" viewable placeholder="••••••••" />
                        <flux:error name="clioAppId" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Clio App Secret</flux:label>
                        <flux:input wire:model="clioAppSecret" type="password" viewable placeholder="••••••••" />
                        <flux:error name="clioAppSecret" />
                    </flux:field>
                </div>
            </div>
        </div>

        {{-- iManage Credentials --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">iManage Credentials</flux:heading>
            </div>
            <div class="p-6">
                <flux:text class="text-xs text-zinc-500 mb-5">
                    Cloud URL, App ID, and App Secret default from <code>IMANAGE_API_URL</code>, <code>IMANAGE_APP_KEY</code>, and <code>IMANAGE_APP_SECRET</code> respectively.
                </flux:text>
                <div class="grid grid-cols-3 gap-x-6 gap-y-5 items-start">
                    <flux:field class="col-span-3">
                        <flux:label>iManage Cloud URL</flux:label>
                        <flux:input wire:model.live="imanageCloudUrl" placeholder="https://cloudimanage.com" />
                        @if ($imanageCloudUrl !== config('services.imanage.api_url'))
                            <flux:callout variant="warning" icon="exclamation-triangle" class="mt-2 text-xs">
                                This differs from the system default (<code>{{ config('services.imanage.api_url') }}</code>). Only change this if the tenant uses a different iManage instance.
                            </flux:callout>
                        @endif
                        <flux:error name="imanageCloudUrl" />
                    </flux:field>

                    <flux:field>
                        <flux:label>iManage App ID</flux:label>
                        <flux:input wire:model="imanageAppId" type="password" viewable placeholder="••••••••" />
                        <flux:error name="imanageAppId" />
                    </flux:field>

                    <flux:field>
                        <flux:label>iManage App Secret</flux:label>
                        <flux:input wire:model="imanageAppSecret" type="password" viewable placeholder="••••••••" />
                        <flux:error name="imanageAppSecret" />
                    </flux:field>

                    <flux:field>
                        <flux:label>iManage Username</flux:label>
                        <flux:input wire:model="imanageUsername" placeholder="service-account@firm.com" />
                        <flux:error name="imanageUsername" />
                    </flux:field>

                    <flux:field>
                        <flux:label>iManage Password</flux:label>
                        <flux:input wire:model="imanagePassword" type="password" viewable placeholder="••••••••" />
                        <flux:error name="imanagePassword" />
                    </flux:field>

                    <div class="pt-6">
                        <flux:checkbox wire:model="passwordAuthentication" label="Password Authentication" />
                    </div>

                    {{-- Test credentials --}}
                    <div class="col-span-3 pt-2 border-t border-zinc-100 dark:border-zinc-700">
                        <div class="flex items-center gap-3">
                            <flux:button
                                type="button"
                                variant="ghost"
                                size="sm"
                                wire:click="testImanageCredentials"
                                wire:loading.attr="disabled"
                                wire:target="testImanageCredentials"
                            >
                                <span wire:loading.remove wire:target="testImanageCredentials">Test iManage Credentials</span>
                                <span wire:loading wire:target="testImanageCredentials">Testing...</span>
                            </flux:button>
                            @if ($credentialTestStatus)
                                <flux:text class="text-xs {{ $credentialTestStatus === 'success' ? 'text-green-600 dark:text-green-400' : ($credentialTestStatus === 'error' ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400') }}">
                                    {{ $credentialTestMessage }}
                                </flux:text>
                            @endif
                        </div>
                    </div>

                    <flux:field>
                        <flux:label>iManage Customer ID</flux:label>
                        <flux:input wire:model="imanageCustomerId" placeholder="Auto-populated on test" />
                        <flux:description>Fetched automatically when you test credentials.</flux:description>
                        <flux:error name="imanageCustomerId" />
                    </flux:field>
                </div>
            </div>
        </div>

        {{-- Feature Flags --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Feature Flags</flux:heading>
            </div>
            <div class="p-6 flex flex-wrap gap-6">
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
            <div class="p-6">
                <flux:checkbox wire:model.live="createAdmin" label="Create an initial Tenant Admin user for this tenant" />

                @if ($createAdmin)
                    <div class="mt-5">
                        <flux:callout variant="info" icon="information-circle" class="mb-5">
                            An invitation email will be sent to this user with a link to set their password.
                        </flux:callout>

                        <div class="grid grid-cols-3 gap-x-6 gap-y-5 items-start">
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
                        </div>
                    </div>
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
