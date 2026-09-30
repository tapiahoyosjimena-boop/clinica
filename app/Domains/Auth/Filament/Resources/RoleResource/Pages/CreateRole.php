<?php

namespace App\Domains\Auth\Filament\Resources\RoleResource\Pages;

use App\Domains\Auth\Filament\Resources\RoleResource;
use App\Domains\Auth\Traits\HasModulePermissions;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    use HasModulePermissions;

    protected static string $resource = RoleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return static::stripVirtualToggleKeys($data);
    }

    protected function afterCreate(): void
    {
        $this->syncRolePermissions($this->getRecord(), $this->form->getState());
    }
}
