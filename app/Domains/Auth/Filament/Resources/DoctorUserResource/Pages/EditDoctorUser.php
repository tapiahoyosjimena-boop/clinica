<?php

namespace App\Domains\Auth\Filament\Resources\DoctorUserResource\Pages;

use App\Domains\Auth\Filament\Resources\DoctorUserResource;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Services\UserPermissionSync;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDoctorUser extends EditRecord
{
    protected static string $resource = DoctorUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $role = Role::query()->where('name', 'Médico')->first();
        if ($role) {
            $this->getRecord()->syncRoles([$role]);
            UserPermissionSync::clearDirectPermissions($this->getRecord());
        }
    }
}
