<div class="space-y-4">
    <section class="content-panel px-5 py-4">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="section-kicker">Administration</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-100">Roles & Permissions</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Navigation permissions are synced automatically from the app navigation config. Add a new nav item there and its permission will be created for you.
                </p>
            </div>

            <button type="button" wire:click="create" class="btn-primary min-w-[7rem]">New role</button>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg border border-emerald-800/70 bg-emerald-950/30 px-3 py-2 text-sm font-medium text-emerald-300">
                {{ session('status') }}
            </div>
        @endif
    </section>

    <section class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
        <div class="table-card overflow-hidden">
            <div class="border-b border-white/[0.055] px-5 py-3">
                <h3 class="text-base font-semibold text-slate-100">Roles</h3>
            </div>
            <div class="divide-y divide-white/[0.055]">
                @foreach ($roles as $role)
                    <div class="flex flex-col gap-3 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-100">{{ $role->label }}</p>
                            <p class="text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ $role->name }}</p>
                            <p class="mt-2 text-sm text-slate-400">{{ $role->users_count }} users • {{ $role->permissions->count() }} permissions</p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" wire:click="edit({{ $role->id }})" class="btn-secondary px-3 py-1.5">Edit</button>
                            <button type="button" wire:click="delete({{ $role->id }})" class="btn-danger px-3 py-1.5">Delete</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="table-card overflow-hidden">
            <div class="border-b border-white/[0.055] px-5 py-3">
                <h3 class="text-base font-semibold text-slate-100">Available permissions</h3>
            </div>
            <div class="space-y-4 px-5 py-4">
                @foreach ($permissions as $group => $groupPermissions)
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ $group }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($groupPermissions as $permission)
                                <span class="badge-role">{{ $permission->name }}</span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @if ($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 py-6">
            <div class="panel w-full max-w-4xl p-6 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-2xl font-semibold text-slate-100">{{ $editingRoleId ? 'Edit role' : 'Create role' }}</h3>
                        <p class="mt-2 text-sm text-slate-400">Choose the permissions this role should have across your modules.</p>
                    </div>
                    <button type="button" wire:click="closeFormModal" class="text-sm font-medium text-slate-500 hover:text-slate-100">Close</button>
                </div>

                <form wire:submit="save" class="mt-6 space-y-6">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="label">Role name</label>
                            <input type="text" wire:model="name" class="input" placeholder="manager">
                            @error('name') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Label</label>
                            <input type="text" wire:model="label" class="input" placeholder="Manager">
                            @error('label') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="space-y-5">
                        @foreach ($permissions as $group => $groupPermissions)
                            <div class="rounded-xl border border-white/[0.06] p-4">
                                <p class="text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ $group }}</p>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    @foreach ($groupPermissions as $permission)
                                        <label class="flex items-center gap-3 rounded-lg border border-white/[0.06] px-3 py-2.5 text-sm text-slate-300">
                                            <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->name }}" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                                            <span>{{ $permission->label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="closeFormModal" class="btn-secondary">Cancel</button>
                        <button type="submit" class="btn-primary">Save role</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
