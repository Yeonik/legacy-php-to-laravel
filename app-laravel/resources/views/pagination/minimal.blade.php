{{-- Plain text pagination — no framework CSS, no SVG chevrons. Styled by
     public/css/app.css. Replaces Laravel's default Tailwind view, whose
     unstyled arrows render as oversized SVGs without a build step. --}}
@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="pagination__link is-disabled" aria-disabled="true">&lsaquo; Prev</span>
        @else
            <a class="pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo; Prev</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination__ellipsis">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination__link is-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pagination__link" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next &rsaquo;</a>
        @else
            <span class="pagination__link is-disabled" aria-disabled="true">Next &rsaquo;</span>
        @endif
    </nav>
@endif
