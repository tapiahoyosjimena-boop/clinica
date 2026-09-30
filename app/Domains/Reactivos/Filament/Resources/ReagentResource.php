<?php

namespace App\Domains\Reactivos\Filament\Resources;

use App\Domains\Reactivos\Filament\Resources\ReagentResource\Pages;
use App\Domains\Reactivos\Filament\Resources\ReagentResource\RelationManagers;
use App\Domains\Reactivos\Models\Provider;
use App\Domains\Reactivos\Models\Reagent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReagentResource extends Resource
{
    protected static ?string $model = Reagent::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Reactivos';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Reactivo';

    protected static ?string $pluralModelLabel = 'Reactivos';

    // ─── Autorización ─────────────────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('reactivos.access') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('reactivos.access') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('reactivos.access') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('reactivos.access') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('reactivos.access') ?? false;
    }

    // ─── Formulario ──────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del reactivo')
                ->icon('heroicon-o-beaker')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->label('Descripción')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('unit')
                        ->label('Unidad de medida')
                        ->required()
                        ->maxLength(50)
                        ->placeholder('ej. mL, unidades, kit'),

                    Forms\Components\Select::make('provider_id')
                        ->label('Proveedor')
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->options(fn () => Provider::orderBy('name')->pluck('name', 'id')),
                ])
                ->columns(2),

            Forms\Components\Section::make('Inventario')
                ->icon('heroicon-o-archive-box')
                ->schema([
                    Forms\Components\TextInput::make('stock_quantity')
                        ->label('Stock inicial')
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->default(0)
                        ->helperText('Si es mayor a 0, se registrará automáticamente un movimiento de entrada por ese valor.'),

                    Forms\Components\TextInput::make('min_stock')
                        ->label('Stock mínimo')
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->default(10)
                        ->helperText('Cuando el stock esté por debajo de este valor, aparecerá la alerta "Stock bajo".'),

                    Forms\Components\DatePicker::make('expiration_date')
                        ->label('Fecha de vencimiento')
                        ->native(false)
                        ->nullable()
                        ->displayFormat('d/m/Y'),
                ])
                ->columns(3),
        ]);
    }

    // ─── Tabla ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('provider.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Unidad'),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable()
                    ->icon(fn (Reagent $r) => $r->isLowStock() ? 'heroicon-s-exclamation-triangle' : null)
                    ->iconPosition('after')
                    ->iconColor('warning')
                    ->tooltip(fn (Reagent $r) => $r->isLowStock() ? 'Stock bajo — clic para ver inventario' : null)
                    ->url(fn (Reagent $r) => $r->isLowStock()
                        ? static::getUrl('view', ['record' => $r]).'#inventario'
                        : null
                    ),

                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Mín.')
                    ->sortable()
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('expiration_date')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),

                // ── Indicadores visuales ──────────────────────────────────────
                Tables\Columns\BadgeColumn::make('expiry_alert')
                    ->label('Alerta vencimiento')
                    ->state(function (Reagent $r): ?string {
                        if ($r->isExpired()) {
                            return 'Vencido';
                        }
                        if ($r->isExpiringSoon()) {
                            return 'Por vencer';
                        }

                        return null;
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'Vencido' => 'danger',
                        'Por vencer' => 'warning',
                        default => 'gray',
                    })
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\Filter::make('stock_bajo')
                    ->label('Stock bajo')
                    ->query(fn (Builder $q) => $q->whereColumn('stock_quantity', '<', 'min_stock'))
                    ->toggle(),

                Tables\Filters\Filter::make('vencidos')
                    ->label('Vencidos')
                    ->query(fn (Builder $q) => $q->whereDate('expiration_date', '<', now()))
                    ->toggle(),

                Tables\Filters\Filter::make('por_vencer')
                    ->label('Por vencer (30 días)')
                    ->query(fn (Builder $q) => $q
                        ->whereDate('expiration_date', '>=', now())
                        ->whereDate('expiration_date', '<=', now()->addDays(30)))
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin reactivos en inventario')
            ->emptyStateDescription('Registre reactivos y proveedores para controlar stock y vencimientos.')
            ->emptyStateIcon('heroicon-o-beaker')
            ->defaultSort('name');
    }

    // ─── Infolist ─────────────────────────────────────────────────────────────

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Información del reactivo')
                ->icon('heroicon-o-beaker')
                ->description('Datos generales del reactivo y su proveedor.')
                ->schema([
                    TextEntry::make('name')->label('Nombre'),
                    TextEntry::make('provider.name')->label('Proveedor'),
                    TextEntry::make('unit')->label('Unidad'),
                    TextEntry::make('description')->label('Descripción')->columnSpanFull()->placeholder('—'),
                ])
                ->columns(['default' => 1, 'sm' => 2]),

            InfoSection::make('Inventario')
                ->id('inventario')
                ->icon('heroicon-o-archive-box')
                ->description('Estado actual del stock y fecha de vencimiento.')
                ->schema([
                    TextEntry::make('stock_quantity')
                        ->label('Stock actual')
                        ->badge()
                        ->color(fn (Reagent $r) => $r->isLowStock() ? 'warning' : 'success'),

                    TextEntry::make('min_stock')
                        ->label('Stock mínimo'),

                    TextEntry::make('expiration_date')
                        ->label('Fecha de vencimiento')
                        ->date('d/m/Y')
                        ->placeholder('Sin fecha registrada')
                        ->badge()
                        ->color(function (Reagent $r): string {
                            if ($r->isExpired()) {
                                return 'danger';
                            }
                            if ($r->isExpiringSoon()) {
                                return 'warning';
                            }

                            return 'success';
                        }),
                ])
                ->columns(['default' => 1, 'sm' => 2, 'lg' => 3]),
        ]);
    }

    // ─── Query ────────────────────────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with('provider');
    }

    // ─── Páginas ──────────────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            RelationManagers\StockMovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReagents::route('/'),
            'create' => Pages\CreateReagent::route('/create'),
            'view' => Pages\ViewReagent::route('/{record}'),
            'edit' => Pages\EditReagent::route('/{record}/edit'),
        ];
    }
}
