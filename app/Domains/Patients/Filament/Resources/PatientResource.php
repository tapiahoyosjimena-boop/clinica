<?php

namespace App\Domains\Patients\Filament\Resources;

use App\Domains\Patients\Filament\Resources\PatientResource\Pages;
use App\Domains\Patients\Models\Patient;
use App\Support\DbLikeInsensitive;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Pacientes';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Paciente';

    protected static ?string $pluralModelLabel = 'Pacientes';

    // ─── Autorización ─────────────────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('patients.access') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('patients.access') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('patients.access') ?? false;
    }

    public static function canDelete($record): bool
    {
        return static::userMayDeletePatientRecord();
    }

    public static function canDeleteAny(): bool
    {
        return static::userMayDeletePatientRecord();
    }

    public static function canRestore(Model $record): bool
    {
        return static::userMayDeletePatientRecord();
    }

    public static function canRestoreAny(): bool
    {
        return static::userMayDeletePatientRecord();
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::userMayDeletePatientRecord();
    }

    public static function canForceDeleteAny(): bool
    {
        return static::userMayDeletePatientRecord();
    }

    /** Borrar / restaurar / eliminar definitivo: solo Administrador y Recepcionista con acceso al módulo. */
    protected static function userMayDeletePatientRecord(): bool
    {
        $user = auth()->user();
        if (! $user?->can('patients.access')) {
            return false;
        }

        return $user->hasAnyRole(['Administrador', 'Recepcionista']);
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('patients.access') ?? false;
    }

    // ─── Formulario ──────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── Identificación ────────────────────────────────────────────────
            Forms\Components\Section::make('Identificación')
                ->icon('heroicon-o-identification')
                ->schema([
                    Forms\Components\TextInput::make('ci')
                        ->label('Cédula de Identidad (CI)')
                        ->required()
                        ->unique(table: 'patients', column: 'ci', ignoreRecord: true)
                        ->validationMessages([
                            'unique' => 'Esta cédula de identidad ya está registrada.',
                            'required' => 'La cédula de identidad es obligatoria.',
                            'max' => 'La cédula no puede tener más de :max caracteres.',
                        ])
                        ->maxLength(20)
                        ->placeholder('Ej: 12345678')
                        ->helperText('Debe ser único en el sistema.')
                        ->prefixIcon('heroicon-o-identification'),

                    Forms\Components\TextInput::make('first_name')
                        ->label('Nombre')
                        ->placeholder('Ej: Juan')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('last_name')
                        ->label('Apellido')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\DatePicker::make('birth_date')
                        ->label('Fecha de nacimiento')
                        ->required()
                        ->maxDate(now())
                        ->displayFormat('d/m/Y')
                        ->native(false),

                    Forms\Components\Select::make('gender')
                        ->label('Género')
                        ->required()
                        ->options([
                            'masculino' => 'Masculino',
                            'femenino' => 'Femenino',
                            'binario' => 'Binario',
                            'otro' => 'Otro',
                        ]),
                ])
                ->columns(2),

            // ── Contacto ─────────────────────────────────────────────────────
            Forms\Components\Section::make('Datos de Contacto')
                ->icon('heroicon-o-phone')
                ->schema([
                    Forms\Components\TextInput::make('phone')
                        ->label('Teléfono')
                        ->required()
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo electrónico')
                        ->email()
                        ->required()
                        ->unique(table: 'patients', column: 'email', ignoreRecord: true)
                        ->validationMessages([
                            'required' => 'El correo electrónico es obligatorio para crear la cuenta del paciente.',
                            'unique' => 'Este correo electrónico ya está registrado en otro paciente.',
                            'email' => 'Ingrese un correo electrónico válido.',
                        ])
                        ->maxLength(255),

                    Forms\Components\Textarea::make('address')
                        ->label('Dirección')
                        ->nullable()
                        ->rows(2)
                        ->maxLength(500)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // ── Historial Clínico ─────────────────────────────────────────────
            Forms\Components\Section::make('Historial Clínico')
                ->icon('heroicon-o-clipboard-document-list')
                ->description('Notas médicas generales del paciente.')
                ->schema([
                    Forms\Components\Textarea::make('medical_history_notes')
                        ->label('Notas del historial clínico')
                        ->nullable()
                        ->rows(5)
                        ->maxLength(5000)
                        ->placeholder('Alergias, antecedentes médicos, enfermedades crónicas...')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ─── Tabla ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ci')
                    ->label('CI')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Paciente')
                    ->getStateUsing(fn (Patient $record) => $record->first_name.' '.$record->last_name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function ($q) use ($search) {
                            DbLikeInsensitive::where($q, 'first_name', $search)
                                ->orWhere(fn ($b) => DbLikeInsensitive::where($b, 'last_name', $search));
                        });
                    })
                    ->sortable(['last_name', 'first_name']),

                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Fecha nac.')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('age')
                    ->label('Edad')
                    ->getStateUsing(fn (Patient $record) => $record->birth_date->age.' años')
                    ->sortable(false),

                Tables\Columns\BadgeColumn::make('gender')
                    ->label('Género')
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->colors([
                        'info' => 'masculino',
                        'success' => 'femenino',
                        'warning' => 'otro',
                    ]),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('medical_history_notes')
                    ->label('Historial')
                    ->boolean()
                    ->getStateUsing(fn (Patient $record) => filled($record->medical_history_notes))
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('info')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('fecha de creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('created_this_month')
                    ->label('Registrados este mes')
                    ->query(fn (Builder $query) => $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)),

                Tables\Filters\Filter::make('has_history')
                    ->label('Con historial clínico')
                    ->query(fn (Builder $query) => $query->whereNotNull('medical_history_notes')
                        ->where('medical_history_notes', '<>', '')),

                Tables\Filters\TrashedFilter::make()
                    ->label('Incluir eliminados'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Patient $record): bool => static::canDelete($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No hay pacientes registrados')
            ->emptyStateDescription('Registre un paciente para iniciar órdenes, muestras y facturación.')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'view' => Pages\ViewPatient::route('/{record}'),
            'edit' => Pages\EditPatient::route('/{record}/edit'),
        ];
    }
}
