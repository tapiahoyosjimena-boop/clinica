<?php

namespace App\Console\Commands;

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Restaura la cuenta administrador principal (útil si se eliminó por error desde el panel).
 */
class EnsureSystemAdminCommand extends Command
{
    protected $signature = 'clinica:ensure-admin
                            {--email=admin@tecnoweb.shop : Correo del administrador principal}
                            {--password=Admin@2026! : Contraseña en texto plano}
                            {--name=Administrador de Sistema : Nombre visible en el panel}';

    protected $description = 'Crea o restaura el usuario Administrador principal con rol y permisos completos.';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $plainPassword = (string) $this->option('password');
        $name = (string) $this->option('name');

        $adminRole = Role::query()->where('name', 'Administrador')->first();
        if ($adminRole === null) {
            $this->error('No existe el rol «Administrador». Ejecute antes: php artisan db:seed --class=App\\Domains\\Auth\\Seeders\\AuthSeeder');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($plainPassword),
                'email_verified_at' => now(),
            ]);
            $this->info("Usuario creado: {$email}");
        } else {
            $user->forceFill([
                'name' => $name,
                'password' => Hash::make($plainPassword),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
            $this->info("Usuario restaurado: {$email}");
        }

        $user->syncRoles([$adminRole]);
        $user->syncPermissions(Permission::all());

        $legacy = User::query()->where('email', 'admin@clinicanorte.com')->first();
        if ($legacy !== null && $legacy->id !== $user->id) {
            $legacy->syncRoles([]);
            $legacy->syncPermissions([]);
            $this->warn('Cuenta legacy admin@clinicanorte.com: se quitaron roles y permisos (tiene registros vinculados; no se borró el usuario).');
        }

        $this->newLine();
        $this->table(['Campo', 'Valor'], [
            ['Correo', $email],
            ['Contraseña', $plainPassword],
            ['Panel', url('/admin/login')],
        ]);

        $this->warn('Si sigue viendo 403, cierre sesión o borre cookies del sitio y vuelva a iniciar sesión.');

        return self::SUCCESS;
    }
}
