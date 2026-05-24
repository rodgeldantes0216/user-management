@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-slate-500">
            @if ($paginator->firstItem())
                <span>{{ $paginator->firstItem() }}-{{ $paginator->lastItem() }}</span>
            @else
                <span>{{ $paginator->count() }}</span>
            @endif
            <span>of {{ $paginator->total() }}</span>
        </div>

        <div class="flex items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-700">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            @else
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:text-slate-100" aria-label="{{ __('pagination.previous') }}">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            @endif

            @php
                $current = $paginator->currentPage();
                $last = $paginator->lastPage();
                $pages = array_values(array_unique(array_filter([
                    1,
                    $current - 1,
                    $current,
                    $current + 1,
                    $last,
                ], fn ($page) => $page >= 1 && $page <= $last)));

                sort($pages);
                $previousPage = null;
            @endphp

            @foreach ($pages as $page)
                @if (! is_null($previousPage) && $page - $previousPage > 1)
                    <span class="px-1 text-sm text-slate-700">...</span>
                @endif

                @if ($page == $current)
                    <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm font-semibold text-white">
                        {{ $page }}
                    </span>
                @else
                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm font-medium text-slate-400 transition hover:text-slate-100" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                        {{ $page }}
                    </button>
                @endif

                @php
                    $previousPage = $page;
                @endphp
            @endforeach

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:text-slate-100" aria-label="{{ __('pagination.next') }}">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            @else
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-700">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
