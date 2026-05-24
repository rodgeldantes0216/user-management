<div class="space-y-4">
    <section class="content-panel px-5 py-4">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="section-kicker">Operations</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-100">Notification Center</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Track in-app notifications by module, with unread badges and per-notification read state.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <select wire:model.live="filter" class="select sm:w-[12rem]">
                    <option value="all">All notifications</option>
                    <option value="unread">Unread only</option>
                    <option value="read">Read only</option>
                </select>
                <button type="button" wire:click="markAllAsRead" class="btn-secondary">Mark all read</button>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg border border-emerald-800/70 bg-emerald-950/30 px-3 py-2 text-sm font-medium text-emerald-300">
                {{ session('status') }}
            </div>
        @endif
    </section>

    <section class="grid gap-3 md:grid-cols-3">
        <article class="content-panel px-5 py-4">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Unread notifications</p>
            <p class="mt-3 text-2xl font-semibold text-slate-100">{{ $unreadCount }}</p>
        </article>
    </section>

    <section class="table-card relative">
        <div wire:loading.flex wire:target="gotoPage,nextPage,previousPage,filter" class="absolute inset-0 z-20 hidden items-center justify-center rounded-xl bg-black/20 backdrop-blur-md">
            <div class="flex items-center gap-3 rounded-lg border border-white/10 bg-[#12161b] px-4 py-3 text-sm font-medium text-slate-200">
                <svg class="h-5 w-5 animate-spin text-brand-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span>Loading notifications...</span>
            </div>
        </div>

        <div wire:loading.class="pointer-events-none opacity-40 blur-[3px] saturate-50" wire:target="gotoPage,nextPage,previousPage,filter" class="divide-y divide-white/[0.055]">
            @forelse ($notifications as $notification)
                <div class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-start lg:justify-between {{ $notification->read_at ? 'opacity-65' : '' }}">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-3">
                            <p class="text-sm font-semibold text-slate-100">{{ $notification->title }}</p>
                            <span class="badge-role">{{ $notification->module_key }}</span>
                            @if (! $notification->read_at)
                                <span class="inline-flex rounded-md bg-brand-700/20 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-brand-200">Unread</span>
                            @endif
                        </div>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-400">{{ $notification->message }}</p>
                        <p class="mt-2 text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ $notification->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($notification->read_at)
                            <button type="button" wire:click="markAsUnread({{ $notification->id }})" class="btn-secondary px-3 py-1.5">Mark unread</button>
                        @else
                            <button type="button" wire:click="markAsRead({{ $notification->id }})" class="btn-secondary px-3 py-1.5">Mark read</button>
                        @endif
                        @can('notifications.delete')
                            <button type="button" wire:click="delete({{ $notification->id }})" class="btn-danger px-3 py-1.5">Delete</button>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-slate-500">No notifications found.</div>
            @endforelse
        </div>

        <div class="px-5 py-3">
            {{ $notifications->links() }}
        </div>
    </section>
</div>
