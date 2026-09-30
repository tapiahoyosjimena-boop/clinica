@if ($paginator->hasPages())
    <nav>
        @if ($paginator->onFirstPage())
            <span class="page-link" style="opacity:.4;cursor:default;">&#8592; Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="page-link">&#8592; Anterior</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="page-link" style="border:none;">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="page-link active">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="page-link">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="page-link">Siguiente &#8594;</a>
        @else
            <span class="page-link" style="opacity:.4;cursor:default;">Siguiente &#8594;</span>
        @endif
    </nav>
@endif
