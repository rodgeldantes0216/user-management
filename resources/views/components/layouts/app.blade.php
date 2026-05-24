<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ? $title . ' | ' . config('app.name') : config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="app-shell min-h-screen">
        <div class="mx-auto min-h-screen max-w-[1760px]">
            <div class="flex min-h-screen flex-col lg:flex-row">
            <aside class="border-r border-white/[0.06] bg-[#0d1014]/80 px-4 py-4 backdrop-blur-xl lg:sticky lg:top-0 lg:h-screen lg:w-72 lg:shrink-0 lg:overflow-y-auto">
                <div class="flex min-h-full flex-col">
                    <div>
                        <div class="rounded-xl border border-white/[0.06] bg-[#080b11] px-4 py-4 text-white">
                            <p class="section-kicker">Workspace</p>
                            <h1 class="mt-3 text-lg font-semibold tracking-[0.01em]">{{ \App\Support\Settings::all()['site_name'] }}</h1>
                        </div>
                    </div>

                    <livewire:layout.sidebar-nav />

                    <div class="sidebar-card mt-4 px-3.5 py-3">
                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-600">Signed in</p>
                        <p class="mt-2 truncate text-sm font-semibold text-slate-100">{{ auth()->user()->name }}</p>
                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                        <span class="badge-role mt-3">{{ auth()->user()->primaryRoleName() }}</span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="mt-auto pt-4">
                        @csrf
                        <button type="submit" class="btn-secondary w-full">Sign out</button>
                    </form>
                </div>
            </aside>

            <main class="min-w-0 flex-1 px-4 py-4 lg:px-6 lg:py-5">
                {{ $slot }}
            </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
