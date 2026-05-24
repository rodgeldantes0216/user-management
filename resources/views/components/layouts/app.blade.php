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
        <div class="mx-auto flex min-h-screen max-w-7xl flex-col gap-6 px-4 py-4 lg:flex-row lg:px-6">
            <aside class="panel lg:sticky lg:top-4 lg:h-[calc(100vh-2rem)] lg:w-80 lg:p-4">
                <div class="flex h-full flex-col">
                    <div>
                        <div class="rounded-[1.75rem] bg-slate-950 p-6 text-white">
                            <p class="text-sm uppercase tracking-[0.3em] text-brand-200">Workspace</p>
                            <h1 class="mt-4 text-2xl font-semibold">{{ config('app.name') }}</h1>
                            <p class="mt-2 text-sm leading-6 text-slate-300">Simple user management with authentication, authorization, and Livewire SPA navigation.</p>
                        </div>
                    </div>

                    <nav class="mt-6 space-y-2">
                        <a href="{{ route('dashboard') }}" wire:navigate class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">
                            <span>Dashboard</span>
                        </a>

                        @can('viewAny', \App\Models\User::class)
                            <a href="{{ route('users.index') }}" wire:navigate class="nav-link {{ request()->routeIs('users.index') ? 'nav-link-active' : '' }}">
                                <span>Users</span>
                            </a>
                        @endcan
                    </nav>

                    <div class="mt-6 rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Signed in as</p>
                        <p class="mt-3 text-lg font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="text-sm text-slate-500">{{ auth()->user()->email }}</p>
                        <span class="mt-4 inline-flex rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">
                            {{ auth()->user()->role }}
                        </span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="mt-auto pt-6">
                        @csrf
                        <button type="submit" class="btn-secondary w-full">Sign out</button>
                    </form>
                </div>
            </aside>

            <main class="min-w-0 flex-1 py-2">
                {{ $slot }}
            </main>
        </div>

        @livewireScripts
    </body>
</html>
