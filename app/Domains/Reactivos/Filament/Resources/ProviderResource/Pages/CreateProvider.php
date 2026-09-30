<?php

namespace App\Domains\Reactivos\Filament\Resources\ProviderResource\Pages;

use App\Domains\Reactivos\Filament\Resources\ProviderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProvider extends CreateRecord
{
    protected static string $resource = ProviderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
