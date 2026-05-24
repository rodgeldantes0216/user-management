<div class="space-y-4">
    <section class="content-panel px-5 py-4">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="section-kicker">Administration</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-100">Audit Trail</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Review who performed important actions across the app. Activity logs can only be deleted by users with the right permission.
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <input type="text" wire:model.live.debounce.300ms="search" class="input sm:w-72" placeholder="Search name, email, or action">
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg border border-emerald-800/70 bg-emerald-950/30 px-3 py-2 text-sm font-medium text-emerald-300">
                {{ session('status') }}
            </div>
        @endif
    </section>

    <section class="table-card relative">
        <div wire:loading.flex wire:target="gotoPage,nextPage,previousPage,search" class="absolute inset-0 z-20 hidden items-center justify-center rounded-xl bg-black/20 backdrop-blur-md">
            <div class="flex items-center gap-3 rounded-lg border border-white/10 bg-[#12161b] px-4 py-3 text-sm font-medium text-slate-200">
                <svg class="h-5 w-5 animate-spin text-brand-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span>Loading activity...</span>
            </div>
        </div>

        <div wire:loading.class="pointer-events-none opacity-40 blur-[3px] saturate-50" wire:target="gotoPage,nextPage,previousPage,search" class="overflow-x-auto transition duration-200">
            <table class="min-w-full">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Action</th>
                        <th class="px-5 py-3">Timestamp</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activities as $activity)
                        <tr class="table-row">
                            <td class="table-cell font-medium text-blue-100">{{ $activity->name }}</td>
                            <td class="table-cell text-blue-100/95">{{ $activity->email }}</td>
                            <td class="table-cell">
                                <span class="badge-role">{{ $activity->action }}</span>
                            </td>
                            <td class="table-cell text-blue-100/95">{{ $activity->created_at->format('M d, Y h:i A') }}</td>
                            <td class="table-cell">
                                <div class="flex justify-end gap-2">
                                    @can('activities.delete')
                                        <button type="button" wire:click="confirmDelete({{ $activity->id }})" class="btn-danger px-3 py-1.5">Delete</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">No activity logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-3">
            {{ $activities->links() }}
        </div>
    </section>

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 py-6">
            <div class="panel w-full max-w-lg p-6 sm:p-8">
                <h3 class="text-2xl font-semibold text-slate-100">Delete activity log</h3>
                <p class="mt-3 text-sm leading-6 text-slate-400">This removes the selected audit record permanently.</p>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="closeDeleteModal" class="btn-secondary">Cancel</button>
                    <button type="button" wire:click="delete" class="btn-danger" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="delete">Delete log</span>
                        <span wire:loading wire:target="delete">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
