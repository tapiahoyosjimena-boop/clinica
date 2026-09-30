<nav class="pp-nav">
    @can(\App\Domains\Auth\Support\SystemPermissions::DOCTOR_DASHBOARD)
    <a href="{{ route('doctor.dashboard') }}"
       class="{{ request()->routeIs('doctor.dashboard') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
            <path d="M2 10a8 8 0 1 1 16 0 8 8 0 0 1-16 0Zm8-3a1 1 0 0 0-.867.5 1 1 0 1 1-1.731-1A3 3 0 0 1 13 10a3 3 0 0 1-2 2.83V13a1 1 0 1 1-2 0v-1a1 1 0 0 1 1-1 1 1 0 1 0 0-2 1 1 0 0 0-1 1 1 1 0 1 1-2 0 3 3 0 0 1 3-3Z"/>
        </svg>
        Inicio
    </a>
    @endcan
    @can(\App\Domains\Auth\Support\SystemPermissions::DOCTOR_PATIENTS)
    <a href="{{ route('doctor.patients') }}"
       class="{{ request()->routeIs('doctor.patients') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM6 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM1.49 15.326a.78.78 0 0 1-.358-.442 3 3 0 0 1 4.308-3.516 6.484 6.484 0 0 0-1.905 3.959c-.023.222-.014.442.025.654a4.97 4.97 0 0 1-2.07-.655ZM16.44 15.98a4.97 4.97 0 0 0 2.07-.654.78.78 0 0 0 .357-.442 3 3 0 0 0-4.308-3.517 6.484 6.484 0 0 1 1.907 3.96 2.32 2.32 0 0 1-.026.654ZM18 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM5.304 16.19a.844.844 0 0 1-.277-.71 5 5 0 0 1 9.947 0 .843.843 0 0 1-.277.71A6.975 6.975 0 0 1 10 18a6.974 6.974 0 0 1-4.696-1.81Z"/>
        </svg>
        Mis Pacientes
    </a>
    @endcan
    @can(\App\Domains\Auth\Support\SystemPermissions::DOCTOR_RESULTS)
    <a href="{{ route('doctor.results') }}"
       class="{{ request()->routeIs('doctor.results') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4 4a2 2 0 0 1 2-2h4.586A2 2 0 0 1 12 2.586L15.414 6A2 2 0 0 1 16 7.414V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4Zm2 6a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1Zm1 3a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H7Z" clip-rule="evenodd"/>
        </svg>
        Resultados
    </a>
    @endcan
    @can(\App\Domains\Auth\Support\SystemPermissions::DOCTOR_NOTIFICATIONS)
    <a href="{{ route('doctor.notifications') }}"
       class="{{ request()->routeIs('doctor.notifications') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4 8a6 6 0 1 1 12 0c0 1.887.454 3.665 1.257 5.234a.75.75 0 0 1-.515 1.076 32.91 32.91 0 0 1-3.256.508 3.5 3.5 0 0 1-6.972 0 32.903 32.903 0 0 1-3.256-.508.75.75 0 0 1-.515-1.076A11.448 11.448 0 0 0 4 8Zm6 7c-.655 0-1.246-.268-1.669-.698a31.55 31.55 0 0 0 3.338 0A1.998 1.998 0 0 1 10 15Z" clip-rule="evenodd"/>
        </svg>
        Notificaciones
    </a>
    @endcan
    @can(\App\Domains\Auth\Support\SystemPermissions::DOCTOR_PROFILE)
    <a href="{{ route('doctor.profile') }}"
       class="{{ request()->routeIs('doctor.profile*') ? 'active' : '' }}">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0 0-13.074.003Z"/>
        </svg>
        Mi Perfil
    </a>
    @endcan
</nav>
