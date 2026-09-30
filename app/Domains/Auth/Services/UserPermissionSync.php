<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

class UserPermissionSync
{
    /**
     * Permisos directos adicionales (no los del rol).
     */
    public static function hasCustomDirectPermissions(User $user): bool
    {
        $user->loadMissing(['roles.permissions', 'permissions']);

        $roleNames = $user->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->pluck('name')
            ->unique();

        return $user->permissions
            ->pluck('name')
            ->diff($roleNames)
            ->isNotEmpty();
    }

    public static function clearDirectPermissions(User $user): void
    {
        $user->syncPermissions([]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  list<string>  $permissionNames
     */
    public static function syncDirectPermissions(User $user, array $permissionNames): void
    {
        $permissions = Permission::query()
            ->whereIn('name', $permissionNames)
            ->get();

        $user->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Tras guardar un rol: quita permisos directos de sus usuarios para que rija solo el rol.
     */
    public static function applyRoleToUsers(Role $role): int
    {
        $count = 0;

        foreach (User::role($role->name)->get() as $user) {
            static::clearDirectPermissions($user);
            $count++;
        }

        return $count;
    }

    /**
     * @return Collection<int, string>
     */
    public static function permissionNamesFromRole(?Role $role): Collection
    {
        if (! $role) {
            return collect();
        }

        $role->loadMissing('permissions');

        return $role->permissions->pluck('name');
    }

    /**
     * Limpia permisos directos de todo el personal con rol de panel (no Paciente/Médico).
     */
    public static function reconcileStaffUsers(): int
    {
        $count = 0;

        foreach (['Administrador', 'Recepcionista', 'Bioquímico', 'Tecnólogo de Imagen'] as $roleName) {
            $roleExists = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->exists();

            if (! $roleExists) {
                continue;
            }

            foreach (User::role($roleName)->get() as $user) {
                static::clearDirectPermissions($user);
                $count++;
            }
        }

        return $count;
    }
}
