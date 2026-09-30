<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Estudios de Imagen e Informes — Clínica Norte</title>
    @include('reportes._partials.pdf-styles')
</head>
<body>
    @include('reportes._partials.pdf-header', ['reportTitle' => 'Estudios de Imagen e Informes'])
    @include('reportes._partials.pdf-metadata', ['periodLabel' => 'Periodo (alta del estudio)'])

    <div class="info-box">
        Estado del informe (derivado): <strong>Publicado</strong> si el resultado fue publicado al portal;
        <strong>Listo</strong> si hay validación de resultado o el estudio figura completado sin publicación aún;
        <strong>Pendiente</strong> en otro caso.
    </div>

    @include('reportes._partials.pdf-capped')

    <table class="data-table">
        <thead>
        <tr>
            <th>· Paciente</th>
            <th>· Estudio</th>
            <th>· Fecha</th>
            <th>· Equipo (tipo)</th>
            <th>· Tecnólogo</th>
            <th>· Estado estudio</th>
            <th>· Informe</th>
        </tr>
        </thead>
        <tbody>
        @php $tz = config('clinic_bank.timezone', config('app.timezone')); @endphp
        @forelse($rows as $r)
            @php
                $patient = trim(($r->patient_first_name ?? '').' '.($r->patient_last_name ?? ''));
                $dt = $r->collected_at
                    ? \Carbon\Carbon::parse($r->collected_at)->timezone($tz)
                    : ($r->study_created_at ? \Carbon\Carbon::parse($r->study_created_at)->timezone($tz) : null);
                $fecha = $dt ? $dt->format('d/m/Y H:i') : '—';
                $eq = $r->equipment_type ?? '';
                $eqLabel = match ($eq) {
                    'ecógrafo' => 'Ecografía',
                    'rayos_x' => 'Rayos X',
                    'tomógrafo' => 'Tomografía',
                    'otro' => 'Otro / equipo',
                    default => $eq !== '' ? $eq : '—',
                };
                $studyKey = $r->study_status ?? '';
                $studySt = match ($studyKey) {
                    'programado' => 'Programado',
                    'paciente_presente' => 'Paciente presente',
                    'en_proceso' => 'En proceso',
                    'completado' => 'Completado',
                    'cancelado' => 'Cancelado',
                    default => $studyKey !== '' ? $studyKey : '—',
                };
                $studyBadge = match ($studyKey) {
                    'completado' => 'success',
                    'en_proceso', 'paciente_presente' => 'info',
                    'programado' => 'warning',
                    'cancelado' => 'danger',
                    default => 'neutral',
                };
                $pub = $r->published_to_portal_at;
                $rv = $r->result_validated_at;
                if ($pub) {
                    $inf = 'Publicado';
                    $infBadge = 'success';
                } elseif ($rv || $studyKey === 'completado') {
                    $inf = 'Listo';
                    $infBadge = 'info';
                } else {
                    $inf = 'Pendiente';
                    $infBadge = 'warning';
                }
            @endphp
            <tr class="{{ $loop->even ? 'row-even' : 'row-alt' }}">
                <td>{{ $patient !== '' ? $patient : '—' }}</td>
                <td>{{ $r->exam_name }}</td>
                <td>{{ $fecha }}</td>
                <td>{{ $eqLabel }}</td>
                <td>{{ $r->technologist_name ?? '—' }}</td>
                <td>@include('reportes._partials.badge', ['label' => $studySt, 'variant' => $studyBadge])</td>
                <td>@include('reportes._partials.badge', ['label' => $inf, 'variant' => $infBadge])</td>
            </tr>
        @empty
            <tr class="empty-row">
                <td colspan="7">No hay registros en el periodo seleccionado.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    @include('reportes._partials.pdf-footer')
</body>
</html>
