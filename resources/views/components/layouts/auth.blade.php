<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ? $title . ' | ' . config('app.name') : config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen app-shell">
        <div class="relative isolate flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,_rgba(59,109,244,0.12),transparent_32%),radial-gradient(circle_at_bottom,_rgba(14,165,233,0.14),transparent_24%)]"></div>

            <div class="grid w-full max-w-5xl gap-6 lg:grid-cols-[1fr_0.78fr]">
                <section class="hidden rounded-xl border border-white/[0.06] bg-slate-950/80 px-7 py-8 text-white shadow-2xl lg:flex lg:flex-col lg:justify-between">
                    <div>
                        <p class="section-kicker">User Management</p>
                        <h1 class="mt-4 max-w-md text-3xl font-semibold leading-tight">Clean workspace for Auth and Admin control.</h1>
                        <p class="mt-4 max-w-xl text-sm leading-6 text-slate-400">
                            SPA-style navigation, role-based access, and a modern layout inspired by Flux UI.
                        </p>
                    </div>

                    <!-- <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                            <p class="text-sm text-slate-300">Authentication</p>
                            <p class="mt-2 text-lg font-semibold">Register, sign in, sign out</p>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                            <p class="text-sm text-slate-300">Authorization</p>
                            <p class="mt-2 text-lg font-semibold">Admin-only user module</p>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                            <p class="text-sm text-slate-300">Navigation</p>
                            <p class="mt-2 text-lg font-semibold">Livewire SPA transitions</p>
                        </div>
                    </div> -->

                    <!-- // add ruuning PH time here -->
                    <p class="text-sm text-slate-300">Current PH Time: {{ now()->setTimezone('Asia/Manila')->format('h:i:s A') }}</p>
                </section>

                <section class="panel px-5 py-6 sm:px-7 sm:py-7">
                    {{ $slot }}
                </section>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
