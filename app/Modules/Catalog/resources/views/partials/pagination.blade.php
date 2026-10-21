@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
@endphp
@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="disabled"><span aria-hidden="true">←</span></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Page précédente">←</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="disabled"><span>{{ $element }}</span></span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="active" aria-current="page"><span>{{ $page }}</span></span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Page suivante">→</a>
        @else
            <span class="disabled"><span aria-hidden="true">→</span></span>
        @endif
    </nav>
@endif
