<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Resultados de Laboratorio — Clínica Norte</title>
    @include('reportes._partials.pdf-styles')
</head>
<body>
    @include('reportes._partials.pdf-header', ['reportTitle' => 'Resultados de Laboratorio: Proceso y Tiempos'])
    @include('reportes._partials.pdf-metadata', ['periodLabel' => 'Periodo (órdenes creadas en)'])

    <div class="info-box">
        Estados derivados: <strong>Validado</strong> (fecha de validación registrada),
        <strong>En proceso</strong> (muestra en análisis o procesada sin validar),
        <strong>Pendiente</strong> (resto). Tiempo desde recepción de muestra hasta validación o hasta la generación del reporte si aún no valida.
    </div>

    @include('reportes._partials.pdf-capped')

    <table class="data-table">
        <thead>
        <tr>
            <th>· Orden</th>
            <th>· Paciente</th>
            <th>· Examen</th>
            <th>· Área</th>
            <th>· Recepción muestra</th>
            <th>· Tiempo</th>
            <th>· Estado</th>
        </tr>
        </thead>
        <tbody>
        @php $tz = config('clinic_bank.timezone', config('app.timezone')); @endphp
        @forelse($rows as $r)
            @php
                $patient = trim(($r->patient_first_name ?? '').' '.($r->patient_last_name ?? ''));
                $col = $r->collected_at ? \Carbon\Carbon::parse($r->collected_at)->timezone($tz) : null;
                $val = $r->validated_at ? \Carbon\Carbon::parse($r->validated_at)->timezone($tz) : null;
                $sampleSt = $r->sample_status;
                $endAt = $val ?? $generatedAt->copy()->timezone($tz);

                if ($val) {
                    $estado = 'Validado';
                    $badgeVariant = 'success';
                    $mins = $col ? $col->diffInMinutes($val) : null;
                } elseif (in_array($sampleSt, ['en_analisis', 'procesada'], true)) {
                    $estado = 'En proceso';
                    $badgeVariant = 'info';
                    $mins = $col ? $col->diffInMinutes($endAt) : null;
                } else {
                    $estado = 'Pendiente';
                    $badgeVariant = 'warning';
                    $mins = $col ? $col->diffInMinutes($endAt) : null;
                }

                if ($mins !== null) {
                    $h = intdiv((int) $mins, 60);
                    $m = (int) $mins % 60;
                    $tiempo = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
                } else {
                    $tiempo = '—';
                }

                $recepcion = $col ? $col->format('d/m/Y H:i') : '—';
            @endphp
            <tr class="{{ $loop->even ? 'row-even' : 'row-alt' }}">
                <td>{{ $r->order_number }}</td>
                <td>{{ $patient !== '' ? $patient : '—' }}</td>
                <td>{{ $r->exam_name }}</td>
                <td>{{ $r->category_name ?? '—' }}</td>
                <td>{{ $recepcion }}</td>
                <td>{{ $tiempo }}</td>
                <td>@include('reportes._partials.badge', ['label' => $estado, 'variant' => $badgeVariant])</td>
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
