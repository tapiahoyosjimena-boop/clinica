<?php

namespace App\Domains\Auth\Filament\Resources\RoleResource\Pages;

use App\Domains\Auth\Filament\Resources\RoleResource;
use App\Domains\Auth\Traits\HasModulePermissions;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    use HasModulePermissions;

    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(fn (): bool => in_array($this->getRecord()->name, [
                    'Administrador',
                    'Paciente',
                    'Médico',
                ], true)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return static::mergePermissionToggleStateIntoFormData($data, $this->getRecord());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return static::stripVirtualToggleKeys($data);
    }

    protected function afterSave(): void
    {
        $state = $this->form->getState();
        $usersUpdated = $this->syncRolePermissions($this->getRecord()->fresh(), $state);

        if (static::formToggleIsEnabled($state, 'apply_to_users')) {
            Notification::make()
                ->title('Permisos del rol guardados')
                ->body("Se actualizaron {$usersUpdated} usuario(s): ahora rigen solo los permisos de este rol.")
                ->success()
                ->send();
        }
    }
}
