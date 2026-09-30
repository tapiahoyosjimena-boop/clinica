<?php

namespace App\Domains\Auth\Filament\Resources\DoctorUserResource\Pages;

use App\Domains\Auth\Filament\Resources\DoctorUserResource;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Services\UserPermissionSync;
use Filament\Resources\Pages\CreateRecord;

class CreateDoctorUser extends CreateRecord
{
    protected static string $resource = DoctorUserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $role = Role::query()->where('name', 'Médico')->first();
        if ($role) {
            $this->getRecord()->syncRoles([$role]);
            UserPermissionSync::clearDirectPermissions($this->getRecord());
        }
    }
}
