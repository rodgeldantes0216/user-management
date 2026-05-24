<div class="space-y-4">
    <section class="content-panel px-5 py-4">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <p class="section-kicker">Administration</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-100">Module builder</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Create metadata-driven CRUD modules with generated tables, permissions, validation, audit logs, and sidebar entries.</p>
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
                    <p class="mt-1 text-sm text-slate-500">Use the arrows to reorder fields before generation.</p>
                </div>
                <button type="button" wire:click="addField" class="btn-secondary px-3 py-1.5">Add field</button>
            </div>

            <div class="mt-4 space-y-3">
                @foreach ($fields as $index => $field)
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
                                    <input type="text" wire:model="fields.{{ $index }}.name" class="input" placeholder="first_name">
                                    @error("fields.$index.name") <p class="mt-2 text-sm text-rose-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label">Type</label>
                                    <select wire:model="fields.{{ $index }}.type" class="select">
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

                        <div class="mt-4">
                            <label class="label">Select / radio options</label>
                            <textarea wire:model="fields.{{ $index }}.options_text" class="input min-h-24" placeholder="One option per line"></textarea>
                        </div>
                    </div>
                @endforeach
            </div>

            @error('fields') <p class="mt-4 text-sm text-rose-500">{{ $message }}</p> @enderror

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
