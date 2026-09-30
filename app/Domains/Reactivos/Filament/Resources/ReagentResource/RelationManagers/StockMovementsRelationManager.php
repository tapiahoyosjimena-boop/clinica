<?php

namespace App\Domains\Reactivos\Filament\Resources\ReagentResource\RelationManagers;

use App\Domains\Reactivos\Models\Reagent;
use App\Domains\Reactivos\Models\StockMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static ?string $title = 'Movimientos de stock';

    protected static ?string $modelLabel = 'Movimiento';

    protected static ?string $pluralModelLabel = 'Movimientos';

    public function isReadOnly(): bool
    {
        return false;
    }

    // ─── Formulario ──────────────────────────────────────────────────────────

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->label('Tipo')
                ->required()
                ->native(false)
                ->options([
                    'entrada' => 'Entrada',
                    'salida' => 'Salida',
                ]),

            Forms\Components\TextInput::make('quantity')
                ->label('Cantidad')
                ->required()
                ->integer()
                ->minValue(1),

            Forms\Components\Textarea::make('notes')
                ->label('Notas')
                ->rows(2)
                ->columnSpanFull(),
        ])->columns(2);
    }

    // ─── Tabla ────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => $state === 'entrada' ? 'Entrada' : 'Salida')
                    ->color(fn ($state) => $state === 'entrada' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario'),

                Tables\Columns\TextColumn::make('movement_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(60)
                    ->placeholder('—'),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Registrar movimiento')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        $data['movement_date'] = now();

                        return $data;
                    })
                    ->after(function (StockMovement $record): void {
                        /** @var Reagent $reagent */
                        $reagent = $this->getOwnerRecord();

                        $newStock = $record->type === 'entrada'
                            ? $reagent->stock_quantity + $record->quantity
                            : $reagent->stock_quantity - $record->quantity;

                        if ($newStock < 0) {
                            $record->delete();

                            Notification::make()
                                ->title('Stock insuficiente')
                                ->body('La salida registrada supera el stock disponible. No se realizó el movimiento.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $reagent->update(['stock_quantity' => $newStock]);

                        Notification::make()
                            ->title('Movimiento registrado')
                            ->body('Stock actualizado a '.$newStock.' '.$reagent->unit.'.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('movement_date', 'desc');
    }
}
