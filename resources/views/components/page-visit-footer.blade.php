@if ($pageKey !== null)
    <div
        class="cn-page-visits cn-page-visits--{{ $variant }}"
        role="status"
        aria-label="Contador de visitas de esta página"
    >
        Visitas a esta página: <strong>{{ number_format($hits, 0, ',', '.') }}</strong>
    </div>
@endif
