<?php

namespace App\Domains\Auth\Traits;

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Services\UserPermissionSync;
use App\Domains\Auth\Support\SystemPermissions;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Spatie\Permission\PermissionRegistrar;

/**
 * Formularios y sincronización de permisos para RoleResource.
 */
trait HasModulePermissions
{
    public static function surfaceDefinitions(): array
    {
        return SystemPermissions::surfaceDefinitions();
    }

    public static function adminPanelModuleDefinitions(): array
    {
        return SystemPermissions::adminPanelModuleDefinitions();
    }

    public static function patientPortalModuleDefinitions(): array
    {
        return SystemPermissions::patientPortalModuleDefinitions();
    }

    public static function doctorPortalModuleDefinitions(): array
    {
        return SystemPermissions::doctorPortalModuleDefinitions();
    }

    public static function specialPermissionDefinitions(): array
    {
        return SystemPermissions::specialPermissionDefinitions();
    }

    public static function surfacePermissionName(string $surfaceKey): string
    {
        return SystemPermissions::surfacePermissionName($surfaceKey);
    }

    public static function buildSurfaceToggle(string $surfaceKey): Forms\Components\Toggle
    {
        $def = static::surfaceDefinitions()[$surfaceKey];

        return Forms\Components\Toggle::make("surface_{$surfaceKey}")
            ->label($def['label'])
            ->onColor('success')
            ->offColor('danger')
            ->helperText($def['permission'])
            ->live()
            ->afterStateUpdated(function (bool $state, Set $set) use ($surfaceKey): void {
                if (! $state) {
                    return;
                }

                foreach (array_keys(static::surfaceDefinitions()) as $other) {
                    if ($other !== $surfaceKey) {
                        $set("surface_{$other}", false);
                    }
                }

                static::applySurfaceDefaultsToForm($surfaceKey, $set);
            })
            ->columnSpan(1);
    }

    public static function buildAdminModuleToggle(string $moduleKey): Forms\Components\Toggle
    {
        $def = static::adminPanelModuleDefinitions()[$moduleKey];
        $mandatory = (bool) ($def['mandatory'] ?? false);

        return Forms\Components\Toggle::make("admin_mod_{$moduleKey}")
            ->label($def['label'])
            ->onColor('success')
            ->offColor('danger')
            ->helperText($def['permission'])
            ->disabled($mandatory)
            ->default($mandatory)
            ->columnSpan(1);
    }

    public static function buildPatientModuleToggle(string $moduleKey): Forms\Components\Toggle
    {
        $def = static::patientPortalModuleDefinitions()[$moduleKey];
        $mandatory = (bool) ($def['mandatory'] ?? false);

        return Forms\Components\Toggle::make("patient_mod_{$moduleKey}")
            ->label($def['label'])
            ->onColor('success')
            ->offColor('danger')
            ->helperText($def['permission'])
            ->disabled($mandatory)
            ->default($mandatory)
            ->columnSpan(1);
    }

    public static function buildDoctorModuleToggle(string $moduleKey): Forms\Components\Toggle
    {
        $def = static::doctorPortalModuleDefinitions()[$moduleKey];
        $mandatory = (bool) ($def['mandatory'] ?? false);

        return Forms\Components\Toggle::make("doctor_mod_{$moduleKey}")
            ->label($def['label'])
            ->onColor('success')
            ->offColor('danger')
            ->helperText($def['permission'])
            ->disabled($mandatory)
            ->default($mandatory)
            ->columnSpan(1);
    }

    public static function buildSpecialToggle(string $specialKey): Forms\Components\Toggle
    {
        $def = static::specialPermissionDefinitions()[$specialKey];

        return Forms\Components\Toggle::make("special_{$specialKey}")
            ->label($def['label'])
            ->onColor('success')
            ->offColor('danger')
            ->helperText($def['permission'])
            ->columnSpan(1);
    }

    /**
     * @return list<Component>
     */
    public static function permissionFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Acceso a portales y panel')
                ->icon('heroicon-o-globe-alt')
                ->description('Solo puede activarse una superficie por rol: panel admin, portal paciente o portal médico.')
                ->schema(
                    collect(array_keys(static::surfaceDefinitions()))
                        ->map(fn (string $key) => static::buildSurfaceToggle($key))
                        ->all()
                )
                ->columns(3),

