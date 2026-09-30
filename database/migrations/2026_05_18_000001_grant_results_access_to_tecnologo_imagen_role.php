<?php

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $perm = Permission::firstOrCreate(
            ['name' => 'results.access', 'guard_name' => 'web']
        );

        $role = Role::query()
            ->where('name', 'Tecnólogo de Imagen')
            ->where('guard_name', 'web')
            ->first();

        if ($role instanceof Role && ! $role->hasPermissionTo($perm)) {
            $role->givePermissionTo($perm);
        }

        if ($role instanceof Role) {
            foreach (User::role('Tecnólogo de Imagen')->cursor() as $user) {
                if (! $user->can('results.access')) {
                    $user->givePermissionTo($perm);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $perm = Permission::query()
            ->where('name', 'results.access')
            ->where('guard_name', 'web')
            ->first();

        if (! $perm instanceof Permission) {
            return;
        }

        $role = Role::query()
            ->where('name', 'Tecnólogo de Imagen')
            ->where('guard_name', 'web')
            ->first();

        if ($role instanceof Role) {
            $role->revokePermissionTo($perm);
        }

        if ($role instanceof Role) {
            foreach (User::role('Tecnólogo de Imagen')->cursor() as $user) {
                if ($user->can('results.access')) {
                    $user->revokePermissionTo($perm);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
