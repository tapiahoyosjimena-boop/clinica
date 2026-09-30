<?php

namespace App\Console\Commands;

use App\Domains\Auth\Services\UserPermissionSync;
use Illuminate\Console\Command;

class ReconcileUserPermissionsCommand extends Command
{
    protected $signature = 'permissions:reconcile-users
                            {--force : Ejecutar sin confirmación}';

    protected $description = 'Elimina permisos directos del personal staff para que rijan solo los permisos de su rol';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm(
            '¿Quitar permisos directos de Administrador, Recepcionista, Bioquímico y Tecnólogo? (el acceso quedará definido solo por el rol)',
            true
        )) {
            $this->info('Operación cancelada.');

            return self::SUCCESS;
        }

        $count = UserPermissionSync::reconcileStaffUsers();

        $this->info("✅ {$count} usuario(s) actualizados: permisos directos eliminados; vigente el rol asignado.");

        return self::SUCCESS;
    }
}
