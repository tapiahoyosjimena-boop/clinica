@extends('doctor-portal.layouts.app')

@section('title', 'Mis Pacientes')

@section('content')
    <h1 class="pp-page-title">Mis Pacientes</h1>
    <p class="pp-page-sub">Listado de órdenes en las que usted figura como médico derivante.</p>

    <div class="pp-card">
        @if($orders->isEmpty())
            <div class="pp-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>
                </svg>
                <p>Aún no tiene pacientes derivados registrados.</p>
                <p style="margin-top:.35rem;font-size:.8rem;">Cuando una orden le asigne como médico derivante, aparecerá aquí.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="pp-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Paciente</th>
                            <th>CI</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td style="white-space:nowrap;">{{ $order->created_at->format('d/m/Y') }}</td>
                                <td style="font-weight:500;">{{ $order->patient?->full_name ?? '—' }}</td>
                                <td>{{ $order->patient?->ci ?? '—' }}</td>
                                <td>
                                    @if($order->type === 'laboratorio')
                                        <span class="badge badge-blue">Laboratorio</span>
                                    @elseif($order->type === 'imagen')
                                        <span class="badge badge-green">Imagen</span>
                                    @else
                                        <span class="badge badge-gray">{{ $order->type ?? '—' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusMap = [
                                            'pendiente'  => ['label' => 'Pendiente',  'class' => 'badge-yellow'],
                                            'en_proceso' => ['label' => 'En proceso', 'class' => 'badge-blue'],
                                            'completada' => ['label' => 'Completada', 'class' => 'badge-green'],
                                            'cancelada'  => ['label' => 'Cancelada',  'class' => 'badge-red'],
                                        ];
                                        $s = $statusMap[$order->status] ?? ['label' => $order->status, 'class' => 'badge-gray'];
                                    @endphp
                                    <span class="badge {{ $s['class'] }}">{{ $s['label'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="pp-pagination">
                    {{ $orders->links('portals.partials.pagination') }}
                </div>
            @endif
        @endif
    </div>
@endsection
