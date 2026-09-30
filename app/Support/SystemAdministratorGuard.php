<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SystemAdministratorGuard
{
    public const PRIMARY_EMAIL = 'admin@tecnoweb.shop';

    public static function administratorsQuery(): Builder
    {
        return User::query()->role('Administrador');
    }

    public static function countAdministrators(): int
    {
        return static::administratorsQuery()->count();
    }

    public static function isPrimaryAdministrator(User $user): bool
    {
        return strcasecmp($user->email, self::PRIMARY_EMAIL) === 0;
    }

    /**
     * No permitir eliminar al admin principal ni al último Administrador del sistema.
     */
    public static function canDelete(User $user): bool
    {
        if (static::isPrimaryAdministrator($user)) {
            return false;
        }

        if (! $user->hasRole('Administrador')) {
            return true;
        }

        return static::countAdministrators() > 1;
    }

    public static function deleteBlockedMessage(User $user): string
    {
        if (static::isPrimaryAdministrator($user)) {
            return 'No puede eliminar la cuenta administrador principal ('.self::PRIMARY_EMAIL.'). Use otro administrador para gestionar usuarios.';
        }

        return 'No puede eliminar al último usuario con rol Administrador. Asigne el rol a otra cuenta antes de borrar este registro.';
    }
}
