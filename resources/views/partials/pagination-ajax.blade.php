@if ($paginator->hasPages())
    <div class="pagination-wrap">
        <div class="pagination-summary">
            Menampilkan {{ $paginator->firstItem() ?? 0 }} - {{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }} data
        </div>
        <div class="pagination">
            @if ($paginator->onFirstPage())
                <span class="page-link-disabled">&laquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="page-link" data-ajax-link="true" data-refresh-target="{{ $target ?? '#ajaxCrudFragment' }}">&laquo;</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="page-link-disabled">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="page-link-active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="page-link" data-ajax-link="true" data-refresh-target="{{ $target ?? '#ajaxCrudFragment' }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="page-link" data-ajax-link="true" data-refresh-target="{{ $target ?? '#ajaxCrudFragment' }}">&raquo;</a>
            @else
                <span class="page-link-disabled">&raquo;</span>
            @endif
        </div>
    </div>
@endif
