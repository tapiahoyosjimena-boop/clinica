@extends('doctor-portal.layouts.app')

@section('title', 'Resultados')

@section('content')
    <h1 class="pp-page-title">Resultados</h1>
    <p class="pp-page-sub">Resultados validados de sus pacientes derivados. Puede descargar el PDF cuando esté disponible.</p>

    <div class="pp-card">
        @if($results->isEmpty())
            <div class="pp-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                </svg>
                <p>Aún no hay resultados validados para sus pacientes derivados.</p>
                <p style="margin-top:.35rem;font-size:.8rem;">Cuando el laboratorio o imagen confirme un resultado, aparecerá aquí.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="pp-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Paciente</th>
                            <th>Tipo</th>
                            <th>Examen</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                            <tr>
                                <td style="white-space:nowrap;">
                                    {{ $result->validated_at?->format('d/m/Y') ?? $result->created_at->format('d/m/Y') }}
                                </td>
                                <td style="font-weight:500;">{{ $result->order?->patient?->full_name ?? '—' }}</td>
                                <td>
                                    @if($result->isLabType())
                                        <span class="badge badge-blue">Laboratorio</span>
                                    @elseif($result->isImagingType())
                                        <span class="badge badge-green">Imagen</span>
                                    @else
                                        <span class="badge badge-gray">—</span>
                                    @endif
                                </td>
                                <td>{{ $result->exam?->name ?? '—' }}</td>
                                <td>
                                    @if($result->validated_at)
                                        <span class="badge badge-green">Validado</span>
                                    @else
                                        <span class="badge badge-yellow">Pendiente</span>
                                    @endif
                                    @if($result->is_critical)
                                        <span class="badge badge-red" style="margin-left:.35rem;">Crítico</span>
                                    @endif
                                </td>
                                <td>
                                    @if($result->pdf_path)
                                        <a href="{{ route('results.doctor.pdf', $result) }}"
                                           class="btn btn-primary btn-sm"
                                           target="_blank" rel="noopener">
                                            <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M3 17a1 1 0 0 1 1-1h12a1 1 0 1 1 0 2H4a1 1 0 0 1-1-1Zm3.293-7.707a1 1 0 0 1 1.414 0L9 10.586V3a1 1 0 1 1 2 0v7.586l1.293-1.293a1 1 0 1 1 1.414 1.414l-3 3a1 1 0 0 1-1.414 0l-3-3a1 1 0 0 1 0-1.414Z" clip-rule="evenodd"/>
                                            </svg>
                                            Descargar PDF
                                        </a>
                                    @else
                                        <span style="color:#9CA3AF;font-size:.8rem;">No disponible</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($results->hasPages())
                <div class="pp-pagination">
                    {{ $results->links('portals.partials.pagination') }}
                </div>
            @endif
        @endif
    </div>
@endsection
