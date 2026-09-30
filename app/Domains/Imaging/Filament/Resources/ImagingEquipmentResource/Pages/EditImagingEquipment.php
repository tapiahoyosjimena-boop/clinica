<?php

namespace App\Domains\Imaging\Filament\Resources\ImagingEquipmentResource\Pages;

use App\Domains\Imaging\Filament\Resources\ImagingEquipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditImagingEquipment extends EditRecord
{
    protected static string $resource = ImagingEquipmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Ver'),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => ImagingEquipmentResource::canDelete($this->record)),
            Actions\RestoreAction::make()
                ->visible(fn (): bool => ImagingEquipmentResource::canRestore($this->record)),
        ];
    }
}
