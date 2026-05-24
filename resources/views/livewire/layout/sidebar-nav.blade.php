<nav wire:poll.15s class="mt-4 space-y-1">
    @foreach ($items as $item)
        <a href="{{ route($item['route'], $item['route_params'] ?? []) }}" wire:navigate class="nav-link {{ request()->routeIs($item['route']) && (($item['route_params'][0] ?? null) === request()->route('module') || ! isset($item['route_params'])) ? 'nav-link-active' : '' }}">
            <span>{{ $item['label'] }}</span>
            @if ($item['badge'] > 0)
                <span class="ml-auto inline-flex min-w-5 items-center justify-center rounded-md bg-brand-600/90 px-1.5 py-0.5 text-[10px] font-semibold text-white">
                    {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                </span>
            @endif
        </a>
    @endforeach
</nav>
