<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ? $title . ' | ' . config('app.name') : config('app.name') }}</title>
        <script>
            (function(){
                try {
                    var m = document.cookie.match(/(^|;)\s*theme=([^;]+)/);
                    var theme = m ? decodeURIComponent(m[2]) : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    if (theme === 'dark' || theme === 'light') {
                        document.documentElement.classList.add(theme === 'dark' ? 'dark' : 'light');
                        document.documentElement.style.colorScheme = theme;
                    }
                } catch (e) {}
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="app-shell min-h-screen">
        <div class="mx-auto min-h-screen max-w-[1760px]">
            <div class="flex min-h-screen flex-col lg:flex-row">
            <aside class="border-r sidebar-shell px-4 py-4 backdrop-blur-xl lg:sticky lg:top-0 lg:h-screen lg:w-72 lg:shrink-0 lg:overflow-y-auto">
                <div class="flex min-h-full flex-col">
                    <div>
                        <div class="workspace-card rounded-xl border px-4 py-4">
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
                        <div class="mt-4 rounded-2xl border border-white/[0.06] bg-white/[0.025] p-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500">Theme</p>
                                    <p class="mt-1 text-sm text-slate-400">Switch the interface mode.</p>
                                </div>
                                <button id="themeToggle" type="button" class="btn-secondary inline-flex items-center gap-2" aria-label="Toggle light and dark mode">
                                    <span id="themeToggleIcon">🌙</span>
                                    <span id="themeToggleLabel">Dark mode</span>
                                </button>
                            </div>
                        </div>
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