            Forms\Components\Section::make('Módulos del panel administración')
                ->icon('heroicon-o-key')
                ->description('Dashboard y notificaciones siempre activos con el panel. El resto es opcional.')
                ->visible(fn (Get $get): bool => static::formToggleIsEnabled($get, 'surface_admin_panel'))
                ->schema(
                    collect(array_keys(static::adminPanelModuleDefinitions()))
                        ->map(fn (string $key) => static::buildAdminModuleToggle($key))
                        ->all()
                )
                ->columns(4),

            Forms\Components\Section::make('Módulos del portal paciente')
                ->icon('heroicon-o-user-circle')
                ->description('Inicio y notificaciones siempre activos con el portal paciente.')
                ->visible(fn (Get $get): bool => static::formToggleIsEnabled($get, 'surface_patient_portal'))
                ->schema(
                    collect(array_keys(static::patientPortalModuleDefinitions()))
                        ->map(fn (string $key) => static::buildPatientModuleToggle($key))
                        ->all()
                )
                ->columns(3),

            Forms\Components\Section::make('Módulos del portal médico')
                ->icon('heroicon-o-academic-cap')
                ->description('Dashboard y notificaciones siempre activos con el portal médico.')
                ->visible(fn (Get $get): bool => static::formToggleIsEnabled($get, 'surface_doctor_portal'))
                ->schema(
                    collect(array_keys(static::doctorPortalModuleDefinitions()))
                        ->map(fn (string $key) => static::buildDoctorModuleToggle($key))
                        ->all()
                )
                ->columns(3),

