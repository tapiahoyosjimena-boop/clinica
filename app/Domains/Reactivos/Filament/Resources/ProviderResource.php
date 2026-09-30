<?php

namespace App\Domains\Reactivos\Filament\Resources;

use App\Domains\Reactivos\Filament\Resources\ProviderResource\Pages;
use App\Domains\Reactivos\Models\Provider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Reactivos';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Proveedor';

    protected static ?string $pluralModelLabel = 'Proveedores';

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
            Forms\Components\Section::make('Datos del proveedor')
                ->icon('heroicon-o-truck')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('contact_person')
                        ->label('Persona de contacto')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('Teléfono')
                        ->required()
                        ->tel()
                        ->maxLength(50),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo electrónico')
                        ->email()
                        ->maxLength(255),

                    Forms\Components\Textarea::make('address')
                        ->label('Dirección')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
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

                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Contacto')
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('reagents_count')
                    ->label('Reactivos')
                    ->counts('reagents')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin proveedores')
            ->emptyStateDescription('Agregue proveedores para asociarlos a reactivos y compras.')
            ->emptyStateIcon('heroicon-o-building-storefront')
            ->defaultSort('name');
    }

    // ─── Páginas ──────────────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProviders::route('/'),
            'create' => Pages\CreateProvider::route('/create'),
            'view' => Pages\ViewProvider::route('/{record}'),
            'edit' => Pages\EditProvider::route('/{record}/edit'),
        ];
    }
}
