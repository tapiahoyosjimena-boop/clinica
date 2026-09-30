<?php

namespace Database\Seeders;

use App\Domains\Auth\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Médico derivante adicional para demo VPS (portal /medico/login).
 *
 * Ejecutar: php artisan db:seed --class=DoctorsDemoSeeder --force
 *
 * No modifica medico@tecnoweb.shop (AuthSeeder).
 */
class DoctorsDemoSeeder extends Seeder
{
    public const EMAIL = 'medico1@tecnoweb.shop';

    public const PASSWORD = 'Medico@2026$';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::query()
            ->where('name', 'Médico')
            ->where('guard_name', 'web')
            ->first();

        if (! $role) {
            $this->command?->warn('DoctorsDemoSeeder: ejecute AuthSeeder primero (rol Médico).');

            return;
        }

        $user = User::query()->where('email', self::EMAIL)->first();

        if ($user) {
            $user->forceFill([
                'name' => 'Dra. Laura Fernández Soria',
                'phone' => '71345678',
                'gender' => 'femenino',
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } else {
            $user = User::create([
                'name' => 'Dra. Laura Fernández Soria',
                'email' => self::EMAIL,
                'phone' => '71345678',
                'gender' => 'femenino',
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ]);
        }

        $user->syncRoles([$role]);
        \App\Domains\Auth\Services\UserPermissionSync::clearDirectPermissions($user);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('✅ DoctorsDemoSeeder: médico demo listo.');
        $this->command?->line('   Nombre: '.$user->name);
        $this->command?->line('   Email (portal /medico): '.self::EMAIL);
        $this->command?->line('   Contraseña: '.self::PASSWORD);
    }
}
