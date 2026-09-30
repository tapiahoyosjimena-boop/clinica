@props([
    'unreadCount' => 0,
    'recent' => null,
    'viewAllRoute' => '#',
    'markAllReadRoute' => '#',
    'deleteAllRoute' => '#',
    'openRouteName' => 'doctor.notifications.open',
])

@php
    $recent = $recent ?? collect();
@endphp

<div class="pp-bell-wrap" data-portal-bell>
    <button type="button" class="pp-bell-btn" data-bell-toggle aria-expanded="false" aria-haspopup="true" aria-label="Notificaciones">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M4 8a6 6 0 1 1 12 0c0 1.887.454 3.665 1.257 5.234a.75.75 0 0 1-.515 1.076 32.91 32.91 0 0 1-3.256.508 3.5 3.5 0 0 1-6.972 0 32.903 32.903 0 0 1-3.256-.508.75.75 0 0 1-.515-1.076A11.448 11.448 0 0 0 4 8Zm6 7c-.655 0-1.246-.268-1.669-.698a31.55 31.55 0 0 0 3.338 0A1.998 1.998 0 0 1 10 15Z" clip-rule="evenodd"/>
        </svg>
        @if($unreadCount > 0)
            <span class="pp-bell-badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </button>
    <div class="pp-bell-panel" data-bell-panel hidden>
        <div class="pp-bell-panel-header">Notificaciones</div>
        <div class="pp-bell-panel-list">
            @forelse($recent as $notification)
                @php
                    $raw = $notification->data;
                    $data = is_array($raw) ? $raw : (json_decode($raw, true) ?: []);
                    $title = $data['title'] ?? null;
                    $body = $data['body'] ?? $data['message'] ?? null;
                    $line = $body ? ($title ? $title.' — '.$body : $body) : ($title ?: 'Sin descripción');
                @endphp
                <a href="{{ route($openRouteName, $notification->id) }}" class="pp-bell-item {{ $notification->read_at ? '' : 'unread' }}">{{ $line }}
                    <time>{{ $notification->created_at->format('d/m/Y H:i') }}</time>
                </a>
            @empty
                <p class="pp-bell-empty">Sin notificaciones recientes.</p>
            @endforelse
        </div>
        <div class="pp-bell-panel-footer">
            <a href="{{ $viewAllRoute }}" class="pp-bell-link">Ver todas</a>
            @if($unreadCount > 0)
                <form method="POST" action="{{ $markAllReadRoute }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="pp-bell-footer-btn">Marcar leídas</button>
                </form>
            @endif
            @if($recent->isNotEmpty() || $unreadCount > 0)
                <form method="POST" action="{{ $deleteAllRoute }}" style="margin:0;" onsubmit="return confirm('¿Eliminar todas sus notificaciones? Esta acción no se puede deshacer.');">
                    @csrf
                    <button type="submit" class="pp-bell-footer-btn danger">Eliminar todas</button>
                </form>
            @endif
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-portal-bell]').forEach(function (wrap) {
                    var btn = wrap.querySelector('[data-bell-toggle]');
                    var panel = wrap.querySelector('[data-bell-panel]');
                    if (!btn || !panel) return;
                    btn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        var open = panel.hasAttribute('hidden');
                        document.querySelectorAll('[data-bell-panel]').forEach(function (p) {
                            p.setAttribute('hidden', '');
                        });
                        document.querySelectorAll('[data-bell-toggle]').forEach(function (b) {
                            b.setAttribute('aria-expanded', 'false');
                        });
                        if (open) {
                            panel.removeAttribute('hidden');
                            btn.setAttribute('aria-expanded', 'true');
                        }
                    });
                });
                document.addEventListener('click', function () {
                    document.querySelectorAll('[data-bell-panel]').forEach(function (p) {
                        p.setAttribute('hidden', '');
                    });
                    document.querySelectorAll('[data-bell-toggle]').forEach(function (b) {
                        b.setAttribute('aria-expanded', 'false');
                    });
                });
                document.querySelectorAll('[data-bell-panel]').forEach(function (panel) {
                    panel.addEventListener('click', function (e) {
                        e.stopPropagation();
                    });
                });
            });
        </script>
    @endpush
@endonce
