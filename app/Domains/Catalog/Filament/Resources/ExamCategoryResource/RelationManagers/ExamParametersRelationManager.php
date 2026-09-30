<?php

namespace App\Domains\Catalog\Filament\Resources\ExamCategoryResource\RelationManagers;

use App\Domains\Catalog\Models\ExamCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ExamParametersRelationManager extends RelationManager
{
    protected static string $relationship = 'parameters';

    protected static ?string $title = 'Parámetros analíticos';

    protected static ?string $modelLabel = 'Parámetro';

    protected static ?string $pluralModelLabel = 'Parámetros';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof ExamCategory
            && $ownerRecord->supportsAnalyticalParameters();
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Parámetro')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre del parámetro')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Ej: Hemoglobina'),

                    Forms\Components\TextInput::make('unit')
                        ->label('Unidad de medida')
                        ->nullable()
                        ->maxLength(50)
                        ->placeholder('Ej: g/dL'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Valores de Referencia')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    Forms\Components\TextInput::make('reference_min')
                        ->label('Ref. mínimo')
                        ->numeric()
                        ->integer()
                        ->nullable()
                        ->step(1)
                        ->placeholder('Ej: 12'),

                    Forms\Components\TextInput::make('reference_max')
                        ->label('Ref. máximo')
                        ->numeric()
                        ->integer()
                        ->nullable()
                        ->step(1)
                        ->placeholder('Ej: 18'),

                    Forms\Components\TextInput::make('critical_min')
                        ->label('Crítico mínimo')
                        ->numeric()
                        ->integer()
                        ->nullable()
                        ->step(1)
                        ->placeholder('Ej: 7'),

                    Forms\Components\TextInput::make('critical_max')
                        ->label('Crítico máximo')
                        ->numeric()
                        ->integer()
                        ->nullable()
                        ->step(1)
                        ->placeholder('Ej: 20'),
                ])
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Parámetro')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Unidad')
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('reference_min')
                    ->label('Ref. mín.')
                    ->placeholder('—')
                    ->numeric(decimalPlaces: 0),

                Tables\Columns\TextColumn::make('reference_max')
                    ->label('Ref. máx.')
                    ->placeholder('—')
                    ->numeric(decimalPlaces: 0),

                Tables\Columns\TextColumn::make('critical_min')
                    ->label('Crít. mín.')
                    ->placeholder('—')
                    ->numeric(decimalPlaces: 0)
                    ->color('danger'),

                Tables\Columns\TextColumn::make('critical_max')
                    ->label('Crít. máx.')
                    ->placeholder('—')
                    ->numeric(decimalPlaces: 0)
                    ->color('danger'),
            ])
            ->defaultSort('name')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
