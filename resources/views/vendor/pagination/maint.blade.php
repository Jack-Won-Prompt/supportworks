@if ($paginator->hasPages())
    @php
        $btnBase = 'inline-flex items-center justify-center min-w-[34px] h-[34px] px-2 text-sm rounded-lg border transition-colors';
        $btnIdle = 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900';
        $btnActive = 'bg-indigo-600 border-indigo-600 text-white font-semibold shadow-sm';
        $btnDisabled = 'bg-white border-gray-100 text-gray-300 cursor-not-allowed';
        $arrow = 'w-4 h-4';
    @endphp

    <nav role="navigation" aria-label="Pagination" class="flex items-center gap-1.5">

        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="{{ $btnBase }} {{ $btnDisabled }}" aria-disabled="true">
                <svg class="{{ $arrow }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $btnBase }} {{ $btnIdle }}" aria-label="Previous">
                <svg class="{{ $arrow }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
        @endif

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="inline-flex items-center justify-center min-w-[34px] h-[34px] text-sm text-gray-400">…</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="{{ $btnBase }} {{ $btnActive }}">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="{{ $btnBase }} {{ $btnIdle }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $btnBase }} {{ $btnIdle }}" aria-label="Next">
                <svg class="{{ $arrow }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        @else
            <span class="{{ $btnBase }} {{ $btnDisabled }}" aria-disabled="true">
                <svg class="{{ $arrow }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </span>
        @endif

    </nav>
@endif
