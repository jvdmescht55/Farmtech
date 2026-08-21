{{-- Locked-token pagination: numeric buttons, active state, prev/next arrows, .card-style hover. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-center gap-1.5 flex-wrap">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="w-9 h-9 rounded-full border border-border text-ink-muted flex items-center justify-center cursor-not-allowed">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}"
               class="w-9 h-9 rounded-full border border-border text-ink-secondary flex items-center justify-center hover:border-mint/40 hover:text-brand-900 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span aria-disabled="true" class="w-9 h-9 flex items-center justify-center text-sm text-ink-muted">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="w-9 h-9 rounded-full bg-mint text-white flex items-center justify-center text-sm font-semibold">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                           class="w-9 h-9 rounded-full border border-border text-ink-secondary flex items-center justify-center text-sm font-medium hover:border-mint/40 hover:text-brand-900 transition">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}"
               class="w-9 h-9 rounded-full border border-border text-ink-secondary flex items-center justify-center hover:border-mint/40 hover:text-brand-900 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        @else
            <span aria-disabled="true" class="w-9 h-9 rounded-full border border-border text-ink-muted flex items-center justify-center cursor-not-allowed">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </span>
        @endif
    </nav>
@endif
