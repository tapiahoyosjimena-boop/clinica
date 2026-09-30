<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        (function () {
            try {
                var k = 'cn_portal_theme';
                var m = localStorage.getItem(k) || 'system';
                var d = m === 'dark' || (m === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', d);
            } catch (e) {}
        })();
    </script>
    <title>@yield('title', 'Portal Médico') — Clínica Norte</title>
    @php
        $cnFaviconVer = is_file(public_path('images/branding/clinica-norte-favicon.svg'))
            ? (string) filemtime(public_path('images/branding/clinica-norte-favicon.svg'))
            : '1';
    @endphp
    <link rel="icon" href="{{ asset('images/branding/clinica-norte-favicon.svg') }}?v={{ $cnFaviconVer }}" type="image/svg+xml">
    @include('portals.partials.layout-styles', ['containerMaxWidth' => '1100px'])
    <x-portal-theme-styles />
    @stack('styles')
</head>
<body>

    @include('portals.partials.header', [
        'homeRoute' => 'doctor.dashboard',
        'brandSub' => 'Portal Médico',
        'logoutRoute' => 'doctor.logout',
        'userDisplayName' => 'Dr. '.(auth()->user()?->name ?? ''),
        'notificationsRoute' => 'doctor.notifications',
        'notificationsReadAllRoute' => 'doctor.notifications.readAll',
        'notificationsDeleteAllRoute' => 'doctor.notifications.deleteAll',
        'notificationsOpenRoute' => 'doctor.notifications.open',
    ])

    @include('portals.partials.nav-doctor')

    <main class="pp-container">
        @if (session('portal_warning'))
            <div class="pp-alert pp-alert--warning" role="alert">{{ session('portal_warning') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="pp-footer">
        <span>Clínica Norte S.R.L. — C/ Warnes Esq. Angel Sandoval — 75030069</span>
        <x-page-visit-footer />
    </footer>

    @stack('scripts')
    <x-portal-theme-script />
</body>
</html>
