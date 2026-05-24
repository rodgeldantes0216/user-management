<div>
    <div class="mb-8">
        <p class="text-sm uppercase tracking-[0.3em] text-brand-600">Welcome back</p>
        <h2 class="mt-3 text-3xl font-semibold text-slate-950">Sign in to your account</h2>
        <p class="mt-3 text-sm leading-6 text-slate-500">
            Use the seeded admin account to manage users: <span class="font-semibold">admin@example.com</span> / <span class="font-semibold">password</span>
        </p>
    </div>

    <form wire:submit="login" class="space-y-5">
        <div>
            <label for="email" class="label">Email address</label>
            <input id="email" type="email" wire:model="email" class="input" placeholder="you@example.com">
            @error('email') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="label">Password</label>
            <input id="password" type="password" wire:model="password" class="input" placeholder="Enter your password">
            @error('password') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-3 text-sm text-slate-600">
            <input type="checkbox" wire:model="remember" class="h-4 w-4 rounded border-slate-300 text-brand-500 focus:ring-brand-400">
            <span>Keep me signed in</span>
        </label>

        <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">Sign in</span>
            <span wire:loading wire:target="login">Signing in...</span>
        </button>
    </form>

    <p class="mt-8 text-sm text-slate-500">
        Don’t have an account?
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-brand-600 hover:text-brand-700">Create one</a>
    </p>
</div>
