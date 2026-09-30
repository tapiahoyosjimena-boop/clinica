<?php

use App\Domains\Auth\Services\UserPermissionSync;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        UserPermissionSync::reconcileStaffUsers();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // No reversible: los permisos directos anteriores no se restauran.
    }
};
