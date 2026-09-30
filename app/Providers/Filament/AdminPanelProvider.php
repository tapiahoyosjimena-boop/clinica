<?php

namespace App\Providers\Filament;

use App\Domains\Auth\Filament\Pages\EditAdminProfile;
use App\Domains\Auth\Filament\Pages\Login;
use App\Domains\Auth\Filament\Pages\RequestPasswordReset;
use App\Domains\Auth\Filament\Pages\ResetPassword;
use App\Domains\Auth\Filament\Pages\ViewAdminProfile;
use App\Filament\Widgets\ClinicOperationalStats;
use App\Http\Middleware\EnsureValidPanelUser;
use App\Http\Middleware\RecordPageVisit;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /** Rutas de login / recuperación de contraseña del panel admin. */
    private const AUTH_ROUTE_NAMES = [
        'filament.admin.auth.login',
        'filament.admin.auth.password-reset.request',
        'filament.admin.auth.password-reset.reset',
    ];

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->profile(ViewAdminProfile::class, isSimple: false)
            ->pages([
                EditAdminProfile::class,
            ])
            ->passwordReset(RequestPasswordReset::class, ResetPassword::class)
            ->brandName('Clínica Norte')
            ->brandLogo('/images/branding/clinica-norte-logo.svg')
            ->brandLogoHeight('3.75rem')
            ->favicon('/images/branding/clinica-norte-favicon.svg')
            ->colors([
                'primary' => Color::hex('#2EB67D'),
                'success' => Color::hex('#2EB67D'),
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'gray' => Color::Slate,
            ])
            ->maxContentWidth(MaxWidth::Full)
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(
                    '<link rel="stylesheet" href="'.asset('css/clinica-norte-admin.css').'?v='.filemtime(public_path('css/clinica-norte-admin.css')).'">'
                )
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): HtmlString => request()->routeIs(...self::AUTH_ROUTE_NAMES)
                    ? new HtmlString('')
                    : new HtmlString(Blade::render('<x-page-visit-footer variant="filament" />'))
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): HtmlString => new HtmlString(
                    view('filament.partials.admin-auth-footer')->render()
                )
            )
            ->renderHook(
                PanelsRenderHook::AUTH_PASSWORD_RESET_REQUEST_FORM_AFTER,
                fn (): HtmlString => new HtmlString(
                    view('filament.partials.admin-auth-footer')->render()
                )
            )
            ->renderHook(
                PanelsRenderHook::AUTH_PASSWORD_RESET_RESET_FORM_AFTER,
                fn (): HtmlString => new HtmlString(
                    view('filament.partials.admin-auth-footer')->render()
                )
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => request()->routeIs('filament.admin.pages.dashboard')
                    ? new HtmlString(
                        '<link rel="stylesheet" href="'.asset('css/home-search.css').'?v='.filemtime(public_path('css/home-search.css')).'">'
                    )
                    : new HtmlString('')
            )
            ->renderHook(
                PanelsRenderHook::CONTENT_START,
                fn (): HtmlString => request()->routeIs('filament.admin.pages.dashboard')
                    ? new HtmlString(Blade::render('<x-home-search variant="filament" />'))
                    : new HtmlString('')
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): HtmlString => request()->routeIs('filament.admin.pages.dashboard')
                    ? new HtmlString(
                        '<script src="'.asset('js/home-search.js').'?v='.filemtime(public_path('js/home-search.js')).'" defer></script>'
                    )
                    : new HtmlString('')
            )
            // Recursos del dominio Auth
            ->discoverResources(
                in: app_path('Domains/Auth/Filament/Resources'),
                for: 'App\\Domains\\Auth\\Filament\\Resources'
            )
            // Recursos del dominio Patients (Módulo 2)
            ->discoverResources(
                in: app_path('Domains/Patients/Filament/Resources'),
                for: 'App\\Domains\\Patients\\Filament\\Resources'
            )
            // Recursos del dominio Orders (Módulo 3)
            ->discoverResources(
                in: app_path('Domains/Orders/Filament/Resources'),
                for: 'App\\Domains\\Orders\\Filament\\Resources'
            )
            // Recursos del dominio Samples (Módulo 4)
            ->discoverResources(
                in: app_path('Domains/Samples/Filament/Resources'),
                for: 'App\\Domains\\Samples\\Filament\\Resources'
            )
            // Recursos del dominio Imaging (Módulo 5)
            ->discoverResources(
                in: app_path('Domains/Imaging/Filament/Resources'),
                for: 'App\\Domains\\Imaging\\Filament\\Resources'
            )
            // Recursos del dominio Catalog
            ->discoverResources(
                in: app_path('Domains/Catalog/Filament/Resources'),
                for: 'App\\Domains\\Catalog\\Filament\\Resources'
            )
            // Recursos del dominio Payments
            ->discoverResources(
                in: app_path('Domains/Payments/Filament/Resources'),
                for: 'App\\Domains\\Payments\\Filament\\Resources'
            )
            // Recursos del dominio Results (Módulo 6)
            ->discoverResources(
                in: app_path('Domains/Results/Filament/Resources'),
                for: 'App\\Domains\\Results\\Filament\\Resources'
            )
            // Recursos del dominio Reactivos (Inventario)
            ->discoverResources(
                in: app_path('Domains/Reactivos/Filament/Resources'),
                for: 'App\\Domains\\Reactivos\\Filament\\Resources'
            )
            // Recursos del dominio Notifications
            ->discoverResources(
                in: app_path('Domains/Notifications/Filament/Resources'),
                for: 'App\\Domains\\Notifications\\Filament\\Resources'
            )
            // Recursos generales (para módulos futuros)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Domains/Reportes/Filament/Pages'), for: 'App\\Domains\\Reportes\\Filament\\Pages')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                ClinicOperationalStats::class,
            ])
            ->navigationGroups([
                'Administración',
                'Pacientes',
                'Órdenes',
                'Muestras',
                'Estudios de Imagen',
                'Resultados',
                'Pagos',
                'Catálogo',
                'Reactivos',
                'Reportes',
                'Sistema',
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                RecordPageVisit::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                // Impide que Paciente o Médico accedan al panel admin.
                EnsureValidPanelUser::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s');
    }
}
