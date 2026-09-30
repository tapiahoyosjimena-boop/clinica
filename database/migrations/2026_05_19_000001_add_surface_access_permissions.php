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

        foreach (SystemPermissions::surfacePermissionNames() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $map = [
            'Administrador' => SystemPermissions::allAssignable(),
            'Recepcionista' => [
                SystemPermissions::ADMIN_PANEL,
                'patients.access',
                'orders.access',
                'payments.access',
                'samples.access',
                'imaging.access',
                'notifications.access',
                'reportes.access',
            ],
            'Bioquímico' => [
                SystemPermissions::ADMIN_PANEL,
                'samples.access',
                'samples.approve',
                'samples.reject',
                'results.access',
                'reactivos.access',
                'patients.access',
                'orders.access',
                'notifications.access',
                'reportes.access',
            ],
            'Tecnólogo de Imagen' => [
                SystemPermissions::ADMIN_PANEL,
                'imaging.access',
                'imaging.approve',
                'results.access',
                'patients.access',
                'orders.access',
                'catalog.access',
                'notifications.access',
                'reportes.access',
            ],
            'Médico' => [
                SystemPermissions::DOCTOR_PORTAL,
                'patients.access',
                'orders.access',
                'results.access',
                'notifications.access',
            ],
            'Paciente' => [
                SystemPermissions::PATIENT_PORTAL,
                'results.access',
            ],
        ];

        foreach ($map as $roleName => $permissionNames) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
            if (! $role) {
                continue;
            }

            $permissions = Permission::whereIn('name', $permissionNames)->get();
            $role->syncPermissions($permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (SystemPermissions::surfacePermissionNames() as $name) {
            Permission::query()->where('name', $name)->where('guard_name', 'web')->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
