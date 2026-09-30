<nav class="pp-nav">
    @can(\App\Domains\Auth\Support\SystemPermissions::PATIENT_DASHBOARD)
    <a href="{{ route('patient.dashboard') }}"
       class="{{ request()->routeIs('patient.dashboard') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v6a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-6H3a1 1 0 0 1-.707-1.707l7-7Z" clip-rule="evenodd"/>
        </svg>
        Inicio
    </a>
    @endcan
    @can(\App\Domains\Auth\Support\SystemPermissions::PATIENT_RESULTS)
    <a href="{{ route('results.patient.portal') }}"
       class="{{ request()->routeIs('results.patient.portal') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4 4a2 2 0 0 1 2-2h4.586A2 2 0 0 1 12 2.586L15.414 6A2 2 0 0 1 16 7.414V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4Zm2 6a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1Zm1 3a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H7Z" clip-rule="evenodd"/>
        </svg>
        Mis Resultados
    </a>
    @endcan
    @can(\App\Domains\Auth\Support\SystemPermissions::PATIENT_PAYMENTS)
    <a href="{{ route('payments.patient.portal') }}"
       class="{{ request()->routeIs('payments.patient.portal') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M2.5 4A1.5 1.5 0 0 0 1 5.5V6h18v-.5A1.5 1.5 0 0 0 17.5 4h-15ZM19 8.5H1v6A1.5 1.5 0 0 0 2.5 16h15a1.5 1.5 0 0 0 1.5-1.5v-6ZM3 13.25a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 0 1.5h-1.5a.75.75 0 0 1-.75-.75Zm4.25-.75a.75.75 0 0 0 0 1.5h3a.75.75 0 0 0 0-1.5h-3Z" clip-rule="evenodd"/>
        </svg>
        Mis Comprobantes
    </a>
    @endcan
    @can(\App\Domains\Auth\Support\SystemPermissions::PATIENT_NOTIFICATIONS)
    <a href="{{ route('patient.notifications') }}"
       class="{{ request()->routeIs('patient.notifications') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4 8a6 6 0 1 1 12 0c0 1.887.454 3.665 1.257 5.234a.75.75 0 0 1-.515 1.076 32.91 32.91 0 0 1-3.256.508 3.5 3.5 0 0 1-6.972 0 32.903 32.903 0 0 1-3.256-.508.75.75 0 0 1-.515-1.076A11.448 11.448 0 0 0 4 8Zm6 7c-.655 0-1.246-.268-1.669-.698a31.55 31.55 0 0 0 3.338 0A1.998 1.998 0 0 1 10 15Z" clip-rule="evenodd"/>
        </svg>
        Notificaciones
    </a>
    @endcan
    @can(\App\Domains\Auth\Support\SystemPermissions::PATIENT_PROFILE)
    <a href="{{ route('patient.profile') }}"
       class="{{ request()->routeIs('patient.profile*') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0 0-13.074.003Z"/>
        </svg>
        Mi Perfil
    </a>
    @endcan
</nav>
