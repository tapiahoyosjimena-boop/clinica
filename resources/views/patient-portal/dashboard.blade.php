@extends('patient-portal.layouts.app')

@section('title', 'Inicio')

@section('content')
    <div class="pp-dashboard-head">
        <x-home-search
            variant="portal"
            class="pp-home-search"
            placeholder="Buscar en mis órdenes y resultados…"
        />

        <div>
            <h1 class="pp-page-title">Bienvenido/a, {{ $patient->full_name ?? auth()->user()->name }}</h1>
            <p class="pp-page-sub pp-dashboard-head__sub">Bienvenido a tu portal en Clínica Norte.</p>
        </div>
    </div>

    {{-- Tarjetas de estadísticas --}}
    <div class="pd-stats">
        {{-- Resultados disponibles --}}
        <div class="pd-stat-card">
            <div class="pd-stat-icon pd-stat-icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
                </svg>
            </div>
            <div>
                <div class="pd-stat-value">{{ $totalResults }}</div>
                <div class="pd-stat-label">Resultados disponibles</div>
            </div>
        </div>

        {{-- Comprobantes --}}
        <div class="pd-stat-card">
            <div class="pd-stat-icon pd-stat-icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                </svg>
            </div>
            <div>
                <div class="pd-stat-value">{{ $totalInvoices }}</div>
                <div class="pd-stat-label">Comprobantes de pago</div>
            </div>
        </div>

        {{-- Notificaciones sin leer --}}
        <div class="pd-stat-card">
            <div class="pd-stat-icon pd-stat-icon--amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                </svg>
            </div>
            <div>
                <div class="pd-stat-value">{{ $unreadNotifications }}</div>
                <div class="pd-stat-label">Notificaciones sin leer</div>
            </div>
        </div>
    </div>

    {{-- Accesos rápidos --}}
    <div class="pp-quick-actions">
        <a href="{{ route('results.patient.portal') }}" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4 4a2 2 0 0 1 2-2h4.586A2 2 0 0 1 12 2.586L15.414 6A2 2 0 0 1 16 7.414V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4Zm2 6a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1Zm1 3a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H7Z" clip-rule="evenodd"/>
            </svg>
            Ver mis resultados
        </a>
        <a href="{{ route('payments.patient.portal') }}" class="btn btn-outline">
            <svg width="15" height="15" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M2.5 4A1.5 1.5 0 0 0 1 5.5V6h18v-.5A1.5 1.5 0 0 0 17.5 4h-15ZM19 8.5H1v6A1.5 1.5 0 0 0 2.5 16h15a1.5 1.5 0 0 0 1.5-1.5v-6ZM3 13.25a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 0 1.5h-1.5a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd"/>
            </svg>
            Mis comprobantes
        </a>
        @if($unreadNotifications > 0)
            <a href="{{ route('patient.notifications') }}" class="btn btn-outline btn-warn">
                {{ $unreadNotifications }} notificación{{ $unreadNotifications > 1 ? 'es' : '' }} sin leer
            </a>
        @endif
    </div>

    {{-- Últimos resultados --}}
    @if($recentResults->isNotEmpty())
        <p class="pp-page-sub" style="margin-bottom:0.75rem;">Últimos resultados publicados</p>
        <div class="pp-card">
            <div style="overflow-x:auto;">
                <table class="pp-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Examen</th>
                            <th>Orden</th>
                            <th>Descargar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentResults as $result)
                            <tr>
                                <td style="white-space:nowrap;">
                                    {{ $result->published_to_portal_at?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td>{{ $result->exam?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge badge-gray">
                                        {{ $result->order?->order_number ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('results.patient.pdf', $result) }}"
                                       target="_blank"
                                       class="btn btn-primary"
                                       style="padding:0.3rem 0.7rem;font-size:0.75rem;">
                                        <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/>
                                            <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/>
                                        </svg>
                                        PDF
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($totalResults > 3)
                <div style="padding:0.75rem 1rem;border-top:1px solid var(--border);text-align:right;">
                    <a href="{{ route('results.patient.portal') }}" class="btn btn-outline" style="font-size:0.78rem;">
                        Ver todos los resultados ({{ $totalResults }})
                    </a>
                </div>
            @endif
        </div>
    @else
        <div class="pp-card">
            <div class="pp-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                </svg>
                <p>Aún no hay resultados publicados para su cuenta.</p>
                <p style="font-size:0.8rem;margin-top:0.35rem;">Cuando el laboratorio o imagen confirme su resultado, aparecerá aquí.</p>
            </div>
        </div>
    @endif

@endsection
