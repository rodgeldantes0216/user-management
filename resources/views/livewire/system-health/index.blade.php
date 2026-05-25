<div class="space-y-4">
    <section class="content-panel px-5 py-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="section-kicker">Operations</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-100">System Health</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Live checks for queues, failed jobs, mail, database, cache, storage, and security posture.
                </p>
            </div>

            <div class="text-sm text-slate-500">
                Last checked {{ $lastCheckedAt->format('M d, Y h:i A') }}
            </div>
        </div>
    </section>

    <section class="grid gap-3 md:grid-cols-3">
        <article class="content-panel px-5 py-4">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Healthy</p>
            <p class="mt-3 text-2xl font-semibold text-emerald-300">{{ $healthyCount }}</p>
        </article>
        <article class="content-panel px-5 py-4">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Warnings</p>
            <p class="mt-3 text-2xl font-semibold text-amber-300">{{ $warningCount }}</p>
        </article>
        <article class="content-panel px-5 py-4">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Critical</p>
            <p class="mt-3 text-2xl font-semibold text-rose-300">{{ $criticalCount }}</p>
        </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-[0.75fr_1.25fr]">
        <article class="content-panel px-5 py-4">
            <h3 class="text-base font-semibold text-slate-100">Runtime</h3>
            <div class="mt-4 divide-y divide-white/[0.055]">
                @foreach ($runtime as $label => $value)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <span class="text-sm text-slate-500">{{ $label }}</span>
                        <span class="text-sm font-medium text-slate-100">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="table-card px-5 py-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-100">Health checks</h3>
                    <p class="mt-1 text-sm text-slate-500">Grouped status checks for core application services.</p>
                </div>
            </div>

            <div class="mt-4 divide-y divide-white/[0.055]">
                @foreach ($checks as $check)
                    @php
                        $statusClasses = [
                            'healthy' => 'bg-emerald-400 text-emerald-950',
                            'warning' => 'bg-amber-300 text-amber-950',
                            'critical' => 'bg-rose-400 text-rose-950',
                        ][$check['status']];
                    @endphp
                    <div class="grid gap-3 py-4 lg:grid-cols-[0.7fr_1.3fr_auto] lg:items-center">
                        <div>
                            <p class="text-sm font-semibold text-slate-100">{{ $check['name'] }}</p>
                            <p class="mt-0.5 text-xs uppercase tracking-[0.16em] text-slate-500">{{ $check['group'] }}</p>
                        </div>
                        <p class="text-sm leading-6 text-slate-400">{{ $check['message'] }}</p>
                        <span class="inline-flex w-fit rounded-md px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] {{ $statusClasses }}">
                            {{ $check['status'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </article>
    </section>
</div>
