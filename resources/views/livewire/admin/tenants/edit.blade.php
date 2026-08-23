<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('admin.tenants.index') }}" wire:navigate>Tenants</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.tenants.show', $tenant->id) }}" wire:navigate>{{ $tenant->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Edit {{ $tenant->name }}</flux:heading>

    @if (session('success'))
        <flux:callout variant="success" class="mb-4">{{ session('success') }}</flux:callout>
    @endif

    <form wire:submit="save">
        {{-- Basic Information --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Basic Information</flux:heading>
            </div>
            <div class="p-6 space-y-4">
                <flux:field>
                    <flux:label>Name</flux:label>
                    <flux:input wire:model="name" placeholder="Firm name" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Slug</flux:label>
                    <flux:input wire:model="slug" placeholder="firm-name" />
                    <flux:description>Auto-generated from name, can be overridden</flux:description>
                    <flux:error name="slug" />
                </flux:field>

                <flux:field>
                    <flux:label>Reference</flux:label>
                    <flux:input wire:model="reference" placeholder="{{ $tenant->reference }}" />
                    <flux:description>UUID used in the webhook URL. Leave blank to keep the current value.</flux:description>
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
                <flux:text class="text-xs text-zinc-500">Stored encrypted. Leave blank to keep the current value.</flux:text>

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

        {{-- iManage Credentials --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">iManage Credentials</flux:heading>
            </div>
            <div class="p-6 space-y-4">
                <flux:field>
                    <flux:label>iManage Cloud URL</flux:label>
                    <flux:input wire:model="imanageCloudUrl" placeholder="https://cloudimanage.com" />
                    <flux:error name="imanageCloudUrl" />
                </flux:field>

                <flux:field>
                    <flux:label>iManage App ID</flux:label>
                    <flux:input wire:model="imanageAppId" type="password" viewable placeholder="••••••••" />
                    <flux:description>Leave blank to keep current value</flux:description>
                    <flux:error name="imanageAppId" />
                </flux:field>

                <flux:field>
                    <flux:label>iManage App Secret</flux:label>
                    <flux:input wire:model="imanageAppSecret" type="password" viewable placeholder="••••••••" />
                    <flux:description>Leave blank to keep current value</flux:description>
                    <flux:error name="imanageAppSecret" />
                </flux:field>

                <flux:field>
                    <flux:label>iManage Username</flux:label>
                    <flux:input wire:model="imanageUsername" placeholder="service-account@firm.com" />
                    <flux:description>Leave blank to keep current value</flux:description>
                    <flux:error name="imanageUsername" />
                </flux:field>

                <flux:field>
                    <flux:label>iManage Password</flux:label>
                    <flux:input wire:model="imanagePassword" type="password" viewable placeholder="••••••••" />
                    <flux:description>Leave blank to keep current value</flux:description>
                    <flux:error name="imanagePassword" />
                </flux:field>

                <flux:checkbox wire:model="passwordAuthentication" label="Password Authentication" />

                {{-- Test credentials --}}
                <div class="pt-2 border-t border-zinc-100 dark:border-zinc-700">
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
                    <flux:input wire:model="imanageCustomerId" placeholder="Auto-populated on test, or enter manually" />
                    <flux:description>Fetched automatically when you test credentials above.</flux:description>
                    <flux:error name="imanageCustomerId" />
                </flux:field>
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

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">Save Changes</flux:button>
            <flux:button variant="ghost" href="{{ route('admin.tenants.show', $tenant->id) }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>
