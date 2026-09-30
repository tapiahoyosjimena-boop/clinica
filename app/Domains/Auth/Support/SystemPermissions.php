<?php

namespace App\Domains\Auth\Support;

/**
 * Catálogo central de permisos: superficies (portales/panel) y módulos por contexto.
 */
final class SystemPermissions
{
    public const ADMIN_PANEL = 'admin.panel.access';

    public const PATIENT_PORTAL = 'patient.portal.access';

    public const DOCTOR_PORTAL = 'doctor.portal.access';

    public const ADMIN_DASHBOARD = 'admin.dashboard.access';

    public const ADMIN_NOTIFICATIONS = 'notifications.access';

    public const PATIENT_DASHBOARD = 'patient.dashboard.access';

    public const PATIENT_RESULTS = 'patient.results.access';

    public const PATIENT_PAYMENTS = 'patient.payments.access';

    public const PATIENT_NOTIFICATIONS = 'patient.notifications.access';

    public const PATIENT_PROFILE = 'patient.profile.access';

    public const DOCTOR_DASHBOARD = 'doctor.dashboard.access';

    public const DOCTOR_PATIENTS = 'doctor.patients.access';

    public const DOCTOR_RESULTS = 'doctor.results.access';

    public const DOCTOR_NOTIFICATIONS = 'doctor.notifications.access';

    public const DOCTOR_PROFILE = 'doctor.profile.access';

    /** @return array<string, array{label: string, icon: string, permission: string, mandatory?: bool}> */
    public static function adminPanelModuleDefinitions(): array
    {
        return [
            'dashboard' => [
                'label' => 'Dashboard / Inicio (panel)',
                'icon' => 'heroicon-o-home',
                'permission' => self::ADMIN_DASHBOARD,
                'mandatory' => true,
            ],
            'notifications' => [
                'label' => 'Notificaciones (módulo y campana)',
                'icon' => 'heroicon-o-bell',
                'permission' => self::ADMIN_NOTIFICATIONS,
                'mandatory' => true,
            ],
            'auth' => ['label' => 'Administración (Auth)', 'icon' => 'heroicon-o-cog-6-tooth', 'permission' => 'auth.access'],
            'patients' => ['label' => 'Pacientes', 'icon' => 'heroicon-o-heart', 'permission' => 'patients.access'],
            'orders' => ['label' => 'Órdenes', 'icon' => 'heroicon-o-clipboard-document-list', 'permission' => 'orders.access'],
            'samples' => ['label' => 'Muestras', 'icon' => 'heroicon-o-beaker', 'permission' => 'samples.access'],
            'results' => ['label' => 'Resultados', 'icon' => 'heroicon-o-document-chart-bar', 'permission' => 'results.access'],
            'payments' => ['label' => 'Pagos', 'icon' => 'heroicon-o-banknotes', 'permission' => 'payments.access'],
            'reactivos' => ['label' => 'Reactivos', 'icon' => 'heroicon-o-archive-box', 'permission' => 'reactivos.access'],
            'catalog' => ['label' => 'Catálogo', 'icon' => 'heroicon-o-book-open', 'permission' => 'catalog.access'],
            'imaging' => ['label' => 'Imágenes', 'icon' => 'heroicon-o-photo', 'permission' => 'imaging.access'],
            'reportes' => ['label' => 'Reportes', 'icon' => 'heroicon-o-chart-bar', 'permission' => 'reportes.access'],
        ];
    }

    /** @return array<string, array{label: string, icon: string, permission: string, mandatory?: bool}> */
    public static function patientPortalModuleDefinitions(): array
    {
        return [
            'dashboard' => [
                'label' => 'Inicio / dashboard',
                'icon' => 'heroicon-o-home',
                'permission' => self::PATIENT_DASHBOARD,
                'mandatory' => true,
            ],
            'notifications' => [
                'label' => 'Notificaciones',
                'icon' => 'heroicon-o-bell',
                'permission' => self::PATIENT_NOTIFICATIONS,
                'mandatory' => true,
            ],
            'results' => [
                'label' => 'Resultados (PDF)',
                'icon' => 'heroicon-o-document-text',
                'permission' => self::PATIENT_RESULTS,
            ],
            'payments' => [
                'label' => 'Comprobantes / pagos',
                'icon' => 'heroicon-o-banknotes',
                'permission' => self::PATIENT_PAYMENTS,
            ],
            'profile' => [
                'label' => 'Perfil',
                'icon' => 'heroicon-o-user',
                'permission' => self::PATIENT_PROFILE,
            ],
        ];
    }

