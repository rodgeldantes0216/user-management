<div class="space-y-6">
    <section class="content-panel px-7 py-7 sm:px-10 sm:py-8">
        <div>
            <p class="section-kicker">Configuration</p>
            <h2 class="mt-5 text-3xl font-semibold text-slate-100 md:text-4xl">Settings Management</h2>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-400">
                Manage branding, mail defaults, and feature flags from the database.
            </p>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-2xl border border-emerald-800 bg-emerald-950/40 px-4 py-3 text-sm font-medium text-emerald-300">
                {{ session('status') }}
            </div>
        @endif
    </section>

    <form wire:submit="save" class="space-y-6">
        <section class="table-card px-8 py-8">
            <h3 class="text-lg font-semibold text-slate-100">Branding</h3>
            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div>
                    <label class="label">Site name</label>
                    <input type="text" wire:model="site_name" class="input" placeholder="User Management">
                    @error('site_name') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Site logo URL or path</label>
                    <input type="text" wire:model="site_logo" class="input" placeholder="/images/logo.svg">
                    @error('site_logo') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="table-card px-8 py-8">
            <h3 class="text-lg font-semibold text-slate-100">Mail Settings</h3>
            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div>
                    <label class="label">Mail from name</label>
                    <input type="text" wire:model="mail_from_name" class="input" placeholder="Admin Team">
                    @error('mail_from_name') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Mail from address</label>
                    <input type="email" wire:model="mail_from_address" class="input" placeholder="admin@example.com">
                    @error('mail_from_address') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="table-card px-8 py-8">
            <h3 class="text-lg font-semibold text-slate-100">Feature Flags</h3>
            <div class="mt-6 grid gap-4">
                <label class="flex items-center gap-3 rounded-2xl border border-slate-800 px-4 py-4 text-sm text-slate-300">
                    <input type="checkbox" wire:model="feature_registration_enabled" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                    <span>Enable public registration</span>
                </label>
                <label class="flex items-center gap-3 rounded-2xl border border-slate-800 px-4 py-4 text-sm text-slate-300">
                    <input type="checkbox" wire:model="feature_audit_enabled" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                    <span>Enable audit trail module</span>
                </label>
                <label class="flex items-center gap-3 rounded-2xl border border-slate-800 px-4 py-4 text-sm text-slate-300">
                    <input type="checkbox" wire:model="feature_notifications_enabled" class="h-4 w-4 rounded border-slate-700 bg-transparent text-brand-500 focus:ring-0">
                    <span>Enable notification center</span>
                </label>
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Save settings</button>
        </div>
    </form>
</div>
