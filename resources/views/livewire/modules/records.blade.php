<div class="space-y-4">
    <section class="content-panel px-5 py-4">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="section-kicker">Dynamic module</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-100">{{ $module->name }}</h2>
                @if ($module->description)
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">{{ $module->description }}</p>
                @endif
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <input type="text" wire:model.live.debounce.300ms="search" class="input sm:w-64" placeholder="Search records">
                @foreach ($filterFields as $field)
                    <select wire:model.live="filters.{{ $field->name }}" class="select sm:w-44">
                        <option value="">{{ $field->label }}</option>
                        @if (in_array($field->type, ['checkbox', 'toggle'], true))
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        @elseif ($field->type === 'relationship')
                            @foreach ($field->relationshipOptions() as $option)
                                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        @else
                            @foreach ($field->optionList() as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        @endif
                    </select>
                @endforeach
                @if ($canCreate)
                    <button type="button" wire:click="create" class="btn-primary min-w-[7rem]">New record</button>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg border border-emerald-800/70 bg-emerald-950/30 px-3 py-2 text-sm font-medium text-emerald-300">
                {{ session('status') }}
            </div>
        @endif
    </section>

    <section class="table-card relative">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="table-head">
                    <tr>
                        @foreach ($listFields as $field)
                            <th class="px-5 py-3">
                                @if ($field->sortable)
                                    <button type="button" wire:click="sortBy('{{ $field->name }}')" class="text-left uppercase tracking-[0.28em]">
                                        {{ $field->label }}
                                        @if ($sortField === $field->name)
                                            {{ $sortDirection === 'asc' ? 'ASC' : 'DESC' }}
                                        @endif
                                    </button>
                                @else
                                    <span class="text-left uppercase tracking-[0.28em]">{{ $field->label }}</span>
                                @endif
                            </th>
                        @endforeach
                        @if ($canUpdate || $canDelete)
                            <th class="px-5 py-3 text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr class="table-row">
                            @foreach ($listFields as $field)
                                <td class="table-cell">
                                    @if (in_array($field->type, ['checkbox', 'toggle'], true))
                                        <span class="badge-role">{{ $record->{$field->name} ? 'Yes' : 'No' }}</span>
                                    @elseif ($field->type === 'relationship')
                                        {{ $relationshipDisplays[$field->name][$record->{$field->name}] ?? ('#'.$record->{$field->name}) }}
                                    @elseif ($field->type === 'color' && $record->{$field->name})
                                        <span class="inline-flex items-center gap-2">
                                            <span class="h-4 w-4 rounded border border-white/10" style="background-color: {{ $record->{$field->name} }}"></span>
                                            {{ $record->{$field->name} }}
                                        </span>
                                    @elseif ($field->type === 'currency')
                                        {{ is_numeric($record->{$field->name}) ? number_format((float) $record->{$field->name}, 2) : $record->{$field->name} }}
                                    @elseif ($field->type === 'date_range' && $record->{$field->name})
                                        @php($range = json_decode($record->{$field->name}, true) ?: [])
                                        {{ ($range['start'] ?? '') }} @if (($range['start'] ?? '') || ($range['end'] ?? '')) to @endif {{ ($range['end'] ?? '') }}
                                    @elseif ($field->type === 'json')
                                        <code class="text-xs text-slate-400">{{ str($record->{$field->name})->limit(80) }}</code>
                                    @elseif ($field->type === 'rich_text')
                                        {{ str(strip_tags($record->{$field->name}))->limit(120) }}
                                    @elseif (in_array($field->type, ['file', 'image'], true) && $record->{$field->name})
                                        <a href="{{ asset('storage/'.$record->{$field->name}) }}" target="_blank" class="text-brand-300 hover:text-brand-200">View file</a>
                                    @elseif ($field->type === 'password')
                                        <span class="text-slate-500">Protected</span>
                                    @else
                                        {{ $record->{$field->name} }}
                                    @endif
                                </td>
                            @endforeach
                            @if ($canUpdate || $canDelete)
                                <td class="table-cell">
                                    <div class="flex justify-end gap-2">
                                        @if ($canUpdate)
                                            <button type="button" wire:click="edit({{ $record->id }})" class="btn-secondary px-3 py-1.5">Edit</button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" wire:click="confirmDelete({{ $record->id }})" class="btn-danger px-3 py-1.5">Delete</button>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $listFields->count() + (($canUpdate || $canDelete) ? 1 : 0) }}" class="px-5 py-10 text-center text-sm text-slate-500">No records matched your current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-3">
            {{ $records->links() }}
        </div>
    </section>

    @if ($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 py-6">
            <div class="panel max-h-[92vh] w-full max-w-4xl overflow-y-auto p-6 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-2xl font-semibold text-slate-100">{{ $editingRecordId ? 'Edit record' : 'Create record' }}</h3>
                        <p class="mt-2 text-sm text-slate-400">{{ $module->name }}</p>
                    </div>
                    <button type="button" wire:click="closeFormModal" class="text-sm font-medium text-slate-500 hover:text-slate-100">Close</button>
                </div>

                <form wire:submit="save" class="mt-6 space-y-5">
                    <div class="space-y-5">
                        @foreach ($formGroups as $groupName => $groupFields)
                            <section class="space-y-4">
                                <h4 class="text-sm font-semibold text-slate-200">{{ $groupName }}</h4>
                                <div class="grid gap-5 sm:grid-cols-2">
                        @foreach ($groupFields as $field)
                            @if ($this->fieldIsVisible($field))
                                <div class="{{ in_array($field->type, ['textarea', 'rich_text', 'json'], true) || $field->column_span === 2 ? 'sm:col-span-2' : '' }}">
                                    <label class="label">{{ $field->label }}</label>

                                    @if (in_array($field->type, ['textarea', 'rich_text'], true))
                                        <textarea wire:model.live="form.{{ $field->name }}" class="input min-h-32" placeholder="{{ $field->placeholder }}" @readonly((bool) ($field->computed_config['readonly'] ?? false))></textarea>
                                    @elseif ($field->type === 'json')
                                        <textarea wire:model.live="form.{{ $field->name }}" class="input min-h-36 font-mono text-xs" placeholder='{"key":"value"}' @readonly((bool) ($field->computed_config['readonly'] ?? false))></textarea>
                                    @elseif ($field->type === 'select')
                                        <select wire:model.live="form.{{ $field->name }}" class="select">
                                            <option value="">Choose {{ strtolower($field->label) }}</option>
                                            @foreach ($field->optionList() as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($field->type === 'relationship')
                                        <select wire:model.live="form.{{ $field->name }}" class="select">
                                            <option value="">Choose {{ strtolower($field->label) }}</option>
                                            @foreach ($field->relationshipOptions() as $option)
                                                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($field->type === 'radio')
                                        <div class="flex flex-wrap gap-3">
                                            @foreach ($field->optionList() as $option)
                                                <label class="flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                                                    <input type="radio" wire:model.live="form.{{ $field->name }}" value="{{ $option }}" class="h-4 w-4 border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                                    <span>{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif (in_array($field->type, ['checkbox', 'toggle'], true))
                                        <label class="flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                                            <input type="checkbox" wire:model.live="form.{{ $field->name }}" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                            <span>Enabled</span>
                                        </label>
                                    @elseif (in_array($field->type, ['file', 'image'], true))
                                        <input type="file" wire:model="form.{{ $field->name }}" class="input">
                                    @elseif ($field->type === 'color')
                                        <div class="flex gap-3">
                                            <input type="color" wire:model.live="form.{{ $field->name }}" class="h-11 w-16 rounded border border-white/[0.08] bg-transparent">
                                            <input type="text" wire:model.live="form.{{ $field->name }}" class="input" placeholder="#2563eb">
                                        </div>
                                    @elseif ($field->type === 'currency')
                                        <input type="number" step="0.01" wire:model.live="form.{{ $field->name }}" class="input" placeholder="{{ $field->placeholder }}" @readonly((bool) ($field->computed_config['readonly'] ?? false))>
                                    @elseif ($field->type === 'date_range')
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <input type="date" wire:model.live="form.{{ $field->name }}.start" class="input">
                                            <input type="date" wire:model.live="form.{{ $field->name }}.end" class="input">
                                        </div>
                                    @else
                                        <input type="{{ $field->type === 'datetime' ? 'datetime-local' : $field->type }}" wire:model.live="form.{{ $field->name }}" class="input" placeholder="{{ $field->placeholder }}" @readonly((bool) ($field->computed_config['readonly'] ?? false))>
                                    @endif

                                    @error('form.'.$field->name) <p class="mt-2 text-sm text-rose-500">{{ $message }}</p> @enderror
                                </div>
                            @endif
                        @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="closeFormModal" class="btn-secondary">Cancel</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">{{ $editingRecordId ? 'Save changes' : 'Create record' }}</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 py-6">
            <div class="panel w-full max-w-lg p-6 sm:p-8">
                <h3 class="text-2xl font-semibold text-slate-100">Delete record</h3>
                <p class="mt-3 text-sm leading-6 text-slate-400">This action removes the selected {{ strtolower($module->name) }} record.</p>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="closeDeleteModal" class="btn-secondary">Cancel</button>
                    <button type="button" wire:click="delete" class="btn-danger" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="delete">Delete record</span>
                        <span wire:loading wire:target="delete">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
