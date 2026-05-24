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
                        @else
                            @foreach ($field->optionList() as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        @endif
                    </select>
                @endforeach
                <button type="button" wire:click="create" class="btn-primary min-w-[7rem]">New record</button>
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
                        @foreach ($fields as $field)
                            <th class="px-5 py-3">
                                <button type="button" wire:click="sortBy('{{ $field->name }}')" class="text-left uppercase tracking-[0.28em]">
                                    {{ $field->label }}
                                    @if ($sortField === $field->name)
                                        {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                    @endif
                                </button>
                            </th>
                        @endforeach
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr class="table-row">
                            @foreach ($fields as $field)
                                <td class="table-cell">
                                    @if (in_array($field->type, ['checkbox', 'toggle'], true))
                                        <span class="badge-role">{{ $record->{$field->name} ? 'Yes' : 'No' }}</span>
                                    @elseif (in_array($field->type, ['file', 'image'], true) && $record->{$field->name})
                                        <a href="{{ asset('storage/'.$record->{$field->name}) }}" target="_blank" class="text-brand-300 hover:text-brand-200">View file</a>
                                    @elseif ($field->type === 'password')
                                        <span class="text-slate-500">Protected</span>
                                    @else
                                        {{ $record->{$field->name} }}
                                    @endif
                                </td>
                            @endforeach
                            <td class="table-cell">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="edit({{ $record->id }})" class="btn-secondary px-3 py-1.5">Edit</button>
                                    <button type="button" wire:click="confirmDelete({{ $record->id }})" class="btn-danger px-3 py-1.5">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $fields->count() + 1 }}" class="px-5 py-10 text-center text-sm text-slate-500">No records matched your current filters.</td>
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
                    <div class="grid gap-5 sm:grid-cols-2">
                        @foreach ($fields as $field)
                            <div class="{{ $field->type === 'textarea' ? 'sm:col-span-2' : '' }}">
                                <label class="label">{{ $field->label }}</label>

                                @if ($field->type === 'textarea')
                                    <textarea wire:model="form.{{ $field->name }}" class="input min-h-32" placeholder="{{ $field->placeholder }}"></textarea>
                                @elseif ($field->type === 'select')
                                    <select wire:model="form.{{ $field->name }}" class="select">
                                        <option value="">Choose {{ strtolower($field->label) }}</option>
                                        @foreach ($field->optionList() as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @elseif ($field->type === 'radio')
                                    <div class="flex flex-wrap gap-3">
                                        @foreach ($field->optionList() as $option)
                                            <label class="flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                                                <input type="radio" wire:model="form.{{ $field->name }}" value="{{ $option }}" class="h-4 w-4 border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                                <span>{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif (in_array($field->type, ['checkbox', 'toggle'], true))
                                    <label class="flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                                        <input type="checkbox" wire:model="form.{{ $field->name }}" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                        <span>Enabled</span>
                                    </label>
                                @elseif (in_array($field->type, ['file', 'image'], true))
                                    <input type="file" wire:model="form.{{ $field->name }}" class="input">
                                @else
                                    <input type="{{ $field->type === 'datetime' ? 'datetime-local' : $field->type }}" wire:model="form.{{ $field->name }}" class="input" placeholder="{{ $field->placeholder }}">
                                @endif

                                @error('form.'.$field->name) <p class="mt-2 text-sm text-rose-500">{{ $message }}</p> @enderror
                            </div>
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
