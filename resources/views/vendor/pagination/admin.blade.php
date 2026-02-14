@if ($paginator->hasPages())
    <nav class="pagination pagination-admin" role="navigation" aria-label="Pagination">
        <div class="pagination-info">
            {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} / {{ $paginator->total() ?? 0 }}
        </div>
        <div class="pagination-items">
            @if ($paginator->onFirstPage())
                <span class="pagination-item is-disabled" aria-disabled="true">前へ</span>
            @else
                <a class="pagination-item" href="{{ $paginator->previousPageUrl() }}" rel="prev">前へ</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination-item is-disabled" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-item is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pagination-item" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pagination-item" href="{{ $paginator->nextPageUrl() }}" rel="next">次へ</a>
            @else
                <span class="pagination-item is-disabled" aria-disabled="true">次へ</span>
            @endif
        </div>
    </nav>
@endif
