<div class="space-y-4">
    <section class="content-panel px-5 py-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="section-kicker">Insights</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-100">Reports / Analytics</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Operational snapshots for account growth, permission coverage, login activity, and system readiness.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('dashboard') }}" wire:navigate class="btn-secondary">Back to Dashboard</a>
                @can('activities.view')
                    <a href="{{ route('activities.index') }}" wire:navigate class="btn-primary">Open Audit Trail</a>
                @endcan
            </div>
        </div>
    </section>

    <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <article class="content-panel px-5 py-4">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">User growth</p>
            <div class="mt-3 flex items-end justify-between gap-3">
                <p class="text-2xl font-semibold text-slate-100">{{ number_format($totalUsers) }}</p>
                <span class="badge-role">{{ $newUsersToday }} today</span>
            </div>
            <p class="mt-3 text-xs leading-5 text-slate-500">Total registered users across the workspace.</p>
        </article>

        <article class="content-panel px-5 py-4">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Permission usage</p>
            <div class="mt-3 flex items-end justify-between gap-3">
                <p class="text-2xl font-semibold text-slate-100">{{ number_format($permissionCount) }}</p>
                <span class="badge-role">{{ $activeRoles }} roles</span>
            </div>
            <p class="mt-3 text-xs leading-5 text-slate-500">Permissions currently available to role assignments.</p>
        </article>

        <article class="content-panel px-5 py-4">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Login trends</p>
            <div class="mt-3 flex items-end justify-between gap-3">
                <p class="text-2xl font-semibold text-slate-100">{{ array_sum($loginValues) }}</p>
                <span class="badge-role">14 days</span>
            </div>
            <p class="mt-3 text-xs leading-5 text-slate-500">
                Latest login:
                {{ $latestLogin?->created_at?->diffForHumans() ?? 'No sign-ins logged yet' }}
            </p>
        </article>

        <article class="content-panel px-5 py-4">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">System health</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="badge-role">{{ $auditEnabled ? 'Audit on' : 'Audit off' }}</span>
                <span class="badge-role">{{ $notificationsEnabled ? 'Notify on' : 'Notify off' }}</span>
            </div>
            <p class="mt-3 text-xs leading-5 text-slate-500">{{ $presenceValues[0] }} users currently active.</p>
        </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
        <article class="content-panel px-5 py-4">
            <div>
                <h3 class="text-base font-semibold text-slate-100">User growth</h3>
                <p class="mt-1 text-sm text-slate-500">New users over the last 30 days.</p>
            </div>
            <div class="mt-5 h-72">
                <canvas id="reports-user-growth-chart" class="h-full w-full"></canvas>
            </div>
        </article>

        <article class="content-panel px-5 py-4">
            <div>
                <h3 class="text-base font-semibold text-slate-100">User presence</h3>
                <p class="mt-1 text-sm text-slate-500">Online, idle, and logged-out account status.</p>
            </div>
            <div class="mt-5 h-72">
                <canvas id="reports-presence-chart" class="h-full w-full"></canvas>
            </div>
        </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
        <article class="content-panel px-5 py-4">
            <div>
                <h3 class="text-base font-semibold text-slate-100">Permission groups</h3>
                <p class="mt-1 text-sm text-slate-500">Permission catalog size by module group.</p>
            </div>
            <div class="mt-5 h-72">
                <canvas id="reports-permission-chart" class="h-full w-full"></canvas>
            </div>
        </article>

        <article class="content-panel px-5 py-4">
            <div>
                <h3 class="text-base font-semibold text-slate-100">Login activity</h3>
                <p class="mt-1 text-sm text-slate-500">Successful sign-ins captured by the audit trail.</p>
            </div>
            <div class="mt-5 h-72">
                <canvas id="reports-login-chart" class="h-full w-full"></canvas>
            </div>
        </article>

        <article class="table-card px-5 py-4">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-100">Top permission usage</h3>
                    <p class="mt-1 text-sm text-slate-500">Permissions assigned to the most roles.</p>
                </div>
            </div>

            <div class="mt-4 divide-y divide-white/[0.055]">
                @forelse ($permissionUsage as $permission)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-100">{{ $permission->label }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $permission->name }}</p>
                        </div>
                        <span class="badge-role shrink-0">{{ $permission->role_count }} roles</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-500">No permissions available yet.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="table-card px-5 py-4">
        <div>
            <h3 class="text-base font-semibold text-slate-100">Recent system activity</h3>
            <p class="mt-1 text-sm text-slate-500">Latest audit events feeding the analytics module.</p>
        </div>

        <div class="mt-4 divide-y divide-white/[0.055]">
            @forelse ($recentActivity as $activity)
                <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-100">{{ $activity->action }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $activity->name }} / {{ $activity->email }}</p>
                    </div>
                    <span class="text-xs text-slate-500">{{ $activity->created_at->format('M d, Y h:i A') }}</span>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-slate-500">No activity has been recorded yet.</p>
            @endforelse
        </div>
    </section>

    <script>
        window.initializeReportsCharts?.({
            growthLabels: @json($growthLabels),
            growthValues: @json($growthValues),
            permissionLabels: @json($permissionLabels),
            permissionValues: @json($permissionValues),
            presenceLabels: @json($presenceLabels),
            presenceValues: @json($presenceValues),
            loginLabels: @json($loginLabels),
            loginValues: @json($loginValues),
        });
    </script>
</div>