            Forms\Components\Section::make('Permisos especiales (panel admin)')
                ->icon('heroicon-o-shield-check')
                ->visible(fn (Get $get): bool => static::formToggleIsEnabled($get, 'surface_admin_panel'))
                ->schema(
                    collect(array_keys(static::specialPermissionDefinitions()))
                        ->map(fn (string $key) => static::buildSpecialToggle($key))
                        ->all()
                )
                ->columns(3),
        ];
    }

    /**
     * @param  array<string, mixed>  $get  Callable Get o estructura similar del formulario
     */
    protected static function formToggleIsEnabled(mixed $get, string $key): bool
    {
        $value = is_callable($get) ? $get($key) : ($get[$key] ?? false);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return list<Component>
     */
    public static function rolePermissionFormSchema(): array
    {
        return [
            Forms\Components\Toggle::make('apply_to_users')
                ->label('Aplicar a usuarios con este rol')
                ->helperText('Quita permisos directos de cada usuario y deja que rija solo este rol (recomendado).')
                ->default(true)
                ->columnSpanFull(),

            ...static::permissionFormSchema(),
        ];
    }

    public static function formatRoleAccessSummaryHtml(?Role $role): HtmlString
    {
        if (! $role) {
            return new HtmlString('<p class="text-sm text-gray-500 dark:text-gray-400">Seleccione un rol para ver portales y módulos incluidos.</p>');
        }

        $role->loadMissing('permissions');
        $names = $role->permissions->pluck('name');
        $surface = SystemPermissions::activeSurfaceKeyFromPermissionNames($names);

        $html = '<div class="space-y-2 text-sm">';

        if ($surface === null) {
            return new HtmlString('<p class="text-sm text-warning-600 dark:text-warning-400">Sin superficie de acceso configurada.</p>');
        }

        $html .= '<p><span class="font-medium">Superficie:</span> '.e(static::surfaceDefinitions()[$surface]['label']).'</p>';

        $moduleLines = match ($surface) {
            'admin_panel' => static::summaryLinesForDefinitions($names, static::adminPanelModuleDefinitions()),
            'patient_portal' => static::summaryLinesForDefinitions($names, static::patientPortalModuleDefinitions()),
            'doctor_portal' => static::summaryLinesForDefinitions($names, static::doctorPortalModuleDefinitions()),
            default => [],
        };

        if ($moduleLines !== []) {
            $html .= '<p><span class="font-medium">Módulos:</span> '.implode(' · ', $moduleLines).'</p>';
        }

        if ($surface === 'admin_panel') {
            $specials = [];
            foreach (static::specialPermissionDefinitions() as $definition) {
                if ($names->contains($definition['permission'])) {
                    $specials[] = e($definition['label']);
                }
            }
            if ($specials !== []) {
                $html .= '<p><span class="font-medium">Especiales:</span> '.implode(' · ', $specials).'</p>';
            }
        }

        $html .= '</div>';

        return new HtmlString($html);
    }

    /**
     * @param  Collection<int, string>  $names
     * @param  array<string, array{label: string, permission: string}>  $definitions
     * @return list<string>
     */
    protected static function summaryLinesForDefinitions(Collection $names, array $definitions): array
    {
        $lines = [];
        foreach ($definitions as $definition) {
            if ($names->contains($definition['permission'])) {
                $lines[] = e($definition['label']);
            }
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mergePermissionToggleStateIntoFormData(array $data, ?Role $role): array
    {
        $permissionNames = $role
            ? $role->loadMissing('permissions')->permissions->pluck('name')
            : collect();

        foreach (array_keys(static::surfaceDefinitions()) as $surfaceKey) {
            $data["surface_{$surfaceKey}"] = $permissionNames->contains(
                static::surfacePermissionName($surfaceKey)
            );
        }

        $activeSurface = SystemPermissions::activeSurfaceKeyFromPermissionNames($permissionNames);

        foreach (static::adminPanelModuleDefinitions() as $moduleKey => $definition) {
            $data["admin_mod_{$moduleKey}"] = $permissionNames->contains($definition['permission']);
        }
        foreach (static::patientPortalModuleDefinitions() as $moduleKey => $definition) {
            $data["patient_mod_{$moduleKey}"] = $permissionNames->contains($definition['permission'])
                || ($moduleKey === 'results' && $permissionNames->contains('results.access'));
        }
        foreach (static::doctorPortalModuleDefinitions() as $moduleKey => $definition) {
            $legacy = match ($moduleKey) {
                'patients' => 'patients.access',
                'results' => 'results.access',
                'notifications' => 'notifications.access',
                default => null,
            };
            $data["doctor_mod_{$moduleKey}"] = $permissionNames->contains($definition['permission'])
                || ($legacy && $permissionNames->contains($legacy));
        }

        foreach (array_keys(static::specialPermissionDefinitions()) as $specialKey) {
            $perm = static::specialPermissionDefinitions()[$specialKey]['permission'];
            $data["special_{$specialKey}"] = $permissionNames->contains($perm);
        }

        if ($activeSurface === null) {
            $data['apply_to_users'] = $data['apply_to_users'] ?? true;

            return $data;
        }

        foreach (static::mandatoryToggleDefaultsForSurface($activeSurface) as $field => $value) {
            $data[$field] = $value;
        }

        $data['apply_to_users'] = $data['apply_to_users'] ?? true;

        return $data;
    }

    /**
     * @return array<string, bool>
     */
    protected static function mandatoryToggleDefaultsForSurface(string $surfaceKey): array
    {
        $defaults = [];

        $definitions = match ($surfaceKey) {
            'admin_panel' => static::adminPanelModuleDefinitions(),
            'patient_portal' => static::patientPortalModuleDefinitions(),
            'doctor_portal' => static::doctorPortalModuleDefinitions(),
            default => [],
        };

        $prefix = match ($surfaceKey) {
            'admin_panel' => 'admin_mod_',
            'patient_portal' => 'patient_mod_',
            'doctor_portal' => 'doctor_mod_',
            default => '',
        };

        foreach ($definitions as $moduleKey => $definition) {
            if ($definition['mandatory'] ?? false) {
                $defaults["{$prefix}{$moduleKey}"] = true;
            }
        }

        return $defaults;
    }

    protected static function applySurfaceDefaultsToForm(string $surfaceKey, Set $set): void
    {
        foreach (array_keys(static::surfaceDefinitions()) as $key) {
            if ($key !== $surfaceKey) {
                $set("surface_{$key}", false);
            }
        }

        $surfacePrefixes = [
            'admin_panel' => 'admin_mod_',
            'patient_portal' => 'patient_mod_',
            'doctor_portal' => 'doctor_mod_',
        ];

        foreach ($surfacePrefixes as $surface => $prefix) {
            $definitions = match ($surface) {
                'admin_panel' => static::adminPanelModuleDefinitions(),
                'patient_portal' => static::patientPortalModuleDefinitions(),
                'doctor_portal' => static::doctorPortalModuleDefinitions(),
                default => [],
            };

            foreach ($definitions as $moduleKey => $definition) {
                $field = "{$prefix}{$moduleKey}";
                if ($surface === $surfaceKey) {
                    $set($field, (bool) ($definition['mandatory'] ?? false));
                } else {
                    $set($field, false);
                }
            }
        }

        foreach (array_keys(static::specialPermissionDefinitions()) as $specialKey) {
            $set("special_{$specialKey}", false);
        }
    }

    /**
     * Elimina del array de datos todos los campos de toggle virtual antes de que
     * Filament los pase al modelo (evita errores de columna no encontrada en BD).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function stripVirtualToggleKeys(array $data): array
    {
        foreach (array_keys(static::surfaceDefinitions()) as $surfaceKey) {
            unset($data["surface_{$surfaceKey}"]);
        }
        foreach (array_keys(static::adminPanelModuleDefinitions()) as $moduleKey) {
            unset($data["admin_mod_{$moduleKey}"]);
        }
        foreach (array_keys(static::patientPortalModuleDefinitions()) as $moduleKey) {
            unset($data["patient_mod_{$moduleKey}"]);
        }
        foreach (array_keys(static::doctorPortalModuleDefinitions()) as $moduleKey) {
            unset($data["doctor_mod_{$moduleKey}"]);
        }
        foreach (array_keys(static::specialPermissionDefinitions()) as $specialKey) {
            unset($data["special_{$specialKey}"]);
        }
        unset($data['apply_to_users']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return list<string>
     */
    protected static function collectPermissionNamesFromFormState(array $state): array
    {
        $activeSurface = null;
        foreach (array_keys(static::surfaceDefinitions()) as $surfaceKey) {
            if (static::formToggleIsEnabled($state, "surface_{$surfaceKey}")) {
                $activeSurface = $surfaceKey;
                break;
            }
        }

        if ($activeSurface === null) {
            return [];
        }

        $names = collect(SystemPermissions::mandatoryPermissionsForSurface($activeSurface));

        if ($activeSurface === 'admin_panel') {
            foreach (static::adminPanelModuleDefinitions() as $moduleKey => $definition) {
                if (static::formToggleIsEnabled($state, "admin_mod_{$moduleKey}")) {
                    $names->push($definition['permission']);
                }
            }
            foreach (static::specialPermissionDefinitions() as $specialKey => $definition) {
                if (static::formToggleIsEnabled($state, "special_{$specialKey}")) {
                    $names->push($definition['permission']);
                }
            }
        }

        if ($activeSurface === 'patient_portal') {
            foreach (static::patientPortalModuleDefinitions() as $moduleKey => $definition) {
                if (static::formToggleIsEnabled($state, "patient_mod_{$moduleKey}")) {
                    $names->push($definition['permission']);
                }
            }
        }

        if ($activeSurface === 'doctor_portal') {
            foreach (static::doctorPortalModuleDefinitions() as $moduleKey => $definition) {
                if (static::formToggleIsEnabled($state, "doctor_mod_{$moduleKey}")) {
                    $names->push($definition['permission']);
                }
            }
        }

        return $names->unique()->values()->all();
    }

    /**
     * @param  array<string, mixed>|null  $state
     */
    protected function syncRolePermissions(Role $role, ?array $state = null): int
    {
        $state ??= $this->form->getState();

        $names = static::collectPermissionNamesFromFormState($state);
        $permissions = Permission::whereIn('name', $names)->get();
        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (static::formToggleIsEnabled($state, 'apply_to_users')) {
            return UserPermissionSync::applyRoleToUsers($role);
        }

        return 0;
    }
}
