<?php

namespace App\Domains\Auth\Filament\Resources;

use App\Domains\Auth\Filament\Resources\RoleResource\Pages;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Traits\HasModulePermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    use HasModulePermissions;

    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Rol';

    protected static ?string $pluralModelLabel = 'Roles';

    // ─── Autorización ─────────────────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('auth.access') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('auth.access') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('auth.access') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('auth.access') ?? false;
    }

    // ─── Formulario ──────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del rol')
                ->icon('heroicon-o-shield-check')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre del rol')
                        ->required()
                        ->unique(table: 'roles', column: 'name', ignoreRecord: true)
                        ->maxLength(255),

                    Forms\Components\Hidden::make('guard_name')
                        ->default('web'),
                ])
                ->columns(1),

            ...static::rolePermissionFormSchema(),
        ]);
    }

    // ─── Tabla ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del Rol')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permisos')
                    ->counts('permissions')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (Role $record): bool => in_array($record->name, [
                        'Administrador',
                        'Paciente',
                        'Médico',
                    ], true)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin roles configurados')
            ->emptyStateDescription('Los roles agrupan permisos; el sistema suele crearlos al ejecutar los seeders.')
            ->emptyStateIcon('heroicon-o-shield-check');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
