<?php

namespace App\Domains\Reactivos\Filament\Resources\ReagentResource\Pages;

use App\Domains\Reactivos\Filament\Resources\ReagentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReagent extends EditRecord
{
    protected static string $resource = ReagentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