    /** @return array<string, array{label: string, icon: string, permission: string, mandatory?: bool}> */
    public static function doctorPortalModuleDefinitions(): array
    {
        return [
            'dashboard' => [
                'label' => 'Dashboard',
                'icon' => 'heroicon-o-home',
                'permission' => self::DOCTOR_DASHBOARD,
                'mandatory' => true,
            ],
            'notifications' => [
                'label' => 'Notificaciones',
                'icon' => 'heroicon-o-bell',
                'permission' => self::DOCTOR_NOTIFICATIONS,
                'mandatory' => true,
            ],
            'patients' => [
                'label' => 'Pacientes derivados',
                'icon' => 'heroicon-o-users',
                'permission' => self::DOCTOR_PATIENTS,
            ],
            'results' => [
                'label' => 'Resultados',
                'icon' => 'heroicon-o-document-chart-bar',
                'permission' => self::DOCTOR_RESULTS,
            ],
            'profile' => [
                'label' => 'Perfil',
                'icon' => 'heroicon-o-user',
                'permission' => self::DOCTOR_PROFILE,
            ],
        ];
    }

    /** @return array<string, array{label: string, permission: string}> */
    public static function specialPermissionDefinitions(): array
    {
        return [
            'samples_approve' => [
                'label' => 'Muestras: aceptar / procesar',
                'permission' => 'samples.approve',
            ],
            'samples_reject' => [
                'label' => 'Muestras: rechazar',
                'permission' => 'samples.reject',
            ],
            'imaging_approve' => [
                'label' => 'Imagen: flujo de estudio',
                'permission' => 'imaging.approve',
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, icon: string, permission: string}>
     */
    public static function surfaceDefinitions(): array
    {
        return [
            'admin_panel' => [
                'label' => 'Panel administración (/admin)',
                'icon' => 'heroicon-o-computer-desktop',
                'permission' => self::ADMIN_PANEL,
            ],
            'patient_portal' => [
                'label' => 'Portal paciente (/paciente)',
                'icon' => 'heroicon-o-user-circle',
                'permission' => self::PATIENT_PORTAL,
            ],
            'doctor_portal' => [
                'label' => 'Portal médico (/medico)',
                'icon' => 'heroicon-o-academic-cap',
                'permission' => self::DOCTOR_PORTAL,
            ],
        ];
    }

    public static function surfacePermissionName(string $surfaceKey): string
    {
        return self::surfaceDefinitions()[$surfaceKey]['permission'];
    }

    /**
     * @return list<string>
     */
    public static function surfacePermissionNames(): array
    {
        return array_column(self::surfaceDefinitions(), 'permission');
    }

    /**
     * @return list<string>
     */
    public static function adminPanelModulePermissionNames(): array
    {
        return array_column(self::adminPanelModuleDefinitions(), 'permission');
    }

    /**
     * @return list<string>
     */
    public static function patientPortalModulePermissionNames(): array
    {
        return array_column(self::patientPortalModuleDefinitions(), 'permission');
    }

    /**
     * @return list<string>
     */
    public static function doctorPortalModulePermissionNames(): array
    {
        return array_column(self::doctorPortalModuleDefinitions(), 'permission');
    }

    /**
     * @return list<string>
     */
    public static function specialPermissionNames(): array
    {
        return array_column(self::specialPermissionDefinitions(), 'permission');
    }

    /**
     * @return list<string>
     */
    public static function allAssignable(): array
    {
        return array_merge(
            self::surfacePermissionNames(),
            self::adminPanelModulePermissionNames(),
            self::patientPortalModulePermissionNames(),
            self::doctorPortalModulePermissionNames(),
            self::specialPermissionNames(),
        );
    }

    /**
     * Permisos mínimos al activar una superficie (siempre se fusionan al guardar).
     *
     * @return list<string>
     */
    public static function mandatoryPermissionsForSurface(string $surfaceKey): array
    {
        return match ($surfaceKey) {
            'admin_panel' => [
                self::ADMIN_PANEL,
                self::ADMIN_DASHBOARD,
                self::ADMIN_NOTIFICATIONS,
            ],
            'patient_portal' => [
                self::PATIENT_PORTAL,
                self::PATIENT_DASHBOARD,
                self::PATIENT_NOTIFICATIONS,
            ],
            'doctor_portal' => [
                self::DOCTOR_PORTAL,
                self::DOCTOR_DASHBOARD,
                self::DOCTOR_NOTIFICATIONS,
            ],
            default => [],
        };
    }

    public static function activeSurfaceKeyFromPermissionNames(iterable $names): ?string
    {
        $collection = collect($names);

        foreach (array_keys(self::surfaceDefinitions()) as $surfaceKey) {
            if ($collection->contains(self::surfacePermissionName($surfaceKey))) {
                return $surfaceKey;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function legacyPatientPortalModulePermissions(): array
    {
        return [
            'results.access',
        ];
    }

    /**
     * @return list<string>
     */
    public static function legacyDoctorPortalModulePermissions(): array
    {
        return [
            'patients.access',
            'orders.access',
            'results.access',
            'notifications.access',
        ];
    }
}
