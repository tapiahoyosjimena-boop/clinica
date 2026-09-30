<?php

namespace App\Domains\Imaging\Filament\Resources\ImagingEquipmentResource\Pages;

use App\Domains\Imaging\Filament\Resources\ImagingEquipmentResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewImagingEquipment extends ViewRecord
{
    protected static string $resource = ImagingEquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => ImagingEquipmentResource::canDelete($this->record)),
            Actions\RestoreAction::make()
                ->visible(fn (): bool => ImagingEquipmentResource::canRestore($this->record)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Información del equipo')
                ->icon('heroicon-o-computer-desktop')
                ->description('Datos generales del equipo de imagen.')
                ->columns(['default' => 1, 'sm' => 2])
                ->schema([
                    TextEntry::make('name')
                        ->label('Nombre'),

                    TextEntry::make('type')
                        ->label('Tipo')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => match ($state) {
                            'ecógrafo' => 'Ecógrafo',
                            'rayos_x' => 'Rayos X',
                            'tomógrafo' => 'Tomógrafo',
                            'otro' => 'Otro',
                            default => ucfirst($state),
                        })
                        ->color(fn (string $state) => match ($state) {
                            'ecógrafo' => 'info',
                            'rayos_x' => 'warning',
                            'tomógrafo' => 'success',
                            default => 'gray',
                        }),

                    TextEntry::make('status')
                        ->label('Estado')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => match ($state) {
                            'disponible' => 'Disponible',
                            'mantenimiento' => 'En mantenimiento',
                            'fuera_de_servicio' => 'Fuera de servicio',
                            default => ucfirst($state),
                        })
                        ->color(fn (string $state) => match ($state) {
                            'disponible' => 'success',
                            'mantenimiento' => 'warning',
                            'fuera_de_servicio' => 'danger',
                            default => 'gray',
                        }),

                    TextEntry::make('description')
                        ->label('Descripción')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),

            Section::make('Registro')
                ->icon('heroicon-o-calendar')
                ->description('Fechas de creación y modificación.')
                ->columns(['default' => 1, 'sm' => 2])
                ->compact()
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Creado el')
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('updated_at')
                        ->label('Última actualización')
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('deleted_at')
                        ->label('Eliminado el')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('—')
                        ->visible(fn (): bool => $this->record->trashed()),
                ]),
        ]);
    }
}
