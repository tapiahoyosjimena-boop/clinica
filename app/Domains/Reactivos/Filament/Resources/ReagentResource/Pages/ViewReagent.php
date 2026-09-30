<?php

namespace App\Domains\Reactivos\Filament\Resources\ReagentResource\Pages;

use App\Domains\Reactivos\Filament\Resources\ReagentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReagent extends ViewRecord
{
    protected static string $resource = ReagentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
