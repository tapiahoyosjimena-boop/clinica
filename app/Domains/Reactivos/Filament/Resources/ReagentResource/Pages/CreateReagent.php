<?php

namespace App\Domains\Reactivos\Filament\Resources\ReagentResource\Pages;

use App\Domains\Reactivos\Filament\Resources\ReagentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReagent extends CreateRecord
{
    protected static string $resource = ReagentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
