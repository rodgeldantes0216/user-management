<div class="space-y-4">
    <section class="content-panel px-5 py-4">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="section-kicker">Administration</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-100">User management</h2>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <input type="text" wire:model.live.debounce.300ms="search" class="input sm:w-64" placeholder="Search name or email">
                <select wire:model.live="roleFilter" class="select sm:w-44">
                    <option value="">All roles</option>
                    @foreach ($roles as $availableRole)
                        <option value="{{ $availableRole->name }}">{{ $availableRole->label }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="create" class="btn-primary min-w-[6.5rem]">New user</button>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg border border-emerald-800/70 bg-emerald-950/30 px-3 py-2 text-sm font-medium text-emerald-300">
                {{ session('status') }}
            </div>
        @endif
    </section>

    <section class="table-card relative">
        <div wire:loading.flex wire:target="gotoPage,nextPage,previousPage,search,roleFilter" class="absolute inset-0 z-20 hidden items-center justify-center rounded-xl bg-black/20 backdrop-blur-md">
            <div class="flex items-center gap-3 rounded-lg border border-white/10 bg-[#12161b] px-4 py-3 text-sm font-medium text-slate-200">
                <svg class="h-5 w-5 animate-spin text-brand-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span>Loading users...</span>
            </div>
        </div>

        <div wire:loading.class="pointer-events-none opacity-40 blur-[3px] saturate-50" wire:target="gotoPage,nextPage,previousPage,search,roleFilter" class="overflow-x-auto transition duration-200">
            <table class="min-w-full">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Created</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="table-row">
                            <td class="table-cell font-medium text-blue-100">{{ $user->name }}</td>
                            <td class="table-cell text-blue-100/95">{{ $user->email }}</td>
                            <td class="table-cell">
                                <span class="badge-role">
                                    {{ $user->primaryRoleName() }}
                                </span>
                            </td>
                            <td class="table-cell text-blue-100/95">{{ $user->created_at->format('M d, Y') }}</td>
                            <td class="table-cell">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="edit({{ $user->id }})" class="btn-secondary px-3 py-1.5">Edit</button>
                                    @if (auth()->id() !== $user->id)
                                        <button type="button" wire:click="confirmDelete({{ $user->id }})" class="btn-danger px-3 py-1.5">Delete</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">No users matched your current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-3">
            {{ $users->links() }}
        </div>
    </section>

    @if ($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 py-6">
            <div class="panel w-full max-w-2xl p-6 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-2xl font-semibold text-slate-100">{{ $editingUserId ? 'Edit user' : 'Create user' }}</h3>
                        <p class="mt-2 text-sm text-slate-400">Fill in the details below and save the account.</p>
                    </div>
                    <button type="button" wire:click="closeFormModal" class="text-sm font-medium text-slate-500 hover:text-slate-100">Close</button>
                </div>

                <form wire:submit="save" class="mt-6 space-y-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="label">Name</label>
                            <input type="text" wire:model="name" class="input" placeholder="John Doe">
                            @error('name') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Email</label>
                            <input type="email" wire:model="email" class="input" placeholder="john@example.com">
                            @error('email') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <label class="label">Role</label>
                            <select wire:model="role" class="select">
                                @foreach ($roles as $availableRole)
                                    <option value="{{ $availableRole->name }}">{{ $availableRole->label }}</option>
                                @endforeach
                            </select>
                            @error('role') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">{{ $editingUserId ? 'New password' : 'Password' }}</label>
                            <input type="password" wire:model="password" class="input" placeholder="{{ $editingUserId ? 'Leave blank to keep current' : 'Minimum 8 characters' }}">
                            @error('password') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Confirm password</label>
                            <input type="password" wire:model="password_confirmation" class="input" placeholder="Repeat password">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="closeFormModal" class="btn-secondary">Cancel</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">{{ $editingUserId ? 'Save changes' : 'Create user' }}</span>
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
                <h3 class="text-2xl font-semibold text-slate-100">Delete user</h3>
                <p class="mt-3 text-sm leading-6 text-slate-400">This action permanently removes the selected user account.</p>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="closeDeleteModal" class="btn-secondary">Cancel</button>
                    <button type="button" wire:click="delete" class="btn-danger" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="delete">Delete user</span>
                        <span wire:loading wire:target="delete">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
