<div class="space-y-4">
    <section class="content-panel px-5 py-4">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <p class="section-kicker">Administration</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-100">Module builder</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Create metadata-driven CRUD modules with generated tables, permissions, validation, audit logs, relationships, and conditional forms.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg border border-emerald-800/70 bg-emerald-950/30 px-3 py-2 text-sm font-medium text-emerald-300">
                {{ session('status') }}
            </div>
        @endif
    </section>

    <section class="grid gap-4 2xl:grid-cols-[1.15fr_0.85fr]">
        <form wire:submit="generate" class="table-card px-5 py-4">
            <div class="grid gap-4 xl:grid-cols-2">
                <div>
                    <label class="label">Module name</label>
                    <input type="text" wire:model.live="name" class="input" placeholder="Employees">
                    @error('name') <p class="mt-2 text-sm text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Table name</label>
                    <input type="text" wire:model="tableName" class="input" placeholder="employees">
                    @error('tableName') <p class="mt-2 text-sm text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Icon</label>
                    <select wire:model="icon" class="select">
                        <option value="square-stack">Square stack</option>
                        <option value="users">Users</option>
                        <option value="briefcase">Briefcase</option>
                        <option value="clipboard-list">Clipboard list</option>
                        <option value="database">Database</option>
                    </select>
                    @error('icon') <p class="mt-2 text-sm text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Description</label>
                    <input type="text" wire:model="description" class="input" placeholder="Employee directory and HR records">
                    @error('description') <p class="mt-2 text-sm text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                    <input type="checkbox" wire:model="softDeletes" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                    <span>Enable soft deletes</span>
                </label>
                <label class="flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                    <input type="checkbox" wire:model="hasTimestamps" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                    <span>Enable timestamps</span>
                </label>
            </div>

            <div class="mt-5 flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-100">Fields</h3>
                    <p class="mt-1 text-sm text-slate-500">Use named fields for conditional rules and relationship columns.</p>
                </div>
                <button type="button" wire:click="addField" class="btn-secondary px-3 py-1.5">Add field</button>
            </div>

            <div class="mt-4 space-y-3">
                @foreach ($fields as $index => $field)
                    @php
                        $relatedModule = $availableModules->firstWhere('id', (int) ($field['relationship_module_id'] ?? 0));
                    @endphp
                    <div wire:key="field-{{ $index }}" class="rounded-xl border border-white/[0.06] bg-white/[0.025] p-4">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start">
                            <div class="grid flex-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <label class="label">Label</label>
                                    <input type="text" wire:model="fields.{{ $index }}.label" class="input" placeholder="First Name">
                                    @error("fields.$index.label") <p class="mt-2 text-sm text-rose-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label">Name</label>
                                    <input type="text" wire:model.live="fields.{{ $index }}.name" class="input" placeholder="first_name">
                                    @error("fields.$index.name") <p class="mt-2 text-sm text-rose-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label">Type</label>
                                    <select wire:model.live="fields.{{ $index }}.type" class="select">
                                        @foreach ($fieldTypes as $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="label">Placeholder</label>
                                    <input type="text" wire:model="fields.{{ $index }}.placeholder" class="input" placeholder="Enter value">
                                </div>
                            </div>

                            <div class="flex gap-2 xl:pt-6">
                                <button type="button" wire:click="moveFieldUp({{ $index }})" class="btn-secondary px-3 py-1.5">Up</button>
                                <button type="button" wire:click="moveFieldDown({{ $index }})" class="btn-secondary px-3 py-1.5">Down</button>
                                <button type="button" wire:click="removeField({{ $index }})" class="btn-danger px-3 py-1.5">Remove</button>
                            </div>
                        </div>

                        @if (($field['type'] ?? 'text') === 'relationship')
                            <div class="mt-4 grid gap-4 md:grid-cols-3">
                                <div>
                                    <label class="label">Relationship type</label>
                                    <select wire:model="fields.{{ $index }}.relationship_type" class="select">
                                        <option value="belongs_to">Belongs to</option>
                                        <option value="has_many">Has many</option>
                                        <option value="belongs_to_many">Belongs to many</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="label">Related module</label>
                                    <select wire:model.live="fields.{{ $index }}.relationship_module_id" class="select">
                                        <option value="">Choose module</option>
                                        @foreach ($availableModules as $availableModule)
                                            <option value="{{ $availableModule->id }}">{{ $availableModule->name }}</option>
                                        @endforeach
                                    </select>
                                    @error("fields.$index.relationship_module_id") <p class="mt-2 text-sm text-rose-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label">Display field</label>
                                    <select wire:model="fields.{{ $index }}.relationship_display_field" class="select">
                                        <option value="">Choose field</option>
                                        @foreach (($relatedModule?->fields ?? collect()) as $relatedField)
                                            @if (in_array($relatedField->type, ['text', 'textarea', 'email', 'select', 'radio', 'number'], true))
                                                <option value="{{ $relatedField->name }}">{{ $relatedField->label }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error("fields.$index.relationship_display_field") <p class="mt-2 text-sm text-rose-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @endif

                        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <label class="flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                                <input type="checkbox" wire:model="fields.{{ $index }}.required" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                <span>Required</span>
                            </label>
                            <label class="flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                                <input type="checkbox" wire:model="fields.{{ $index }}.unique" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                <span>Unique</span>
                            </label>
                            <div>
                                <label class="label">Default value</label>
                                <input type="text" wire:model="fields.{{ $index }}.default_value" class="input" placeholder="Optional">
                            </div>
                            <div>
                                <label class="label">Validation rules</label>
                                <input type="text" wire:model="fields.{{ $index }}.validation_rules" class="input" placeholder="min:2|max:80">
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="label">Min</label>
                                <input type="text" wire:model="fields.{{ $index }}.validation_config.min" class="input" placeholder="2">
                            </div>
                            <div>
                                <label class="label">Max</label>
                                <input type="text" wire:model="fields.{{ $index }}.validation_config.max" class="input" placeholder="255">
                            </div>
                            <div>
                                <label class="label">Regex</label>
                                <input type="text" wire:model="fields.{{ $index }}.validation_config.regex" class="input" placeholder="/^[A-Z]+$/">
                            </div>
                            <div>
                                <label class="label">Custom rules</label>
                                <input type="text" wire:model="fields.{{ $index }}.validation_config.custom" class="input" placeholder="starts_with:INV-">
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-4">
                            <label class="flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                                <input type="checkbox" wire:model="fields.{{ $index }}.validation_config.email" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                <span>Email rule</span>
                            </label>
                            <label class="flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                                <input type="checkbox" wire:model="fields.{{ $index }}.validation_config.numeric" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                <span>Numeric rule</span>
                            </label>
                            <div>
                                <label class="label">File mimes</label>
                                <input type="text" wire:model="fields.{{ $index }}.validation_config.file_mimes" class="input" placeholder="pdf,docx,png">
                            </div>
                            <div>
                                <label class="label">Max file KB</label>
                                <input type="text" wire:model="fields.{{ $index }}.validation_config.max_file_size" class="input" placeholder="5120">
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-3">
                            <div>
                                <label class="label">Visible when field</label>
                                <select wire:model="fields.{{ $index }}.condition_field" class="select">
                                    <option value="">Always visible</option>
                                    @foreach ($fields as $conditionIndex => $conditionField)
                                        @if ($conditionIndex !== $index && filled($conditionField['name'] ?? ''))
                                            <option value="{{ $conditionField['name'] }}">{{ $conditionField['label'] ?: $conditionField['name'] }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error("fields.$index.condition_field") <p class="mt-2 text-sm text-rose-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label">Condition</label>
                                <select wire:model="fields.{{ $index }}.condition_operator" class="select">
                                    <option value="">Choose condition</option>
                                    <option value="equals">Equals</option>
                                    <option value="not_equals">Does not equal</option>
                                    <option value="contains">Contains</option>
                                    <option value="greater_than">Greater than</option>
                                    <option value="less_than">Less than</option>
                                    <option value="empty">Is empty</option>
                                    <option value="not_empty">Is not empty</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Condition value</label>
                                <input type="text" wire:model="fields.{{ $index }}.condition_value" class="input" placeholder="Engineering">
                            </div>
                        </div>

                        <div class="mt-4 rounded-lg border border-white/[0.06] p-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-200">Condition group</p>
                                    <p class="mt-1 text-xs text-slate-500">Use multiple rules when one visibility check is not enough.</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <select wire:model="fields.{{ $index }}.condition_config.boolean" class="select w-24">
                                        <option value="and">AND</option>
                                        <option value="or">OR</option>
                                    </select>
                                    <button type="button" wire:click="addConditionRule({{ $index }})" class="btn-secondary px-3 py-1.5">Add rule</button>
                                </div>
                            </div>

                            <div class="mt-3 space-y-2">
                                @foreach (($field['condition_config']['rules'] ?? []) as $ruleIndex => $rule)
                                    <div class="grid gap-2 md:grid-cols-[1fr_12rem_1fr_6rem]">
                                        <select wire:model="fields.{{ $index }}.condition_config.rules.{{ $ruleIndex }}.field" class="select">
                                            <option value="">Field</option>
                                            @foreach ($fields as $conditionIndex => $conditionField)
                                                @if ($conditionIndex !== $index && filled($conditionField['name'] ?? ''))
                                                    <option value="{{ $conditionField['name'] }}">{{ $conditionField['label'] ?: $conditionField['name'] }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <select wire:model="fields.{{ $index }}.condition_config.rules.{{ $ruleIndex }}.operator" class="select">
                                            <option value="equals">Equals</option>
                                            <option value="not_equals">Does not equal</option>
                                            <option value="contains">Contains</option>
                                            <option value="greater_than">Greater than</option>
                                            <option value="less_than">Less than</option>
                                            <option value="empty">Is empty</option>
                                            <option value="not_empty">Is not empty</option>
                                        </select>
                                        <input type="text" wire:model="fields.{{ $index }}.condition_config.rules.{{ $ruleIndex }}.value" class="input" placeholder="Value">
                                        <button type="button" wire:click="removeConditionRule({{ $index }}, {{ $ruleIndex }})" class="btn-danger px-3 py-1.5">Remove</button>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-3">
                            <label class="flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                                <input type="checkbox" wire:model="fields.{{ $index }}.show_in_list" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                <span>Show in list</span>
                            </label>
                            <label class="flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                                <input type="checkbox" wire:model="fields.{{ $index }}.visible_in_form" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                <span>Show in form</span>
                            </label>
                            <label class="flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                                <input type="checkbox" wire:model="fields.{{ $index }}.searchable" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                <span>Searchable</span>
                            </label>
                            <label class="flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                                <input type="checkbox" wire:model="fields.{{ $index }}.filterable" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                <span>Filterable</span>
                            </label>
                            <label class="flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                                <input type="checkbox" wire:model="fields.{{ $index }}.sortable" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                <span>Sortable</span>
                            </label>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="label">Group</label>
                                <input type="text" wire:model="fields.{{ $index }}.group_name" class="input" placeholder="Billing">
                            </div>
                            <div>
                                <label class="label">Group type</label>
                                <select wire:model="fields.{{ $index }}.group_type" class="select">
                                    <option value="section">Section</option>
                                    <option value="tab">Tab</option>
                                    <option value="accordion">Accordion</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Column span</label>
                                <select wire:model="fields.{{ $index }}.column_span" class="select">
                                    <option value="1">One column</option>
                                    <option value="2">Full width</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-[1fr_10rem_9rem_9rem]">
                            <div>
                                <label class="label">Computed formula</label>
                                <input type="text" wire:model="fields.{{ $index }}.computed_config.expression" class="input" placeholder="{first_name} {last_name} or quantity * price">
                            </div>
                            <div>
                                <label class="label">Mode</label>
                                <select wire:model="fields.{{ $index }}.computed_config.mode" class="select">
                                    <option value="template">Template</option>
                                    <option value="math">Math</option>
                                </select>
                            </div>
                            <label class="mt-6 flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                                <input type="checkbox" wire:model="fields.{{ $index }}.computed_config.persist" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                <span>Persist</span>
                            </label>
                            <label class="mt-6 flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                                <input type="checkbox" wire:model="fields.{{ $index }}.computed_config.readonly" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                <span>Readonly</span>
                            </label>
                        </div>

                        <div class="mt-4">
                            <label class="label">Select / radio options</label>
                            <textarea wire:model="fields.{{ $index }}.options_text" class="input min-h-24" placeholder="One option per line"></textarea>
                        </div>
                    </div>
                @endforeach
            </div>

            @error('fields') <p class="mt-4 text-sm text-rose-500">{{ $message }}</p> @enderror

            <div class="mt-6">
                <div>
                    <h3 class="text-base font-semibold text-slate-100">Role permissions</h3>
                    <p class="mt-1 text-sm text-slate-500">Choose which roles receive this module's generated permissions.</p>
                </div>
                <div class="mt-4 overflow-x-auto rounded-xl border border-white/[0.06]">
                    <table class="min-w-full">
                        <thead class="table-head">
                            <tr>
                                <th class="px-4 py-3 text-left">Role</th>
                                @foreach ($abilities as $ability)
                                    <th class="px-4 py-3 text-center">{{ ucfirst($ability) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr class="table-row">
                                    <td class="table-cell font-medium text-slate-200">{{ $role->label }}</td>
                                    @foreach ($abilities as $ability)
                                        <td class="table-cell text-center">
                                            <input type="checkbox" wire:model="rolePermissions.{{ $role->id }}.{{ $ability }}" class="mx-auto h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="generate">Generate module</span>
                    <span wire:loading wire:target="generate">Generating...</span>
                </button>
            </div>
        </form>

        <div class="space-y-4">
            <section class="table-card overflow-hidden">
                <div class="border-b border-white/[0.055] px-5 py-3">
                    <h3 class="text-base font-semibold text-slate-100">Generated modules</h3>
                </div>
                <div class="divide-y divide-white/[0.055]">
                    @forelse ($modules as $module)
                        <div class="px-5 py-4">
                            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-100">{{ $module->name }}</p>
                                    <p class="text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ $module->table_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $module->fields_count }} fields</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if ($module->generated_at)
                                        <a href="{{ route('modules.records', $module->table_name) }}" wire:navigate class="btn-secondary px-3 py-1.5">Open</a>
                                    @endif
                                    <button type="button" wire:click="regenerate({{ $module->id }})" class="btn-secondary px-3 py-1.5">Regenerate</button>
                                    <button type="button" wire:click="snapshot({{ $module->id }})" class="btn-secondary px-3 py-1.5">Snapshot</button>
                                    <button type="button" wire:click="export({{ $module->id }})" class="btn-secondary px-3 py-1.5">Export</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-sm text-slate-500">No generated modules yet.</div>
                    @endforelse
                </div>
            </section>

            <section class="table-card px-5 py-4">
                <h3 class="text-base font-semibold text-slate-100">JSON import / export</h3>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="label">Import JSON</label>
                        <textarea wire:model="importJson" class="input min-h-36" placeholder="Paste exported module JSON"></textarea>
                        @error('importJson') <p class="mt-2 text-sm text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <button type="button" wire:click="import" class="btn-secondary">Load import</button>
                    <div>
                        <label class="label">Export JSON</label>
                        <textarea readonly class="input min-h-48">{{ $exportJson }}</textarea>
                    </div>
                </div>
            </section>
        </div>
    </section>
</div>
