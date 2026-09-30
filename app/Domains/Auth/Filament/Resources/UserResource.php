<?php

namespace App\Domains\Auth\Filament\Resources;

use App\Domains\Auth\Filament\Resources\UserResource\Pages;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Support\SystemPermissions;
use App\Domains\Auth\Traits\HasModulePermissions;
use App\Models\User;
use App\Support\GenderOptions;
use App\Support\PasswordPolicy;
use App\Support\SystemAdministratorGuard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Usuario del Sistema';

    protected static ?string $pluralModelLabel = 'Usuarios del Sistema';

    // ─── Scope: solo personal clínico (sin pacientes) ─────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereDoesntHave('patient')
            ->whereDoesntHave('roles', fn (Builder $q) => $q->whereIn('name', ['Médico', 'Paciente']));
    }

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

    public static function canView($record): bool
    {
        return auth()->user()?->can('auth.access') ?? false;
    }

    // ─── Formulario ──────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── Datos Personales ──────────────────────────────────────────────
            Forms\Components\Section::make('Datos Personales')
                ->icon('heroicon-o-user')
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
                        ->maxLength(32)
                        ->placeholder('Ej: 70000000')
                        ->helperText('Número de contacto del usuario.'),

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
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null
                        )
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

                    Forms\Components\TimePicker::make('hora_creacion')
                        ->label('Hora de creación')
                        ->default(now())
                        ->disabled()
                        ->dehydrated(true)
                        ->required(),

                    Forms\Components\DatePicker::make('fecha_creacion')
                        ->label('Fecha de creación')
                        ->default(now())
                        ->disabled()
                        ->dehydrated(true)
                        ->required(),
                ])
                ->columns(2),

            // ── Rol (permisos solo en Roles) ─────────────────────────────────
            Forms\Components\Section::make('Rol y acceso')
                ->icon('heroicon-o-shield-check')
                ->description('Portales, módulos y acciones se configuran en Administración → Roles. Aquí solo asigne el rol a esta cuenta.')
                ->schema([
                    Forms\Components\Select::make('role_id')
                        ->label('Rol asignado')
                        ->options(
                            Role::whereNotIn('name', ['Paciente', 'Médico'])
                                ->orderBy('name')
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->native(false)
                        ->live()
                        ->required()
                        ->dehydrated(false)
                        ->validationMessages([
                            'required' => 'Debe asignar un rol al usuario.',
                        ]),

                    Forms\Components\Placeholder::make('role_access_summary')
                        ->label('Acceso incluido en el rol')
                        ->content(function (Get $get) {
                            $roleId = $get('role_id');
                            if (! $roleId) {
                                return RoleResource::formatRoleAccessSummaryHtml(null);
                            }

                            $role = Role::with('permissions')->find($roleId);

                            return RoleResource::formatRoleAccessSummaryHtml($role);
                        })
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->footerActions([
                    Forms\Components\Actions\Action::make('edit_role_permissions')
                        ->label('Editar permisos de este rol')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (Get $get): ?string => filled($get('role_id'))
                            ? RoleResource::getUrl('edit', ['record' => $get('role_id')])
                            : null)
                        ->openUrlInNewTab()
                        ->visible(fn (Get $get): bool => filled($get('role_id'))),
                ]),
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

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo electrónico')
                    ->searchable()
                    ->sortable(),

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
                    ->color('primary'),

                Tables\Columns\IconColumn::make('panel_access')
                    ->label('Panel')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->can(SystemPermissions::ADMIN_PANEL))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

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

                Tables\Columns\TextColumn::make('hora_creacion')
                    ->label('Hora de creación')
                    ->dateTime('H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_creacion')
                    ->label('Fecha de creación')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label('Filtrar por rol')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (User $record): bool => ! SystemAdministratorGuard::canDelete($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records): void {
                            foreach ($records as $record) {
                                if ($record instanceof User && ! SystemAdministratorGuard::canDelete($record)) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Eliminación no permitida')
                                        ->body(SystemAdministratorGuard::deleteBlockedMessage($record))
                                        ->send();

                                    throw new Halt;
                                }
                            }
                        }),
                ]),
            ])
            ->emptyStateHeading('Sin usuarios del sistema')
            ->emptyStateDescription('Personal con acceso al panel (administración, recepción, laboratorio e imagen). Los médicos derivantes se gestionan en «Usuarios Médicos».')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
