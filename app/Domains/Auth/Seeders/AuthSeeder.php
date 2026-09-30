<?php

namespace App\Domains\Auth\Seeders;

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Services\UserPermissionSync;
use App\Domains\Auth\Support\SystemPermissions;
use App\Models\User;
use App\Support\SystemAdministratorGuard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class AuthSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (SystemPermissions::allAssignable() as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $recepcionista = Role::firstOrCreate(['name' => 'Recepcionista', 'guard_name' => 'web']);
        $bioquimico = Role::firstOrCreate(['name' => 'Bioquímico', 'guard_name' => 'web']);
        $tecnologoImagen = Role::firstOrCreate(['name' => 'Tecnólogo de Imagen', 'guard_name' => 'web']);
        $medico = Role::firstOrCreate(['name' => 'Médico', 'guard_name' => 'web']);
        $paciente = Role::firstOrCreate(['name' => 'Paciente', 'guard_name' => 'web']);

        $this->syncRoleByNames($admin, array_merge(
            [SystemPermissions::ADMIN_PANEL],
            SystemPermissions::adminPanelModulePermissionNames(),
            SystemPermissions::specialPermissionNames(),
        ));

        $this->syncRoleByNames($recepcionista, [
            SystemPermissions::ADMIN_PANEL,
            SystemPermissions::ADMIN_DASHBOARD,
            SystemPermissions::ADMIN_NOTIFICATIONS,
            'patients.access',
            'orders.access',
            'payments.access',
            'samples.access',
            'imaging.access',
            'reportes.access',
        ]);

        $this->syncRoleByNames($bioquimico, [
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
        ]);

        $this->syncRoleByNames($tecnologoImagen, [
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
        ]);

        $this->syncRoleByNames($medico, array_merge(
            [SystemPermissions::DOCTOR_PORTAL],
            SystemPermissions::doctorPortalModulePermissionNames(),
        ));

        $this->syncRoleByNames($paciente, array_merge(
            [SystemPermissions::PATIENT_PORTAL],
            SystemPermissions::patientPortalModulePermissionNames(),
        ));

        if (app()->environment('production')) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
            $this->command?->info('Roles y permisos inicializados. No se crearon cuentas demo en producción.');

            return;
        }

        $this->seedStaffUser(
            email: SystemAdministratorGuard::PRIMARY_EMAIL,
            name: 'Administrador de Sistema',
            plainPassword: 'Admin@2026!',
            role: $admin,
        );

        $this->seedStaffUser(
            email: 'recepcionista@tecnoweb.shop',
            name: 'Recepcionista Tecno Web',
            plainPassword: 'Recep@2026!',
            role: $recepcionista,
        );

        $this->seedStaffUser(
            email: 'tecnologo@tecnoweb.shop',
            name: 'Tecnólogo de Imagen Tecno Web',
            plainPassword: 'Tecno@2026!',
            role: $tecnologoImagen,
        );

        $this->seedStaffUser(
            email: 'bioquimico@tecnoweb.shop',
            name: 'Bioquímico Tecno Web',
            plainPassword: 'Bio@2026!',
            role: $bioquimico,
        );

        $this->seedStaffUser(
            email: 'medico@tecnoweb.shop',
            name: 'Médico Derivante Tecno Web',
            plainPassword: 'Medico@2026!',
            role: $medico,
        );

        UserPermissionSync::reconcileStaffUsers();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('✅ Roles y permisos creados correctamente.');
        $this->command->info('👤 Admin: admin@tecnoweb.shop / Admin@2026!');
        $this->command->info('👤 Recepcionista: recepcionista@tecnoweb.shop / Recep@2026!');
        $this->command->info('👤 Tecnólogo: tecnologo@tecnoweb.shop / Tecno@2026!');
        $this->command->info('👤 Bioquímico: bioquimico@tecnoweb.shop / Bio@2026!');
        $this->command->info('👤 Médico (portal /medico): medico@tecnoweb.shop / Medico@2026!');
    }

    /**
     * @param  list<string>  $permissionNames
     */
    private function syncRoleByNames(Role $role, array $permissionNames): void
    {
        $permissions = Permission::whereIn('name', $permissionNames)->get();
        $role->syncPermissions($permissions);
    }

    private function seedStaffUser(
        string $email,
        string $name,
        string $plainPassword,
        Role $role,
    ): User {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($plainPassword),
                'email_verified_at' => now(),
            ],
        );

        if ($user->name !== $name) {
            $user->forceFill(['name' => $name])->save();
        }

        $user->syncRoles([$role]);
        UserPermissionSync::clearDirectPermissions($user);

        return $user;
    }
}
