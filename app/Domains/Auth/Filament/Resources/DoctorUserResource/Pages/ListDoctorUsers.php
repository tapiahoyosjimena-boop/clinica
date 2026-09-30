<?php

namespace App\Domains\Auth\Filament\Resources\DoctorUserResource\Pages;

use App\Domains\Auth\Filament\Resources\DoctorUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDoctorUsers extends ListRecords
{
    protected static string $resource = DoctorUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
