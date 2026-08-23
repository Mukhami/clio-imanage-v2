<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('admin.tenants.index') }}" wire:navigate>Tenants</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.tenants.show', $tenant->id) }}" wire:navigate>{{ $tenant->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Sequence Config</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">{{ $tenant->name }} — Sequence Configuration</flux:heading>

    <form wire:submit="save">
        {{-- Client Sequence --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Client Sequence</flux:heading>
                <div class="flex items-center gap-2">
                    <flux:text class="text-xs text-zinc-500">Preview:</flux:text>
                    <span class="font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ $this->clientPreview }}</span>
                </div>
            </div>
            <div class="p-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Prefix <flux:badge size="sm">Optional</flux:badge></flux:label>
                    <flux:input wire:model.live="clientPrefix" placeholder="e.g. C" />
                    <flux:description>Prepended before the number. A dash is added automatically.</flux:description>
                    <flux:error name="clientPrefix" />
                </flux:field>

                <flux:field>
                    <flux:label>Start Number</flux:label>
                    <flux:input wire:model.live="clientStartNumber" type="number" min="1" placeholder="1" />
                    <flux:description>The first number in the sequence.</flux:description>
                    <flux:error name="clientStartNumber" />
                </flux:field>

                <flux:field>
                    <flux:label>Digit Padding</flux:label>
                    <flux:input wire:model.live="clientDigits" type="number" min="1" max="20" placeholder="5" />
                    <flux:description>Zero-pad numbers to this width (e.g. 5 → "00001").</flux:description>
                    <flux:error name="clientDigits" />
                </flux:field>

                <flux:field>
                    <flux:label>Clio Custom Field Name <flux:badge size="sm">Optional</flux:badge></flux:label>
                    <flux:input wire:model="clientCustomFieldName" placeholder="e.g. client_number" />
                    <flux:description>Clio contact custom field to populate with the generated ID.</flux:description>
                    <flux:error name="clientCustomFieldName" />
                </flux:field>
            </div>
        </div>

        {{-- Matter Sequence --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Matter Sequence</flux:heading>
                <div class="flex items-center gap-2">
                    <flux:text class="text-xs text-zinc-500">Preview:</flux:text>
                    <span class="font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ $this->matterPreview }}</span>
                </div>
            </div>
            <div class="p-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Prefix <flux:badge size="sm">Optional</flux:badge></flux:label>
                    <flux:input wire:model.live="matterPrefix" placeholder="e.g. M" />
                    <flux:description>Prepended before the number. A dash is added automatically.</flux:description>
                    <flux:error name="matterPrefix" />
                </flux:field>

                <flux:field>
                    <flux:label>Start Number</flux:label>
                    <flux:input wire:model.live="matterStartNumber" type="number" min="1" placeholder="1" />
                    <flux:description>The first number in the sequence.</flux:description>
                    <flux:error name="matterStartNumber" />
                </flux:field>

                <flux:field>
                    <flux:label>Digit Padding</flux:label>
                    <flux:input wire:model.live="matterDigits" type="number" min="1" max="20" placeholder="5" />
                    <flux:description>Zero-pad numbers to this width (e.g. 5 → "00001").</flux:description>
                    <flux:error name="matterDigits" />
                </flux:field>

                <flux:field>
                    <flux:label>Clio Custom Field Name <flux:badge size="sm">Optional</flux:badge></flux:label>
                    <flux:input wire:model="matterCustomFieldName" placeholder="e.g. matter_number" />
                    <flux:description>Clio matter custom field to populate with the generated ID.</flux:description>
                    <flux:error name="matterCustomFieldName" />
                </flux:field>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary">Save Configuration</flux:button>
            @if ($tenant->tenantSequenceConfig)
                <flux:button
                    type="button"
                    variant="danger"
                    wire:click="deleteConfig"
                    wire:confirm="Delete this sequence configuration? This cannot be undone."
                >
                    Delete Configuration
                </flux:button>
            @endif
            <flux:button variant="ghost" href="{{ route('admin.tenants.show', $tenant->id) }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>
