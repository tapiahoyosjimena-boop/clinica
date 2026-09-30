<?php

namespace App\Console\Commands;

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\Role;
use App\Models\User;
use App\Support\PasswordPolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Crea o restaura el administrador principal con una contraseña solicitada de forma oculta.
 */
class EnsureSystemAdminCommand extends Command
{
    protected $signature = 'clinica:ensure-admin
                            {--email=admin@tecnoweb.shop : Administrator email address}
                            {--name=Administrador de Sistema : Display name}';

    protected $description = 'Crea o restaura el administrador principal de forma segura.';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $name = (string) $this->option('name');

        $identityValidator = Validator::make(
            ['email' => $email, 'name' => $name],
            ['email' => ['required', 'email', 'max:255'], 'name' => ['required', 'string', 'max:255']],
        );

        if ($identityValidator->fails()) {
            foreach ($identityValidator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $adminRole = Role::query()->where('name', 'Administrador')->first();
        if ($adminRole === null) {
            $this->error('No existe el rol «Administrador». Ejecute primero: php artisan db:seed --force');

            return self::FAILURE;
        }

        $plainPassword = $this->secret('Ingrese una contraseña nueva para el administrador');
        $passwordConfirmation = $this->secret('Confirme la contraseña');

        $validator = Validator::make(
            ['password' => $plainPassword, 'password_confirmation' => $passwordConfirmation],
            ['password' => PasswordPolicy::rules(confirmed: true)],
            PasswordPolicy::validationMessages(),
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

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
            ['Contraseña', 'guardada; no se muestra'],
            ['Panel', url('/admin/login')],
        ]);

        $this->warn('Si sigue viendo 403, cierre sesión o borre cookies del sitio y vuelva a iniciar sesión.');

        return self::SUCCESS;
    }
}
