<div class="space-y-4">
    <section class="content-panel px-5 py-4">
        <p class="text-sm uppercase tracking-[0.3em] text-brand-600">Overview</p>
        <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-100">Dashboard</h2>
                <!-- <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500">
                    This workspace uses Livewire full-page components with `wire:navigate`, so moving between pages stays smooth while the backend remains fully Laravel.
                </p> -->
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('profile.index') }}" wire:navigate class="btn-secondary">Edit Profile</a>
                @can('viewAny', \App\Models\User::class)
                    <a href="{{ route('users.index') }}" wire:navigate class="btn-primary">Open User Management</a>
                @endcan
            </div>
        </div>
    </section>

    <section class="grid gap-3 md:grid-cols-3">
        <article class="content-panel px-5 py-4">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Total users</p>
            <p class="mt-3 text-2xl font-semibold text-slate-100">{{ $totalUsers }}</p>
        </article>
        <article class="content-panel px-5 py-4">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Administrators</p>
            <p class="mt-3 text-2xl font-semibold text-slate-100">{{ $adminCount }}</p>
        </article>
        <article class="content-panel px-5 py-4">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Your role</p>
            <p class="mt-3 text-xl font-semibold capitalize text-slate-100">{{ auth()->user()->role }}</p>
        </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
        <article class="content-panel px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-100">User signups</h3>
                    <p class="mt-1 text-sm text-slate-500">New users over the last 7 days.</p>
                </div>
            </div>
            <div class="mt-5 h-72">
                <canvas id="dashboard-signup-chart" class="w-full h-full"></canvas>
            </div>
        </article>

        <article class="content-panel px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-100">Role distribution</h3>
                    <p class="mt-1 text-sm text-slate-500">Breakdown of users by role.</p>
                </div>
            </div>
            <div class="mt-5 h-72">
                <canvas id="dashboard-role-chart" class="w-full h-full"></canvas>
            </div>
        </article>
    </section>

    <section class="table-card px-5 py-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-semibold text-slate-100">Recently created users</h3>
                <p class="mt-1 text-sm text-slate-500">A quick snapshot of the latest accounts in the system.</p>
            </div>
        </div>

        <div class="mt-4 divide-y divide-white/[0.055]">
            @foreach ($latestUsers as $user)
                <div class="flex items-center justify-between gap-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-slate-100">{{ $user->name }}</p>
                        <p class="text-xs text-slate-500">{{ $user->email }}</p>
                    </div>
                    <span class="badge-role">{{ $user->role }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.initializeDashboardCharts?.({
                roleLabels: @json(array_keys($roleCounts)),
                roleValues: @json(array_values($roleCounts)),
                signupLabels: @json($signupLabels),
                signupValues: @json($signupCounts),
            });
        });
    </script>
</div>
