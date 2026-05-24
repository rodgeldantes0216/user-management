<div>
    <div class="mb-8">
        <p class="text-sm uppercase tracking-[0.3em] text-brand-600">Create account</p>
        <h2 class="mt-3 text-3xl font-semibold text-slate-950">Join the workspace</h2>
        <p class="mt-3 text-sm leading-6 text-slate-500">
            New accounts are created as regular users. Admin access is controlled from the user management module.
        </p>
    </div>

    <form wire:submit="register" class="space-y-5">
        <div>
            <label for="name" class="label">Full name</label>
            <input id="name" type="text" wire:model="name" class="input" placeholder="Jane Doe">
            @error('name') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="label">Email address</label>
            <input id="email" type="email" wire:model="email" class="input" placeholder="jane@example.com">
            @error('email') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="label">Password</label>
            <input id="password" type="password" wire:model="password" class="input" placeholder="Minimum 8 characters">
            @error('password') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="label">Confirm password</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" class="input" placeholder="Repeat your password">
        </div>

        <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="register">Create account</span>
            <span wire:loading wire:target="register">Creating account...</span>
        </button>
    </form>

    <p class="mt-8 text-sm text-slate-500">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-600 hover:text-brand-700">Sign in</a>
    </p>
</div>
