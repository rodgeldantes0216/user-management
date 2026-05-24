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
        <div class="mx-auto min-h-screen max-w-[1680px] px-3 py-4 lg:px-6">
            <div class="panel flex min-h-[calc(100vh-2rem)] flex-col overflow-hidden lg:flex-row">
            <aside class="border-r border-slate-800 px-5 py-5 lg:sticky lg:top-0 lg:h-[calc(100vh-2rem)] lg:w-[28rem] lg:shrink-0 lg:px-7 lg:py-6">
                <div class="flex h-full flex-col">
                    <div>
                        <div class="rounded-[2rem] bg-[#020617] px-8 py-9 text-white">
                            <p class="section-kicker">Workspace</p>
                            <h1 class="mt-6 text-[2.15rem] font-semibold uppercase tracking-[0.03em]">{{ \App\Support\Settings::all()['site_name'] }}</h1>
                        </div>
                    </div>

                    <livewire:layout.sidebar-nav />

                    <div class="sidebar-card mt-8 px-6 py-7">
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-500">Signed in as</p>
                        <p class="mt-5 text-3xl font-semibold text-slate-100">{{ auth()->user()->name }}</p>
                        <p class="mt-1 text-lg text-slate-500">{{ auth()->user()->email }}</p>
                        <span class="badge-role mt-6">
                            {{ auth()->user()->primaryRoleName() }}
                        </span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="mt-auto pt-8">
                        @csrf
                        <button type="submit" class="btn-secondary w-full">Sign out</button>
                    </form>
                </div>
            </aside>

            <main class="min-w-0 flex-1 bg-[#17191b] px-4 py-4 lg:px-8 lg:py-6">
                {{ $slot }}
            </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
