@extends('doctor-portal.layouts.app')

@section('title', 'Notificaciones')

@section('content')
    <h1 class="pp-page-title">Notificaciones</h1>
    <p class="pp-page-sub">Avisos del sistema sobre sus pacientes derivados.</p>

    @if($notifications->isNotEmpty())
        <div style="margin-bottom:1rem;display:flex;justify-content:flex-end;gap:0.5rem;flex-wrap:wrap;">
            @if($notifications->where('read_at', null)->count() > 0)
                <form method="POST" action="{{ route('doctor.notifications.readAll') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm">Marcar todas como leídas</button>
                </form>
            @endif
            <form method="POST" action="{{ route('doctor.notifications.deleteAll') }}"
                  onsubmit="return confirm('¿Eliminar todas sus notificaciones? Esta acción no se puede deshacer.');">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm" style="border-color:#fecaca;color:#b91c1c;">Eliminar todas</button>
            </form>
        </div>
    @endif

    <div class="pp-card">
        @if($notifications->isEmpty())
            <div class="pp-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                </svg>
                <p>No hay notificaciones registradas.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="pp-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Mensaje</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notifications as $notification)
                            <tr class="{{ is_null($notification->read_at) ? 'notif-unread' : '' }}">
                                <td style="white-space:nowrap;">
                                    <a class="pp-notif-date-link" href="{{ route('doctor.notifications.open', $notification->id) }}">
                                        {{ $notification->created_at->format('d/m/Y H:i') }}
                                    </a>
                                </td>
                                <td>
                                    @php
                                        $data = is_array($notification->data) ? $notification->data : json_decode($notification->data, true);
                                        $data = is_array($data) ? $data : [];
                                        $title = $data['title'] ?? null;
                                        $body  = $data['body'] ?? $data['message'] ?? null;
                                        $message = $body
                                            ? ($title ? $title.' — '.$body : $body)
                                            : ($title ?: 'Sin descripción');
                                    @endphp
                                    <a class="pp-notif-msg-link" href="{{ route('doctor.notifications.open', $notification->id) }}">{{ $message }}</a>
                                </td>
                                <td>
                                    @if(is_null($notification->read_at))
                                        <span class="badge badge-yellow">Sin leer</span>
                                    @else
                                        <span class="badge badge-gray">Leída</span>
                                    @endif
                                </td>
                                <td>
                                    @if(is_null($notification->read_at))
                                        <form method="POST"
                                              action="{{ route('doctor.notifications.read', $notification->id) }}"
                                              style="margin:0;">
                                            @csrf
                                            <button type="submit" class="btn btn-outline btn-sm">Marcar leída</button>
                                        </form>
                                    @else
                                        <span style="color:#9CA3AF;font-size:.78rem;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($notifications->hasPages())
                <div class="pp-pagination">
                    {{ $notifications->links('portals.partials.pagination') }}
                </div>
            @endif
        @endif
    </div>
@endsection
