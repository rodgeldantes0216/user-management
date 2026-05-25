<div class="space-y-4">
    <section class="content-panel px-5 py-4">
        <p class="text-sm uppercase tracking-[0.3em] text-brand-600">My Profile</p>
        <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-100">Edit your profile</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Update your personal details, preferences, and profile picture. Your activity summary appears below.
                </p>
            </div>
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.25fr_0.75fr]">
        <article class="content-panel px-5 py-4">
            <form wire:submit.prevent="save" class="space-y-6">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div>
                        <label class="label" for="name">Full name</label>
                        <input id="name" type="text" wire:model.defer="name" class="input w-full" />
                        @error('name') <p class="mt-2 text-xs text-rose-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="username">Username</label>
                        <input id="username" type="text" wire:model.defer="username" class="input w-full" />
                        @error('username') <p class="mt-2 text-xs text-rose-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="email">Email</label>
                        <input id="email" type="email" wire:model.defer="email" class="input w-full" />
                        @error('email') <p class="mt-2 text-xs text-rose-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="phone">Phone</label>
                        <input id="phone" type="text" wire:model.defer="phone" class="input w-full" />
                        @error('phone') <p class="mt-2 text-xs text-rose-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="location">Location</label>
                        <input id="location" type="text" wire:model.defer="location" class="input w-full" />
                        @error('location') <p class="mt-2 text-xs text-rose-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="label" for="bio">Bio</label>
                    <textarea id="bio" wire:model.defer="bio" rows="5" class="input w-full min-h-[8rem]"></textarea>
                    @error('bio') <p class="mt-2 text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="space-y-2">
                        <label class="label" for="profile_picture">Profile picture</label>
                        <input id="profile_picture" type="file" wire:model="profile_picture" class="input w-full" />
                        @error('profile_picture') <p class="mt-2 text-xs text-rose-400">{{ $message }}</p> @enderror
                        @if ($profile_picture)
                            <p class="text-sm text-slate-400">Selected file: {{ $profile_picture->getClientOriginalName() }}</p>
                        @endif
                    </div>
                    <div class="space-y-3">
                        <p class="text-sm text-slate-500">Preferences</p>
                        <div class="flex items-center gap-3">
                            <input id="email_notifications" type="checkbox" wire:model="email_notifications" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0" />
                            <label for="email_notifications" class="text-sm text-slate-100">Email notifications</label>
                        </div>
                        <div class="flex items-center gap-3">
                            <input id="show_activity_summary" type="checkbox" wire:model="show_activity_summary" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0" />
                            <label for="show_activity_summary" class="text-sm text-slate-100">Show activity summary</label>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-4 pt-4">
                    <button type="submit" class="btn-primary">Save profile</button>
                    @if (session('status'))
                        <p class="text-sm text-emerald-300">{{ session('status') }}</p>
                    @endif
                </div>
            </form>
        </article>

        <aside class="content-panel px-5 py-4">
            <div class="space-y-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-3xl bg-slate-800">
                        @if ($currentPictureUrl)
                            <img src="{{ $currentPictureUrl }}" alt="Profile picture" class="h-full w-full object-cover" />
                        @else
                            <span class="text-slate-500">No photo</span>
                        @endif
                    </div>
                    <div>
                        <p class="text-base font-semibold text-slate-100">{{ auth()->user()->name }}</p>
                        <p class="text-sm text-slate-500">{{ auth()->user()->username ? '@'.auth()->user()->username : 'No username set' }}</p>
                        <p class="text-sm text-slate-500">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <div class="rounded-3xl bg-slate-950/50 p-4">
                    <p class="text-sm uppercase tracking-[0.18em] text-slate-500">Activity summary</p>
                    <p class="mt-2 text-sm text-slate-400">Your latest actions are recorded for audit and self-review.</p>

                    <div class="mt-4 space-y-3">
                        @forelse ($activityLogs as $log)
                            <div class="rounded-2xl border border-white/[0.06] p-3">
                                <p class="text-sm font-semibold text-slate-100">{{ $log->action }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $log->created_at->diffForHumans() }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No recent activity was recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </aside>
    </section>
</div>
