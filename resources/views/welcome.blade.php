<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Clínica Norte — Laboratorio clínico e imágenes diagnósticas. Acceda al portal de pacientes, médicos derivantes o personal administrativo.">
    <title>Clínica Norte — Laboratorio e imágenes diagnósticas</title>
    @php
        $cnFaviconVer = is_file(public_path('images/branding/clinica-norte-favicon.svg'))
            ? (string) filemtime(public_path('images/branding/clinica-norte-favicon.svg'))
            : '1';
        $welcomeCssVer = is_file(public_path('css/clinica-norte-welcome.css'))
            ? (string) filemtime(public_path('css/clinica-norte-welcome.css'))
            : '1';
        $homeSearchCssVer = is_file(public_path('css/home-search.css'))
            ? (string) filemtime(public_path('css/home-search.css'))
            : '1';
        $homeSearchJsVer = is_file(public_path('js/home-search.js'))
            ? (string) filemtime(public_path('js/home-search.js'))
            : '1';
    @endphp
    <link rel="icon" href="{{ asset('images/branding/clinica-norte-favicon.svg') }}?v={{ $cnFaviconVer }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/clinica-norte-welcome.css') }}?v={{ $welcomeCssVer }}">
    <link rel="stylesheet" href="{{ asset('css/home-search.css') }}?v={{ $homeSearchCssVer }}">
</head>
<body class="cn-welcome">
    <header class="cn-welcome__header">
        <div class="cn-welcome__header-inner">
            <a href="{{ url('/') }}" class="cn-welcome__brand" aria-label="Clínica Norte — inicio">
                <img src="{{ asset('images/branding/clinica-norte-favicon.svg') }}?v={{ $cnFaviconVer }}" alt="" width="44" height="44">
                <span class="cn-welcome__brand-text">
                    <span class="cn-welcome__brand-name">Clínica Norte S.R.L.</span>
                    <span class="cn-welcome__brand-tag">Laboratorio e imágenes diagnósticas</span>
                </span>
            </a>

            <div class="cn-welcome__search">
                <x-home-search variant="welcome" :include-assets="false" />
            </div>

            <div class="cn-welcome__header-actions">
                @auth
                    @if(auth()->user()->hasRole('Paciente'))
                        <a href="{{ route('results.patient.portal') }}" class="cn-welcome__btn cn-welcome__btn--primary">Mi portal</a>
                    @elseif(auth()->user()->hasRole('Médico'))
                        <a href="{{ route('doctor.dashboard') }}" class="cn-welcome__btn cn-welcome__btn--primary">Mi panel</a>
                    @elseif(auth()->user()->canAccessPanel(filament()->getDefaultPanel()))
                        <a href="{{ url('/admin') }}" class="cn-welcome__btn cn-welcome__btn--primary">Panel admin</a>
                    @endif
                @else
                    <a href="{{ route('filament.admin.auth.login') }}" class="cn-welcome__btn cn-welcome__btn--ghost">Personal</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="cn-welcome__main">
        <section class="cn-welcome__hero" aria-labelledby="hero-title">
            <h1 id="hero-title">Atención clínica con <span>resultados confiables</span></h1>
            <p class="cn-welcome__hero-lead">
                Laboratorio, estudios de imagen y seguimiento en línea para pacientes y médicos derivantes.
            </p>
        </section>

        <section class="cn-welcome__portals" aria-labelledby="portals-title">
            <p id="portals-title" class="cn-welcome__portals-title">Accesos en línea</p>
            <div class="cn-welcome__grid">
                <a href="{{ auth()->check() && auth()->user()->hasRole('Paciente') ? route('results.patient.portal') : route('patient.login') }}" class="cn-welcome__card">
                    <div class="cn-welcome__card-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                        </svg>
                    </div>
                    <h2>Portal del paciente</h2>
                    <p>Consulte resultados validados, descargue PDFs y revise sus comprobantes de pago.</p>
                    <span class="cn-welcome__card-link">
                        {{ auth()->check() && auth()->user()->hasRole('Paciente') ? 'Ir a mis resultados' : 'Ingresar como paciente' }} →
                    </span>
                </a>

                <a href="{{ auth()->check() && auth()->user()->hasRole('Médico') ? route('doctor.dashboard') : route('doctor.login') }}" class="cn-welcome__card">
                    <div class="cn-welcome__card-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/>
                        </svg>
                    </div>
                    <h2>Portal del médico</h2>
                    <p>Revise pacientes derivados, órdenes y resultados listos desde un solo lugar.</p>
                    <span class="cn-welcome__card-link">
                        {{ auth()->check() && auth()->user()->hasRole('Médico') ? 'Ir al panel médico' : 'Ingresar como médico' }} →
                    </span>
                </a>

                <a href="{{ auth()->check() && auth()->user()->canAccessPanel(filament()->getDefaultPanel()) ? url('/admin') : route('filament.admin.auth.login') }}" class="cn-welcome__card">
                    <div class="cn-welcome__card-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
                        </svg>
                    </div>
                    <h2>Panel administrativo</h2>
                    <p>Personal de la clínica: órdenes, muestras, estudios, resultados, pagos y reportes.</p>
                    <span class="cn-welcome__card-link">
                        {{ auth()->check() && auth()->user()->canAccessPanel(filament()->getDefaultPanel()) ? 'Ir al panel' : 'Ingresar como personal' }} →
                    </span>
                </a>
            </div>
        </section>

        <section class="cn-welcome__services" aria-labelledby="services-title">
            <div class="cn-welcome__services-inner">
                <h2 id="services-title">Servicios</h2>
                <div class="cn-welcome__features">
                    <div class="cn-welcome__feature">
                        <strong>Laboratorio clínico</strong>
                        <span>Análisis con seguimiento de muestras y validación profesional.</span>
                    </div>
                    <div class="cn-welcome__feature">
                        <strong>Estudios de imagen</strong>
                        <span>Diagnóstico por imagen integrado al flujo de la clínica.</span>
                    </div>
                    <div class="cn-welcome__feature">
                        <strong>Resultados en línea</strong>
                        <span>Publicación segura de informes en PDF para pacientes autorizados.</span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="cn-welcome__footer">
        <p>&copy; {{ date('Y') }} Clínica Norte S.R.L.</p>
        <x-page-visit-footer variant="minimal" />
    </footer>

    <script src="{{ asset('js/home-search.js') }}?v={{ $homeSearchJsVer }}" defer></script>
</body>
</html>
