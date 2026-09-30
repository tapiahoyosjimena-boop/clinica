<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Órdenes y Exámenes — Clínica Norte</title>
    @include('reportes._partials.pdf-styles')
</head>
<body>
    @include('reportes._partials.pdf-header', ['reportTitle' => 'Reporte de Órdenes y Exámenes (Detalle)'])
    @include('reportes._partials.pdf-metadata', ['periodLabel' => 'Periodo (creación de la orden)'])

    <div class="info-box">
        Una fila por examen vinculado a la orden. Estados según la orden en el sistema (pendiente, en proceso, completada, cancelada).
    </div>

    @include('reportes._partials.pdf-capped')

    <table class="data-table">
        <thead>
        <tr>
            <th>· N° Orden</th>
            <th>· Paciente</th>
            <th>· Examen</th>
            <th style="text-align:right;">· Precio</th>
            <th>· Fecha orden</th>
            <th>· Estado orden</th>
            <th>· Motivo cancelación</th>
            <th>· Responsable</th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $r)
            @php
                $statusKey = $r->order_status ?? '';
                $st = match ($statusKey) {
                    'pendiente' => 'Pendiente',
                    'en_proceso' => 'En proceso',
                    'completada' => 'Completada',
                    'cancelada' => 'Cancelada',
                    default => $statusKey !== '' ? $statusKey : '—',
                };
                $badgeVariant = match ($statusKey) {
                    'completada' => 'success',
                    'en_proceso' => 'info',
                    'pendiente' => 'warning',
                    'cancelada' => 'danger',
                    default => 'neutral',
                };
                $patient = trim(($r->patient_first_name ?? '').' '.($r->patient_last_name ?? ''));
                $tz = config('clinic_bank.timezone', config('app.timezone'));
            @endphp
            <tr class="{{ $loop->even ? 'row-even' : 'row-alt' }}">
                <td>{{ $r->order_number }}</td>
                <td>{{ $patient !== '' ? $patient : '—' }}</td>
                <td>{{ $r->exam_name }}</td>
                <td style="text-align:right;">{{ number_format((float) ($r->exam_price ?? 0), 2, ',', '.') }} {{ config('clinic_bank.currency', 'BOB') }}</td>
                <td>{{ $r->order_created_at ? \Carbon\Carbon::parse($r->order_created_at)->timezone($tz)->format('d/m/Y H:i') : '—' }}</td>
                <td>@include('reportes._partials.badge', ['label' => $st, 'variant' => $badgeVariant])</td>
                <td>{{ $statusKey === 'cancelada' && filled($r->order_cancellation_reason ?? null) ? $r->order_cancellation_reason : '—' }}</td>
                <td>{{ $r->responsible_name ?? '—' }}</td>
            </tr>
        @empty
            <tr class="empty-row">
                <td colspan="8">No hay registros en el periodo seleccionado.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    @include('reportes._partials.pdf-footer')
</body>
</html>
