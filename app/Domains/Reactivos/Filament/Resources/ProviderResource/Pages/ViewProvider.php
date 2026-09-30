<?php

namespace App\Domains\Reactivos\Filament\Resources\ProviderResource\Pages;

use App\Domains\Reactivos\Filament\Resources\ProviderResource;
use App\Domains\Reactivos\Models\Provider;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewProvider extends ViewRecord
{
    protected static string $resource = ProviderResource::class;

    protected function resolveRecord(int|string $key): Provider
    {
        return Provider::query()->withCount('reagents')->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Datos del proveedor')
                ->icon('heroicon-o-truck')
                ->description('Información de contacto y ubicación.')
                ->columns(['default' => 1, 'sm' => 2])
                ->schema([
                    TextEntry::make('name')
                        ->label('Nombre')
                        ->columnSpanFull(),

                    TextEntry::make('contact_person')
                        ->label('Persona de contacto')
                        ->placeholder('—'),

                    TextEntry::make('phone')
                        ->label('Teléfono'),

                    TextEntry::make('email')
                        ->label('Correo electrónico')
                        ->placeholder('—'),

                    TextEntry::make('address')
                        ->label('Dirección')
                        ->placeholder('—')
                        ->columnSpanFull(),

                    TextEntry::make('reagents_count')
                        ->label('Reactivos asociados')
                        ->badge()
                        ->color('gray'),
                ]),

            Section::make('Registro')
                ->icon('heroicon-o-calendar')
                ->compact()
                ->collapsible()
                ->collapsed()
                ->columns(['default' => 1, 'sm' => 2])
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Creado el')
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('updated_at')
                        ->label('Última actualización')
                        ->dateTime('d/m/Y H:i'),
                ]),
        ]);
    }
}
