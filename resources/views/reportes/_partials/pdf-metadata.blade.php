@php
    $periodLabel = $periodLabel ?? 'Periodo';
    $filtersText = filled($filterSummary ?? null) ? $filterSummary : 'Sin filtros adicionales';
@endphp
<div class="meta-box">
    <table class="w100">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                <p class="meta-label">· {{ $periodLabel ?? 'Periodo' }}:</p>
                <p class="meta-value">{{ $periodFrom->format('d/m/Y') }} — {{ $periodUntil->format('d/m/Y') }}</p>
                <p class="meta-label">· Solicitado por:</p>
                <p class="meta-value">{{ $requestedBy }}</p>
                <p class="meta-label">· Generado:</p>
                <p class="meta-value meta-value-last">{{ $generatedAt->format('d/m/Y H:i') }}</p>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                <p class="meta-label">· Alcance:</p>
                <p class="meta-value">{{ $roleLabel }}</p>
                <p class="meta-label">· Filtros:</p>
                <p class="meta-value">{{ $filtersText }}</p>
                <p class="meta-label">· Total registros:</p>
                <p class="meta-value meta-value-last">{{ $totalMatching }}</p>
            </td>
        </tr>
    </table>
</div>
