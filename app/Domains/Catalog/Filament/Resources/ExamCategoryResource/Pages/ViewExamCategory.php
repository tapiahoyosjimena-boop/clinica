<?php

namespace App\Domains\Catalog\Filament\Resources\ExamCategoryResource\Pages;

use App\Domains\Catalog\Filament\Resources\ExamCategoryResource;
use Filament\Actions;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewExamCategory extends ViewRecord
{
    protected static string $resource = ExamCategoryResource::class;

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

            Section::make('Datos de la Categoría')
                ->icon('heroicon-o-book-open')
                ->description('Tipo, nombre y estado de la categoría de examen.')
                ->columns(['default' => 1, 'sm' => 2, 'lg' => 3])
                ->schema([
                    TextEntry::make('type')
                        ->label('Tipo')
                        ->formatStateUsing(fn (string $state) => ucfirst($state))
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'laboratorio' => 'info',
                            'imagen' => 'warning',
                            default => 'gray',
                        }),

                    TextEntry::make('name')
                        ->label('Nombre'),

                    IconEntry::make('is_active')
                        ->label('Estado')
                        ->boolean()
                        ->trueIcon('heroicon-o-check-circle')
                        ->falseIcon('heroicon-o-x-circle')
                        ->trueColor('success')
                        ->falseColor('danger'),

                    TextEntry::make('description')
                        ->label('Descripción')
                        ->placeholder('Sin descripción registrada')
                        ->columnSpanFull(),
                ]),

            Section::make('Configuración de resultados')
                ->icon('heroicon-o-clipboard-document-check')
                ->description(fn (): string => $this->getRecord()->supportsAnalyticalParameters()
                    ? 'Esta categoría admite parámetros analíticos (valores numéricos con rangos) en la pestaña correspondiente.'
                    : 'Los estudios de imagen no usan parámetros en catálogo. Defina equipo y requisitos en cada examen; el tecnólogo redactará el informe y adjuntará el archivo al confirmar el estudio.')
                ->schema([
                    TextEntry::make('type')
                        ->label('Modelo de resultado')
                        ->formatStateUsing(fn (string $state): string => $state === 'laboratorio'
                            ? 'Parámetros analíticos + informe clínico'
                            : 'Informe libre + archivo del estudio')
                        ->badge()
                        ->color(fn (string $state): string => $state === 'laboratorio' ? 'info' : 'warning'),
                ]),

            Section::make('Registro')
                ->icon('heroicon-o-clock')
                ->description('Fechas de creación y última modificación.')
                ->columns(['default' => 1, 'sm' => 2])
                ->compact()
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Creada')
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('updated_at')
                        ->label('Última modificación')
                        ->dateTime('d/m/Y H:i'),
                ]),
        ]);
    }
}
