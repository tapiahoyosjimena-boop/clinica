<?php

namespace App\Domains\Auth\Filament\Resources\UserResource\Pages;

use App\Domains\Auth\Filament\Resources\UserResource;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Services\UserPermissionSync;
use App\Support\SystemAdministratorGuard;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(fn (): bool => ! SystemAdministratorGuard::canDelete($this->getRecord())),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterFill(): void
    {
        $this->data['role_id'] = $this->getRecord()->roles->first()?->id;
    }

    protected function afterSave(): void
    {
        $roleId = $this->data['role_id'] ?? null;
        if ($roleId) {
            $role = Role::find($roleId);
            if ($role) {
                $this->getRecord()->syncRoles([$role]);
            }
        }

        UserPermissionSync::clearDirectPermissions($this->getRecord());
    }
}
