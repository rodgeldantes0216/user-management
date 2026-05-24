<div class="space-y-6">
    <section class="panel p-6 sm:p-8">
        <p class="text-sm uppercase tracking-[0.3em] text-brand-600">Overview</p>
        <div class="mt-4 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-3xl font-semibold text-slate-950">Dashboard</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500">
                    This workspace uses Livewire full-page components with `wire:navigate`, so moving between pages stays smooth while the backend remains fully Laravel.
                </p>
            </div>

            @can('viewAny', \App\Models\User::class)
                <a href="{{ route('users.index') }}" wire:navigate class="btn-primary">Open user management</a>
            @endcan
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-3">
        <article class="panel p-6">
            <p class="text-sm text-slate-500">Total users</p>
            <p class="mt-4 text-4xl font-semibold text-slate-950">{{ $totalUsers }}</p>
        </article>
        <article class="panel p-6">
            <p class="text-sm text-slate-500">Administrators</p>
            <p class="mt-4 text-4xl font-semibold text-slate-950">{{ $adminCount }}</p>
        </article>
        <article class="panel p-6">
            <p class="text-sm text-slate-500">Your role</p>
            <p class="mt-4 text-2xl font-semibold capitalize text-slate-950">{{ auth()->user()->role }}</p>
        </article>
    </section>

    <section class="panel p-6 sm:p-8">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h3 class="text-xl font-semibold text-slate-950">Recently created users</h3>
                <p class="mt-1 text-sm text-slate-500">A quick snapshot of the latest accounts in the system.</p>
            </div>
        </div>

        <div class="mt-6 space-y-4">
            @foreach ($latestUsers as $user)
                <div class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-4">
                    <div>
                        <p class="font-medium text-slate-900">{{ $user->name }}</p>
                        <p class="text-sm text-slate-500">{{ $user->email }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">
                        {{ $user->role }}
                    </span>
                </div>
            @endforeach
        </div>
    </section>
</div>
