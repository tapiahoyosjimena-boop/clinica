@extends('doctor-portal.layouts.app')

@section('title', 'Inicio')

@section('content')
    <x-home-search variant="portal" style="margin-bottom:1.25rem;max-width:36rem;" />

    <h1 class="pp-page-title">Bienvenido, Dr. {{ auth()->user()->name }}</h1>
    <p class="pp-page-sub">Resumen de sus pacientes derivados y actividad reciente.</p>

    {{-- Tarjetas de estadísticas --}}
    <div class="dp-stats">
        <div class="dp-stat-card">
            <div class="dp-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0"/>
                </svg>
            </div>
            <div>
                <div class="dp-stat-value">{{ $totalPatients }}</div>
                <div class="dp-stat-label">Pacientes derivados</div>
            </div>
        </div>

        <div class="dp-stat-card">
            <div class="dp-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/>
                </svg>
            </div>
            <div>
                <div class="dp-stat-value">{{ $pendingOrders }}</div>
                <div class="dp-stat-label">Órdenes pendientes de resultado</div>
            </div>
        </div>

        <div class="dp-stat-card">
            <div class="dp-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
            </div>
            <div>
                <div class="dp-stat-value">{{ $readyResults }}</div>
                <div class="dp-stat-label">Resultados listos para revisar</div>
            </div>
        </div>

        <div class="dp-stat-card">
            <div class="dp-stat-icon" style="background:#fef9c3;color:#854d0e;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <div>
                <div class="dp-stat-value">{{ $unreadNotifications }}</div>
                <div class="dp-stat-label">Notificaciones sin leer</div>
            </div>
        </div>
    </div>

    {{-- Accesos rápidos --}}
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-bottom:2rem;">
        <a href="{{ route('doctor.patients') }}" class="btn btn-outline">Ver mis pacientes</a>
        <a href="{{ route('doctor.results') }}" class="btn btn-primary">Ver resultados</a>
        @if($unreadNotifications > 0)
            <a href="{{ route('doctor.notifications') }}" class="btn btn-outline" style="border-color:#d97706;color:#d97706;">
                {{ $unreadNotifications }} notificación{{ $unreadNotifications > 1 ? 'es' : '' }} sin leer
            </a>
        @endif
    </div>

    {{-- Últimas órdenes --}}
    @if($recentOrders->isNotEmpty())
        <p class="pp-page-sub" style="margin-bottom:0.75rem;">Últimas órdenes derivadas</p>
        <div class="pp-card">
            <div style="overflow-x:auto;">
                <table class="pp-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Paciente</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                            <tr>
                                <td style="white-space:nowrap;">{{ $order->created_at->format('d/m/Y') }}</td>
                                <td>{{ $order->patient?->full_name ?? '—' }}</td>
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
        </div>
    @endif
@endsection
