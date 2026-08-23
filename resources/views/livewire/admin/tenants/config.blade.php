<div
    x-data
    x-on:close-modal.window="$flux.modal($event.detail.name).close()"
>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('admin.tenants.index') }}" wire:navigate>Tenants</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.tenants.show', $tenant->id) }}" wire:navigate>{{ $tenant->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Configuration</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">{{ $tenant->name }} — Configuration</flux:heading>

    {{-- ------------------------------------------------------------------ --}}
    {{-- 1. Default Settings                                                 --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Default Matter Settings</flux:heading>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Default Library</flux:label>
                    <flux:select wire:model="libraryId" placeholder="Select library...">
                        <option value="">None</option>
                        @foreach ($this->libraries as $lib)
                            <option value="{{ $lib->id }}">{{ $lib->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="libraryId" />
                </flux:field>

                <flux:field>
                    <flux:label>Default iManage Template</flux:label>
                    <flux:select wire:model="imanageTemplateId" placeholder="Select template...">
                        <option value="">None</option>
                        @foreach ($this->imanageTemplates as $tmpl)
                            <option value="{{ $tmpl->id }}">{{ $tmpl->description }} ({{ $tmpl->imanage_template_id }})</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="imanageTemplateId" />
                </flux:field>

                <flux:field>
                    <flux:label>Replica iManage Template <flux:badge size="sm">Optional</flux:badge></flux:label>
                    <flux:select wire:model="replicaTemplateId" placeholder="Select template...">
                        <option value="">None</option>
                        @foreach ($this->imanageTemplates as $tmpl)
                            <option value="{{ $tmpl->id }}">{{ $tmpl->description }} ({{ $tmpl->imanage_template_id }})</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="replicaTemplateId" />
                </flux:field>

                <flux:field>
                    <flux:label>Workspace Link Custom Field Name <flux:badge size="sm">Optional</flux:badge></flux:label>
                    <flux:input wire:model="workspaceLinkCustomFieldName" placeholder="e.g. iManageLink" />
                    <flux:description>Clio matter custom field populated with the iManage workspace link.</flux:description>
                    <flux:error name="workspaceLinkCustomFieldName" />
                </flux:field>
            </div>

            <div class="flex flex-wrap gap-6 pt-2">
                <flux:checkbox wire:model="defaultHipaa" label="HIPAA Compliant by default" />
                <flux:checkbox wire:model="defaultEnabled" label="Enable new workspaces by default" />
                <flux:checkbox wire:model="hasReplicaWorkspaces" label="Has replica workspaces" />
                <flux:checkbox wire:model="hasWorkspaceLinkCustomField" label="Has workspace link custom field" />
            </div>

            <div class="pt-2">
                <flux:button wire:click="saveSettings" variant="primary">Save Settings</flux:button>
            </div>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- 2. Practice Area Mappings                                           --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Practice Area Mappings</flux:heading>
            <flux:button size="sm" x-on:click="$flux.modal('add-pa-mapping').show()">Add Mapping</flux:button>
        </div>

        <div class="px-4">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Clio Practice Area</flux:table.column>
                    <flux:table.column>iManage Practice Area</flux:table.column>
                    <flux:table.column>Sub-Practice Area</flux:table.column>
                    <flux:table.column>Custom Field Config</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->practiceAreaMappings as $mapping)
                        <flux:table.row>
                            <flux:table.cell>{{ $mapping->clioPracticeArea?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $mapping->imanagePracticeArea?->description ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $mapping->imanageSubPracticeArea?->description ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $mapping->imanageCustomFieldConfig?->custom_field_identifier ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="sm" variant="ghost" wire:click="deletePracticeAreaMapping({{ $mapping->id }})" wire:confirm="Delete this mapping?">Delete</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="py-8 text-center text-zinc-400">No practice area mappings yet.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- 3. Template Mappings                                                --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Template Mappings</flux:heading>
            <flux:button size="sm" x-on:click="$flux.modal('add-template-mapping').show()">Add Mapping</flux:button>
        </div>

        <div class="px-4">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Clio Practice Area</flux:table.column>
                    <flux:table.column>iManage Template</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->templateMappings as $mapping)
                        <flux:table.row>
                            <flux:table.cell>{{ $mapping->clioPracticeArea?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $mapping->imanageTemplate?->description ?? '—' }} ({{ $mapping->imanageTemplate?->imanage_template_id ?? '—' }})</flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="sm" variant="ghost" wire:click="deleteTemplateMapping({{ $mapping->id }})" wire:confirm="Delete this mapping?">Delete</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="py-8 text-center text-zinc-400">No template mappings yet.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- 4. Group Mappings                                                   --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Group Mappings</flux:heading>
            <flux:button size="sm" x-on:click="$flux.modal('add-group-mapping').show()">Add Mapping</flux:button>
        </div>

        <div class="px-4">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Clio Group</flux:table.column>
                    <flux:table.column>iManage Group</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->groupMappings as $mapping)
                        <flux:table.row>
                            <flux:table.cell>{{ $mapping->clioGroup?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $mapping->imanageGroup?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="sm" variant="ghost" wire:click="deleteGroupMapping({{ $mapping->id }})" wire:confirm="Delete this mapping?">Delete</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="py-8 text-center text-zinc-400">No group mappings yet.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- 5. User Mappings                                                    --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">User Mappings</flux:heading>
            <flux:button size="sm" x-on:click="$flux.modal('add-user-mapping').show()">Add Mapping</flux:button>
        </div>

        <div class="px-4">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Clio User</flux:table.column>
                    <flux:table.column>iManage User</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->userMappings as $mapping)
                        <flux:table.row>
                            <flux:table.cell>{{ $mapping->clioUser?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $mapping->imanageUser?->full_name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="sm" variant="ghost" wire:click="deleteUserMapping({{ $mapping->id }})" wire:confirm="Delete this mapping?">Delete</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="py-8 text-center text-zinc-400">No user mappings yet.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- 6. iManage Custom Field Configs                                     --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">iManage Custom Field Configs</flux:heading>
            <flux:button size="sm" x-on:click="$flux.modal('add-custom-field-config').show()">Add Config</flux:button>
        </div>

        <div class="px-4">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Identifier</flux:table.column>
                    <flux:table.column>Description</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->customFieldConfigs as $config)
                        <flux:table.row>
                            <flux:table.cell class="font-mono">{{ $config->custom_field_identifier }}</flux:table.cell>
                            <flux:table.cell>{{ $config->description ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="sm" variant="ghost" wire:click="deleteCustomFieldConfig({{ $config->id }})" wire:confirm="Delete this config?">Delete</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="py-8 text-center text-zinc-400">No custom field configs yet.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- MODALS                                                              --}}
    {{-- ================================================================== --}}

    {{-- Add Practice Area Mapping --}}
    <flux:modal name="add-pa-mapping" class="w-full max-w-lg">
        <flux:heading size="lg">Add Practice Area Mapping</flux:heading>
        <flux:subheading>Map a Clio practice area to an iManage practice area.</flux:subheading>

        <div class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Clio Practice Area</flux:label>
                <flux:select wire:model.live="paClioPracticeAreaId" placeholder="Select...">
                    <option value="">Choose...</option>
                    @foreach ($this->clioPracticeAreas as $pa)
                        <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="paClioPracticeAreaId" />
            </flux:field>

            <flux:field>
                <flux:label>iManage Practice Area</flux:label>
                <flux:select wire:model.live="paImanagePracticeAreaId" placeholder="Select...">
                    <option value="">Choose...</option>
                    @foreach ($this->imanagePracticeAreas as $pa)
                        <option value="{{ $pa->id }}">{{ $pa->description }} ({{ $pa->key }})</option>
                    @endforeach
                </flux:select>
                <flux:error name="paImanagePracticeAreaId" />
            </flux:field>

            <flux:field>
                <flux:label>Sub-Practice Area <flux:badge size="sm">Optional</flux:badge></flux:label>
                <flux:select wire:model="paImanageSubPracticeAreaId" placeholder="Select...">
                    <option value="">None</option>
                    @foreach ($this->subPracticeAreas as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->description }} ({{ $sub->key }})</option>
                    @endforeach
                </flux:select>
                <flux:description>Populated after selecting an iManage practice area above.</flux:description>
                <flux:error name="paImanageSubPracticeAreaId" />
            </flux:field>

            <flux:field>
                <flux:label>Custom Field Config <flux:badge size="sm">Optional</flux:badge></flux:label>
                <flux:select wire:model="paCustomFieldConfigId" placeholder="Select...">
                    <option value="">None</option>
                    @foreach ($this->customFieldConfigs as $cfg)
                        <option value="{{ $cfg->id }}">{{ $cfg->custom_field_identifier }}{{ $cfg->description ? ' — ' . $cfg->description : '' }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="paCustomFieldConfigId" />
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" wire:click="addPracticeAreaMapping">Add Mapping</flux:button>
        </div>
    </flux:modal>

    {{-- Add Template Mapping --}}
    <flux:modal name="add-template-mapping" class="w-full max-w-lg">
        <flux:heading size="lg">Add Template Mapping</flux:heading>
        <flux:subheading>Map a Clio practice area to an iManage workspace template.</flux:subheading>

        <div class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Clio Practice Area</flux:label>
                <flux:select wire:model="tmClioPracticeAreaId" placeholder="Select...">
                    <option value="">Choose...</option>
                    @foreach ($this->clioPracticeAreas as $pa)
                        <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="tmClioPracticeAreaId" />
            </flux:field>

            <flux:field>
                <flux:label>iManage Template</flux:label>
                <flux:select wire:model="tmImanageTemplateId" placeholder="Select...">
                    <option value="">Choose...</option>
                    @foreach ($this->imanageTemplates as $tmpl)
                        <option value="{{ $tmpl->id }}">{{ $tmpl->description }} ({{ $tmpl->imanage_template_id }})</option>
                    @endforeach
                </flux:select>
                <flux:error name="tmImanageTemplateId" />
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" wire:click="addTemplateMapping">Add Mapping</flux:button>
        </div>
    </flux:modal>

    {{-- Add Group Mapping --}}
    <flux:modal name="add-group-mapping" class="w-full max-w-lg">
        <flux:heading size="lg">Add Group Mapping</flux:heading>
        <flux:subheading>Map a Clio group to an iManage group.</flux:subheading>

        <div class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Clio Group</flux:label>
                <flux:select wire:model="gmClioGroupId" placeholder="Select...">
                    <option value="">Choose...</option>
                    @foreach ($this->clioGroups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="gmClioGroupId" />
            </flux:field>

            <flux:field>
                <flux:label>iManage Group</flux:label>
                <flux:select wire:model="gmImanageGroupId" placeholder="Select...">
                    <option value="">Choose...</option>
                    @foreach ($this->imanageGroups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="gmImanageGroupId" />
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" wire:click="addGroupMapping">Add Mapping</flux:button>
        </div>
    </flux:modal>

    {{-- Add User Mapping --}}
    <flux:modal name="add-user-mapping" class="w-full max-w-lg">
        <flux:heading size="lg">Add User Mapping</flux:heading>
        <flux:subheading>Map a Clio user to their iManage counterpart.</flux:subheading>

        <div class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Clio User</flux:label>
                <flux:select wire:model="umClioUserId" placeholder="Select...">
                    <option value="">Choose...</option>
                    @foreach ($this->clioUsers as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}{{ $user->email ? ' — ' . $user->email : '' }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="umClioUserId" />
            </flux:field>

            <flux:field>
                <flux:label>iManage User</flux:label>
                <flux:select wire:model="umImanageUserId" placeholder="Select...">
                    <option value="">Choose...</option>
                    @foreach ($this->imanageUsers as $user)
                        <option value="{{ $user->id }}">{{ $user->full_name }}{{ $user->email ? ' — ' . $user->email : '' }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="umImanageUserId" />
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" wire:click="addUserMapping">Add Mapping</flux:button>
        </div>
    </flux:modal>

    {{-- Add Custom Field Config --}}
    <flux:modal name="add-custom-field-config" class="w-full max-w-md">
        <flux:heading size="lg">Add Custom Field Config</flux:heading>
        <flux:subheading>Define an iManage custom field identifier used during workspace creation.</flux:subheading>

        <div class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Field Identifier</flux:label>
                <flux:input wire:model="cfIdentifier" placeholder="e.g. custom3" />
                <flux:description>The iManage custom field key (custom3 – custom28).</flux:description>
                <flux:error name="cfIdentifier" />
            </flux:field>

            <flux:field>
                <flux:label>Description <flux:badge size="sm">Optional</flux:badge></flux:label>
                <flux:input wire:model="cfDescription" placeholder="e.g. Client Number" />
                <flux:error name="cfDescription" />
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" wire:click="addCustomFieldConfig">Add Config</flux:button>
        </div>
    </flux:modal>
</div>
