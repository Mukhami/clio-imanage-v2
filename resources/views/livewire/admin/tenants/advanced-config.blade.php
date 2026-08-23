<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('admin.tenants.index') }}" wire:navigate>Tenants</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.tenants.show', $tenant->id) }}" wire:navigate>{{ $tenant->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Advanced Config</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">{{ $tenant->name }} — Advanced Configuration</flux:heading>

    {{-- ------------------------------------------------------------------ --}}
    {{-- 1. Display Number Parsing                                           --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Display Number Parsing</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-1">Determines how client ID and matter ID are extracted from a Clio matter's display number field.</flux:text>
        </div>
        <div class="p-6 space-y-4">
            @if (session('parsing_saved'))
                <flux:callout variant="success" icon="check-circle">{{ session('parsing_saved') }}</flux:callout>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>Parsing Strategy</flux:label>
                    <flux:select wire:model.live="parsingStrategy">
                        <option value="split_delimiter">Split by Delimiter — e.g. "C001/M001" → split on "/"</option>
                        <option value="split_delimiter_nested">Split by Nested Delimiters</option>
                        <option value="regex">Regular Expression</option>
                        <option value="bracket_extraction">Bracket Extraction — e.g. "[C001][M001]"</option>
                        <option value="clio_ids">Use Clio Native IDs (client_id / matter_id)</option>
                        <option value="custom_field_extraction">Custom Field Extraction</option>
                        <option value="display_number_as_matter">Full Display Number as Matter ID</option>
                        <option value="display_number_as_client">Full Display Number as Client ID</option>
                        <option value="sequence_auto">Auto-generate Sequential IDs</option>
                        <option value="legacy_alias_lookup">Legacy Alias Lookup</option>
                        <option value="custom">Custom</option>
                    </flux:select>
                    <flux:error name="parsingStrategy" />
                </flux:field>

                @if ($this->showDelimiterFields)
                    <flux:field>
                        <flux:label>Delimiter</flux:label>
                        <flux:input wire:model="parsingDelimiter" placeholder="e.g. /" />
                        <flux:description>Character(s) to split the display number on.</flux:description>
                        <flux:error name="parsingDelimiter" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Secondary Delimiter <flux:badge size="sm">Optional</flux:badge></flux:label>
                        <flux:input wire:model="parsingSecondaryDelimiter" placeholder="e.g. -" />
                        <flux:description>Used for nested split strategies.</flux:description>
                        <flux:error name="parsingSecondaryDelimiter" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Client ID Position</flux:label>
                        <flux:input wire:model="parsingClientPosition" type="number" min="0" placeholder="0" />
                        <flux:description>Zero-based index of the client ID segment.</flux:description>
                        <flux:error name="parsingClientPosition" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Matter ID Position</flux:label>
                        <flux:input wire:model="parsingMatterPosition" type="number" min="0" placeholder="1" />
                        <flux:description>Zero-based index of the matter ID segment.</flux:description>
                        <flux:error name="parsingMatterPosition" />
                    </flux:field>
                @endif

                @if ($this->showRegexFields)
                    <flux:field class="sm:col-span-2">
                        <flux:label>Regex Pattern</flux:label>
                        <flux:input wire:model="parsingRegexPattern" placeholder="e.g. ^(?P<client>[A-Z0-9]+)\/(?P<matter>[A-Z0-9]+)$" class="font-mono" />
                        <flux:error name="parsingRegexPattern" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Client Capture Group</flux:label>
                        <flux:input wire:model="parsingClientCaptureGroup" placeholder="client" />
                        <flux:description>Named capture group for the client ID.</flux:description>
                        <flux:error name="parsingClientCaptureGroup" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Matter Capture Group</flux:label>
                        <flux:input wire:model="parsingMatterCaptureGroup" placeholder="matter" />
                        <flux:description>Named capture group for the matter ID.</flux:description>
                        <flux:error name="parsingMatterCaptureGroup" />
                    </flux:field>
                @endif

                @if ($this->showCustomFieldName)
                    <flux:field class="sm:col-span-2">
                        <flux:label>Custom Field Name</flux:label>
                        <flux:input wire:model="parsingCustomFieldName" placeholder="e.g. client_reference" />
                        <flux:description>The Clio custom field name to read the ID from.</flux:description>
                        <flux:error name="parsingCustomFieldName" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>Validation Regex <flux:badge size="sm">Optional</flux:badge></flux:label>
                    <flux:input wire:model="parsingValidationRegex" placeholder="e.g. ^\d{5}$" class="font-mono" />
                    <flux:description>Validate extracted IDs against this pattern before using them.</flux:description>
                    <flux:error name="parsingValidationRegex" />
                </flux:field>

                <flux:field>
                    <flux:label>Fallback Strategy <flux:badge size="sm">Optional</flux:badge></flux:label>
                    <flux:input wire:model="parsingFallbackStrategy" placeholder="e.g. clio_ids" />
                    <flux:description>Strategy to use when the primary strategy fails to parse.</flux:description>
                    <flux:error name="parsingFallbackStrategy" />
                </flux:field>
            </div>

            <flux:checkbox wire:model="parsingEnabled" label="Enabled" />

            <flux:button wire:click="saveParsingConfig" variant="primary">Save Parsing Config</flux:button>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- 2. Workspace Naming                                                 --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Workspace Naming</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-1">Template for constructing iManage workspace names from matter data.</flux:text>
        </div>
        <div class="p-6 space-y-4">
            @if (session('naming_saved'))
                <flux:callout variant="success" icon="check-circle">{{ session('naming_saved') }}</flux:callout>
            @endif

            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-4 py-3">
                <flux:text class="text-xs font-medium text-zinc-500 mb-1">Available tokens</flux:text>
                <div class="flex flex-wrap gap-2 font-mono text-xs text-zinc-700 dark:text-zinc-300">
                    @foreach (['{client_id}', '{matter_id}', '{client_name}', '{matter_description}', '{display_number}', '{year}', '{practice_area}'] as $token)
                        <span class="rounded bg-zinc-200 dark:bg-zinc-700 px-1.5 py-0.5">{{ $token }}</span>
                    @endforeach
                </div>
            </div>

            <flux:field>
                <flux:label>Template Pattern</flux:label>
                <flux:input wire:model="workspaceNamingTemplate" placeholder="e.g. {client_id} - {client_name} - {matter_description}" />
                <flux:error name="workspaceNamingTemplate" />
            </flux:field>

            <flux:field>
                <flux:label>Description <flux:badge size="sm">Optional</flux:badge></flux:label>
                <flux:input wire:model="workspaceNamingDescription" placeholder="e.g. Standard matter workspace format" />
                <flux:error name="workspaceNamingDescription" />
            </flux:field>

            <flux:button wire:click="saveWorkspaceNaming" variant="primary">Save Workspace Naming</flux:button>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- 3. Client Name Transformation                                       --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Client Name Transformation</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-1">Optionally reformat Clio client names before using them in iManage.</flux:text>
        </div>
        <div class="p-6 space-y-4">
            @if (session('client_name_saved'))
                <flux:callout variant="success" icon="check-circle">{{ session('client_name_saved') }}</flux:callout>
            @endif

            <flux:field>
                <flux:label>Strategy</flux:label>
                <flux:select wire:model.live="clientNameStrategy">
                    <option value="none">None — use name as-is</option>
                    <option value="last_name_first">Last Name First — "Smith, John"</option>
                    <option value="reverse_words">Reverse Words</option>
                    <option value="custom_template">Custom Template</option>
                </flux:select>
                <flux:error name="clientNameStrategy" />
            </flux:field>

            @if ($this->showClientNameTemplate)
                <flux:field>
                    <flux:label>Template Pattern</flux:label>
                    <flux:input wire:model="clientNameTemplate" placeholder="e.g. {last_name}, {first_name}" />
                    <flux:description>Available tokens: {first_name}, {last_name}, {full_name}</flux:description>
                    <flux:error name="clientNameTemplate" />
                </flux:field>
            @endif

            <div class="flex flex-wrap gap-6">
                <flux:checkbox wire:model="clientNameApplyToPersonsOnly" label="Apply to person clients only" />
                <flux:checkbox wire:model="clientNameEnabled" label="Enabled" />
            </div>

            <flux:button wire:click="saveClientNameConfig" variant="primary">Save Client Name Config</flux:button>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- 4. Matter Description Transformation                               --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Matter Description Transformation</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-1">Optionally reformat the iManage workspace description field derived from Clio matter data.</flux:text>
        </div>
        <div class="p-6 space-y-4">
            @if (session('matter_desc_saved'))
                <flux:callout variant="success" icon="check-circle">{{ session('matter_desc_saved') }}</flux:callout>
            @endif

            <flux:field>
                <flux:label>Strategy</flux:label>
                <flux:select wire:model.live="matterDescStrategy">
                    <option value="none">None — use description as-is</option>
                    <option value="use_display_number">Use Display Number as Description</option>
                    <option value="use_client_description">Use Client Description</option>
                    <option value="composite_template">Composite Template</option>
                    <option value="strip_prefix">Strip Prefix from Description</option>
                </flux:select>
                <flux:error name="matterDescStrategy" />
            </flux:field>

            @if ($this->showMatterDescSourceField)
                <flux:field>
                    <flux:label>Source Field <flux:badge size="sm">Optional</flux:badge></flux:label>
                    <flux:input wire:model="matterDescSourceField" placeholder="e.g. display_number" />
                    <flux:description>Clio matter field to read from (e.g. display_number, description).</flux:description>
                    <flux:error name="matterDescSourceField" />
                </flux:field>
            @endif

            @if ($this->showMatterDescTemplate)
                <flux:field>
                    <flux:label>Template Pattern</flux:label>
                    <flux:input wire:model="matterDescTemplate" placeholder="e.g. {client_name} — {display_number}" />
                    <flux:description>Available tokens: {client_name}, {display_number}, {matter_description}, {practice_area}</flux:description>
                    <flux:error name="matterDescTemplate" />
                </flux:field>
            @endif

            <flux:checkbox wire:model="matterDescEnabled" label="Enabled" />

            <flux:button wire:click="saveMatterDescConfig" variant="primary">Save Matter Description Config</flux:button>
        </div>
    </div>
</div>
