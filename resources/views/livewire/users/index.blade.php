<div class="space-y-6">
    <section class="panel p-6 sm:p-8">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.3em] text-brand-600">Administration</p>
                <h2 class="mt-3 text-3xl font-semibold text-slate-950">User management</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500">
                    Manage users, assign roles, and keep access control simple with an admin-only module.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <input type="text" wire:model.live.debounce.300ms="search" class="input sm:w-72" placeholder="Search name or email">
                <select wire:model.live="roleFilter" class="select sm:w-44">
                    <option value="">All roles</option>
                    <option value="admin">Admin</option>
                    <option value="user">User</option>
                </select>
                <button type="button" wire:click="create" class="btn-primary">New user</button>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif
    </section>

    <section class="table-card">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="table-head">
                    <tr>
                        <th class="px-6 py-4">Name</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Role</th>
                        <th class="px-6 py-4">Created</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($users as $user)
                        <tr class="bg-white">
                            <td class="table-cell font-medium text-slate-900">{{ $user->name }}</td>
                            <td class="table-cell">{{ $user->email }}</td>
                            <td class="table-cell">
                                <span class="rounded-full {{ $user->role === 'admin' ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-600' }} px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em]">
                                    {{ $user->role }}
                                </span>
                            </td>
                            <td class="table-cell">{{ $user->created_at->format('M d, Y') }}</td>
                            <td class="table-cell">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="edit({{ $user->id }})" class="btn-secondary px-3 py-2">Edit</button>
                                    @if (auth()->id() !== $user->id)
                                        <button type="button" wire:click="confirmDelete({{ $user->id }})" class="btn-danger px-3 py-2">Delete</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">No users matched your current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 bg-white px-6 py-4">
            {{ $users->links() }}
        </div>
    </section>

    @if ($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 py-6">
            <div class="panel w-full max-w-2xl p-6 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-2xl font-semibold text-slate-950">{{ $editingUserId ? 'Edit user' : 'Create user' }}</h3>
                        <p class="mt-2 text-sm text-slate-500">Fill in the details below and save the account.</p>
                    </div>
                    <button type="button" wire:click="closeFormModal" class="text-sm font-medium text-slate-500 hover:text-slate-900">Close</button>
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
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
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
                <h3 class="text-2xl font-semibold text-slate-950">Delete user</h3>
                <p class="mt-3 text-sm leading-6 text-slate-500">This action permanently removes the selected user account.</p>

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
