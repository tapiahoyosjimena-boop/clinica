<?php

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Support\SystemPermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (SystemPermissions::allAssignable() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $rolePresets = [
            'Administrador' => array_merge(
                [SystemPermissions::ADMIN_PANEL],
                SystemPermissions::adminPanelModulePermissionNames(),
                SystemPermissions::specialPermissionNames(),
            ),
            'Recepcionista' => [
                SystemPermissions::ADMIN_PANEL,
                SystemPermissions::ADMIN_DASHBOARD,
                SystemPermissions::ADMIN_NOTIFICATIONS,
                'patients.access',
                'orders.access',
                'payments.access',
                'samples.access',
                'imaging.access',
                'reportes.access',
            ],
            'Bioquímico' => [
                SystemPermissions::ADMIN_PANEL,
                SystemPermissions::ADMIN_DASHBOARD,
                SystemPermissions::ADMIN_NOTIFICATIONS,
                'samples.access',
                'samples.approve',
                'samples.reject',
                'results.access',
                'reactivos.access',
                'patients.access',
                'orders.access',
                'reportes.access',
            ],
            'Tecnólogo de Imagen' => [
                SystemPermissions::ADMIN_PANEL,
                SystemPermissions::ADMIN_DASHBOARD,
                SystemPermissions::ADMIN_NOTIFICATIONS,
                'imaging.access',
                'imaging.approve',
                'results.access',
                'patients.access',
                'orders.access',
                'catalog.access',
                'reportes.access',
            ],
            'Médico' => array_merge(
                [SystemPermissions::DOCTOR_PORTAL],
                SystemPermissions::doctorPortalModulePermissionNames(),
            ),
            'Paciente' => array_merge(
                [SystemPermissions::PATIENT_PORTAL],
                SystemPermissions::patientPortalModulePermissionNames(),
            ),
        ];

        foreach ($rolePresets as $roleName => $permissionNames) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
            if (! $role) {
                continue;
            }

            $permissions = Permission::whereIn('name', $permissionNames)->get();
            $role->syncPermissions($permissions);
        }

        Role::query()->where('guard_name', 'web')->each(function (Role $role) use ($rolePresets): void {
            if (array_key_exists($role->name, $rolePresets)) {
                return;
            }

            $role->load('permissions');
            $names = $role->permissions->pluck('name');

            $surface = SystemPermissions::activeSurfaceKeyFromPermissionNames($names);
            if ($surface === null) {
                return;
            }

            $surfaces = [];
            foreach (array_keys(SystemPermissions::surfaceDefinitions()) as $key) {
                if ($names->contains(SystemPermissions::surfacePermissionName($key))) {
                    $surfaces[] = $key;
                }
            }

            if (count($surfaces) > 1) {
                $keep = in_array('admin_panel', $surfaces, true)
                    ? 'admin_panel'
                    : ($surfaces[0] ?? 'admin_panel');
                $surface = $keep;
            }

            $newNames = collect(SystemPermissions::mandatoryPermissionsForSurface($surface));

            if ($surface === 'admin_panel') {
                foreach (SystemPermissions::adminPanelModuleDefinitions() as $def) {
                    if ($names->contains($def['permission'])) {
                        $newNames->push($def['permission']);
                    }
                }
                foreach (SystemPermissions::specialPermissionDefinitions() as $def) {
                    if ($names->contains($def['permission'])) {
                        $newNames->push($def['permission']);
                    }
                }
            }

            if ($surface === 'patient_portal') {
                foreach (SystemPermissions::patientPortalModuleDefinitions() as $moduleKey => $def) {
                    $has = $names->contains($def['permission'])
                        || ($moduleKey === 'results' && $names->contains('results.access'));
                    if ($has) {
                        $newNames->push($def['permission']);
                    }
                }
            }

            if ($surface === 'doctor_portal') {
                $legacyMap = [
                    'patients.access' => SystemPermissions::DOCTOR_PATIENTS,
                    'results.access' => SystemPermissions::DOCTOR_RESULTS,
                    'notifications.access' => SystemPermissions::DOCTOR_NOTIFICATIONS,
                ];
                foreach (SystemPermissions::doctorPortalModuleDefinitions() as $def) {
                    if ($names->contains($def['permission'])) {
                        $newNames->push($def['permission']);
                    }
                }
                foreach ($legacyMap as $legacy => $modern) {
                    if ($names->contains($legacy)) {
                        $newNames->push($modern);
                    }
                }
            }

            $permissions = Permission::whereIn('name', $newNames->unique()->values()->all())->get();
            $role->syncPermissions($permissions);
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (array_merge(
            SystemPermissions::patientPortalModulePermissionNames(),
            SystemPermissions::doctorPortalModulePermissionNames(),
            [SystemPermissions::ADMIN_DASHBOARD],
        ) as $name) {
            Permission::query()->where('name', $name)->where('guard_name', 'web')->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
