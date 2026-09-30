<?php

namespace App\Domains\Auth\Filament\Resources;

use App\Domains\Auth\Filament\Resources\DoctorUserResource\Pages;
use App\Models\User;
use App\Support\GenderOptions;
use App\Support\PasswordPolicy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class DoctorUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Usuario Médico';

    protected static ?string $pluralModelLabel = 'Usuarios Médicos';

    /**
     * Solo usuarios con rol Médico y sin vínculo a paciente (portal médico).
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereDoesntHave('patient')
            ->whereHas('roles', fn (Builder $q) => $q->where('name', 'Médico'));
    }

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

    public static function canView($record): bool
    {
        return auth()->user()?->can('auth.access') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del médico')
                ->icon('heroicon-o-user')
                ->description('Cuenta para acceder al portal médico (/medico/login). El rol asignado es siempre «Médico» (medicina general / derivante).')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre completo')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo electrónico')
                        ->email()
                        ->required()
                        ->unique(table: 'users', column: 'email', ignoreRecord: true)
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('Celular')
                        ->tel()
                        ->required()
                        ->maxLength(32)
                        ->placeholder('Ej: 70000000')
                        ->helperText('Número de contacto del médico.'),

                    Forms\Components\Select::make('gender')
                        ->label('Sexo')
                        ->options(GenderOptions::labels())
                        ->native(false)
                        ->placeholder('Seleccionar')
                        ->nullable(),

                    Forms\Components\TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        ->revealable()
                        ->helperText(PasswordPolicy::helperText())
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->minLength(PasswordPolicy::minLength())
                        ->rules(PasswordPolicy::complexityRegexRules())
                        ->validationMessages(PasswordPolicy::filamentValidationMessages())
                        ->maxLength(PasswordPolicy::maxLength()),

                    Forms\Components\TextInput::make('password_confirmation')
                        ->label('Confirmar contraseña')
                        ->password()
                        ->revealable()
                        ->dehydrated(false)
                        ->same('password')
                        ->required(fn (string $operation): bool => $operation === 'create'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo (acceso portal)')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Correo copiado'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Celular')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('gender')
                    ->label('Sexo')
                    ->formatStateUsing(fn (?string $state): string => GenderOptions::label($state) ?? '—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rol')
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verificado')
                    ->boolean()
                    ->getStateUsing(fn ($record) => (bool) $record->email_verified_at)
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin usuarios médicos')
            ->emptyStateDescription('Cree cuentas para médicos derivantes; acceden al portal médico con correo y contraseña.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDoctorUsers::route('/'),
            'create' => Pages\CreateDoctorUser::route('/create'),
            'view' => Pages\ViewDoctorUser::route('/{record}'),
            'edit' => Pages\EditDoctorUser::route('/{record}/edit'),
        ];
    }
}
