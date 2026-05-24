<nav wire:poll.15s class="mt-10 space-y-3">
    @foreach ($items as $item)
        <a href="{{ route($item['route']) }}" wire:navigate class="nav-link {{ request()->routeIs($item['route']) ? 'nav-link-active' : '' }}">
            <span>{{ $item['label'] }}</span>
            @if ($item['badge'] > 0)
                <span class="ml-auto inline-flex min-w-7 items-center justify-center rounded-full bg-brand-700 px-2 py-1 text-[11px] font-semibold text-white">
                    {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                </span>
            @endif
        </a>
    @endforeach
</nav>
