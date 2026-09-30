<?php

namespace App\Domains\Imaging\Filament\Resources;

use App\Domains\Imaging\Filament\Resources\ImagingStudyResource\Pages;
use App\Domains\Imaging\Models\ImagingEquipment;
use App\Domains\Imaging\Models\ImagingStatusHistory;
use App\Domains\Imaging\Models\ImagingStudy;
use App\Domains\Imaging\Support\ImagingResultFileSupport;
use App\Domains\Orders\Models\Order;
use App\Models\User;
use App\Support\DbLikeInsensitive;
use App\Support\ResponsibleClinicalStaffScoping;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ImagingStudyResource extends Resource
{
    protected static ?string $model = ImagingStudy::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Estudios de Imagen';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Estudio de imagen';

    protected static ?string $pluralModelLabel = 'Estudios de imagen';

    // ─── Autorización ─────────────────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('imaging.access') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('imaging.access') ?? false;
    }

    public static function canEdit($record): bool
    {
        if (! (auth()->user()?->can('imaging.access') ?? false)) {
            return false;
        }

        // Completado: solo administración (incluye a quien tenga el módulo por rol de operación).
        if ($record->status === 'completado') {
            return auth()->user()?->can('auth.access') ?? false;
        }

        // Cualquier rol con acceso al módulo (p. ej. recepción, tecnólogo, bioquímico si el admin se lo asignó).
        return true;
    }

    public static function canDelete($record): bool
    {
        return (auth()->user()?->can('auth.access') ?? false)
            && (auth()->user()?->can('imaging.access') ?? false);
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('imaging.access') ?? false;
    }

    // ─── Formulario ──────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── Identificación del estudio ────────────────────────────────────
            Forms\Components\Section::make('Identificación')
                ->icon('heroicon-o-photo')
                ->schema([
                    Forms\Components\TextInput::make('study_code')
                        ->label('Código de estudio')
                        ->required()
                        ->unique(table: 'imaging_studies', column: 'study_code', ignoreRecord: true)
                        ->default(fn () => ImagingStudy::generateStudyCode())
                        ->prefixIcon('heroicon-o-qr-code')
                        ->maxLength(50)
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('regenerate_study_code')
                                ->icon('heroicon-m-arrow-path')
                                ->tooltip('Generar nuevo código')
                                ->action(fn (Forms\Set $set) => $set('study_code', ImagingStudy::generateStudyCode()))
                        )
                        ->validationMessages([
                            'unique' => 'Este código de estudio ya está en uso.',
                            'required' => 'El código de estudio es obligatorio.',
                        ]),

                    Forms\Components\Hidden::make('status')
                        ->default('programado')
                        ->visible(fn (string $operation) => $operation === 'create'),

                    Forms\Components\TextInput::make('status_display')
                        ->label('Estado')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($record) => match ($record?->status) {
                            'programado' => 'Programado',
                            'paciente_presente' => 'Paciente presente',
                            'en_proceso' => 'En proceso',
                            'completado' => 'Completado',
                            'cancelado' => 'Cancelado',
                            default => ucfirst($record?->status ?? ''),
                        })
                        ->visible(fn (string $operation) => $operation === 'edit'),
                ])
                ->columns(2),

            // ── Orden, Examen y Equipo ────────────────────────────────────────
            Forms\Components\Section::make('Orden, Examen y Equipo')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema([
                    Forms\Components\Select::make('order_id')
                        ->label('Orden')
                        ->required()
                        ->options(
                            function () {
                                // Órdenes de imagen que ya tienen estudio registrado
                                $ordersWithStudy = ImagingStudy::pluck('order_id')->all();

                                return ResponsibleClinicalStaffScoping::scopeOrderQueryForPanel(
                                    Order::query()
                                        ->with(['patient', 'invoice'])
                                        ->where('type', 'imagen')
                                        ->whereHas('invoice', fn ($q) => $q->where('status', 'pagada'))
                                )
                                    ->orderByDesc('created_at')
                                    ->get()
                                    ->reject(fn (Order $o) => in_array($o->id, $ordersWithStudy))
                                    ->mapWithKeys(fn (Order $o) => [
                                        $o->id => $o->order_number
                                            .' — '
                                            .($o->patient?->first_name.' '.$o->patient?->last_name),
                                    ]);
                            }
                        )
                        ->searchable()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                            if (! $state) {
                                $set('exam_id', null);
                                $set('responsible_user_id', null);
                                $set('equipment_id', null);

                                return;
                            }
                            $order = Order::with(['exams' => fn ($q) => $q->where('type', 'imagen')])->find($state);
                            $set('responsible_user_id', $order?->responsible_user_id);
                            $set('equipment_id', $order?->equipment_id);
                            $exam = $order?->exams->first();
                            $set('exam_id', $exam?->id);
                        })
                        ->validationMessages(['required' => 'Debe seleccionar una orden.']),

                    Forms\Components\Select::make('exam_id')
                        ->label('Examen')
                        ->required()
                        ->options(function (Get $get) {
                            $orderId = $get('order_id');
                            if (! $orderId) {
                                return [];
                            }
                            $order = Order::with(['exams' => fn ($q) => $q->where('type', 'imagen')])->find($orderId);

                            return $order?->exams->pluck('name', 'id') ?? [];
                        })
                        ->searchable()
                        ->native(false)
                        ->disabled(fn (string $operation, Get $get): bool => $operation === 'create' || ! $get('order_id'))
                        ->dehydrated(true)
                        ->helperText(fn (string $operation): ?string => $operation === 'create'
                            ? 'Se asigna automáticamente según el examen de imagen de la orden.'
                            : null)
                        ->validationMessages(['required' => 'Debe seleccionar un examen.']),

                    Forms\Components\Select::make('equipment_id')
                        ->label('Equipo asignado')
                        ->nullable()
                        ->options(
                            fn () => ImagingEquipment::where('status', 'disponible')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->native(false)
                        ->placeholder('Sin asignar')
                        // En creación: solo lectura (viene auto-completado desde la orden)
                        ->disabled(fn (string $operation) => $operation === 'create')
                        ->dehydrated(true),
                ])
                ->columns(2),

            // ── Programación (desde la orden) ─────────────────
            Forms\Components\Section::make('Programación')
                ->icon('heroicon-o-calendar-days')
                ->schema([
                    Forms\Components\Placeholder::make('estudio_desde_orden')
                        ->label('Fecha y hora del estudio')
                        ->content(function (Get $get): string {
                            $tz = config('clinic_bank.timezone', config('app.timezone'));
                            $orderId = $get('order_id');
                            if (! $orderId) {
                                return 'Seleccione una orden para ver la fecha y hora programadas.';
                            }
                            $order = Order::query()->find($orderId);
                            $dt = $order?->getScheduledDateTime();
                            if (! $dt) {
                                return 'La orden no tiene fecha y hora de cita completas. Complete la programación en la orden antes de registrar el estudio.';
                            }

                            return 'Se registrará automáticamente según la orden: '.$dt->timezone($tz)->format('d/m/Y H:i').'.';
                        })
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // ── Tecnólogo de imagen asignado (FK responsible_user_id en BD) ─────────
            Forms\Components\Section::make('Asignación — Tecnólogo de imagen')
                ->icon('heroicon-o-user-group')
                ->schema([
                    Forms\Components\Select::make('responsible_user_id')
                        ->label('Tecnólogo de imagen asignado')
                        ->nullable()
                        ->options(
                            fn () => User::role('Tecnólogo de Imagen')->orderBy('name')->pluck('name', 'id')
                        )
                        ->searchable()
                        ->native(false)
                        ->placeholder('Sin asignar')
                        // En creación: siempre solo lectura (viene auto-completado desde la orden)
                        ->disabled(fn (string $operation) => $operation === 'create')
                        ->dehydrated(true),
                ])
                ->visible(fn (string $operation) => $operation === 'create'
                    || ($operation === 'edit' && (
                        auth()->user()?->can('auth.access')
                        || auth()->user()?->can('imaging.access')
                        || auth()->user()?->can('samples.access')
                    ))
                ),
        ]);
    }

    // ─── Tabla ───────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('study_code')
                    ->label('Código')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-qr-code'),

                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('N° Orden')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('order.patient.first_name')
                    ->label('Paciente')
                    ->formatStateUsing(fn ($record) => $record->order?->patient
                        ? $record->order->patient->first_name.' '.$record->order->patient->last_name
                        : '—')
                    ->searchable(query: fn ($query, $search) => $query->whereHas(
                        'order.patient',
                        fn ($q) => DbLikeInsensitive::where($q, 'first_name', $search)
                            ->orWhere(fn ($b) => DbLikeInsensitive::where($b, 'last_name', $search))
                    )),

                Tables\Columns\TextColumn::make('exam.name')
                    ->label('Examen')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('equipment.name')
                    ->label('Equipo')
                    ->placeholder('Sin asignar')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'programado' => 'Programado',
                        'paciente_presente' => 'Paciente presente',
                        'en_proceso' => 'En proceso',
                        'completado' => 'Completado',
                        'cancelado' => 'Cancelado',
                        default => ucfirst($state),
                    })
                    ->colors([
                        'gray' => 'programado',
                        'info' => 'paciente_presente',
                        'warning' => 'en_proceso',
                        'success' => 'completado',
                        'danger' => 'cancelado',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('collected_at')
                    ->label('Fecha del estudio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('responsibleUser.name')
                    ->label('Tecnólogo de imagen')
                    ->placeholder('Sin asignar')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'programado' => 'Programado',
                        'paciente_presente' => 'Paciente presente',
                        'en_proceso' => 'En proceso',
                        'completado' => 'Completado',
                        'cancelado' => 'Cancelado',
                    ])
                    ->native(false),

                Tables\Filters\Filter::make('collected_at')
                    ->label('Fecha del estudio')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $v) => $q->whereDate('collected_at', '>=', $v))
                            ->when($data['until'], fn ($q, $v) => $q->whereDate('collected_at', '<=', $v));
                    }),

                Tables\Filters\SelectFilter::make('order_id')
                    ->label('Orden')
                    ->options(
                        fn () => ResponsibleClinicalStaffScoping::scopeOrderQueryForPanel(
                            Order::query()->with('patient')->where('type', 'imagen')
                        )
                            ->orderByDesc('created_at')
                            ->get()
                            ->mapWithKeys(fn (Order $o) => [
                                $o->id => $o->order_number
                                    .' — '
                                    .($o->patient?->first_name.' '.$o->patient?->last_name),
                            ])
                    )
                    ->searchable()
                    ->native(false),

                Tables\Filters\Filter::make('mis_estudios')
                    ->label('Mis estudios')
                    ->query(fn ($query) => $query->where('responsible_user_id', auth()->id()))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('responsible_user_id')
                    ->label('Tecnólogo de imagen')
                    ->options(fn () => User::role('Tecnólogo de Imagen')->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->native(false),
            ])
            ->actions([
                // ── Registrar Llegada (programado → paciente_presente) ────────
                Tables\Actions\Action::make('registrar_llegada')
                    ->label('Registrar llegada')
                    ->icon('heroicon-o-user-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Registrar llegada del paciente')
                    ->modalDescription('El estudio pasará a estado "Paciente presente".')
                    ->visible(
                        fn (ImagingStudy $record) => auth()->user()?->can('registrarLlegada', $record) ?? false
                    )
                    ->action(function (ImagingStudy $record): void {
                        Gate::authorize('registrarLlegada', $record);

                        $oldStatus = $record->status;

                        $record->update(['status' => 'paciente_presente']);

                        ImagingStatusHistory::create([
                            'imaging_study_id' => $record->id,
                            'old_status' => $oldStatus,
                            'new_status' => 'paciente_presente',
                            'changed_by' => auth()->id(),
                            'notes' => 'Paciente registrado como presente.',
                        ]);

                        Notification::make()
                            ->title('Llegada registrada')
                            ->body('El paciente fue registrado como presente.')
                            ->success()
                            ->send();
                    }),

                // ── Iniciar Estudio (paciente_presente → en_proceso) ──────────
                Tables\Actions\Action::make('iniciar_estudio')
                    ->label('Iniciar estudio')
                    ->icon('heroicon-o-play-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Iniciar estudio')
                    ->modalDescription('El estudio pasará a estado "En proceso".')
                    ->visible(
                        fn (ImagingStudy $record) => auth()->user()?->can('iniciarEstudio', $record) ?? false
                    )
                    ->action(function (ImagingStudy $record): void {
                        Gate::authorize('iniciarEstudio', $record);

                        $oldStatus = $record->status;

                        $record->update(['status' => 'en_proceso']);

                        ImagingStatusHistory::create([
                            'imaging_study_id' => $record->id,
                            'old_status' => $oldStatus,
                            'new_status' => 'en_proceso',
                            'changed_by' => auth()->id(),
                            'notes' => 'Estudio iniciado.',
                        ]);

                        Notification::make()
                            ->title('Estudio iniciado')
                            ->body('El estudio de imagen está en proceso.')
                            ->warning()
                            ->send();
                    }),

                // ── Completar Estudio (en_proceso → completado) ───────────────
                Tables\Actions\Action::make('completar_estudio')
                    ->label('Completar estudio')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->modalHeading('Completar estudio de imagen')
                    ->visible(
                        fn (ImagingStudy $record) => auth()->user()?->can('completarEstudio', $record) ?? false
                    )
                    ->form([
                        Forms\Components\FileUpload::make('result_file')
                            ->label('Imagen del estudio (opcional)')
                            ->helperText('JPG, PNG, WebP, GIF o BMP. No se aceptan PDF.')
                            ->nullable()
                            ->acceptedFileTypes(ImagingResultFileSupport::ACCEPTED_MIME_TYPES)
                            ->disk('public')
                            ->directory('imaging-results')
                            ->maxSize(10240),
                    ])
                    ->action(function (ImagingStudy $record, array $data): void {
                        Gate::authorize('completarEstudio', $record);

                        $resultFile = ImagingResultFileSupport::normalizeUploadedPath($data['result_file'] ?? null);
                        ImagingResultFileSupport::assertOptionalUploadIsImage($resultFile);

                        $oldStatus = $record->status;

                        $record->update([
                            'status' => 'completado',
                            'result_file' => $resultFile,
                        ]);

                        ImagingStatusHistory::create([
                            'imaging_study_id' => $record->id,
                            'old_status' => $oldStatus,
                            'new_status' => 'completado',
                            'changed_by' => auth()->id(),
                            'notes' => 'Estudio completado.',
                        ]);

                        Notification::make()
                            ->title('Estudio completado')
                            ->body('El estudio fue marcado como completado exitosamente.')
                            ->success()
                            ->send();
                    }),

                // ── Cancelar Estudio ──────────────────────────────────────────
                Tables\Actions\Action::make('cancelar_estudio')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(
                        fn (ImagingStudy $record) => auth()->user()?->can('cancelarEstudio', $record) ?? false
                    )
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motivo de cancelación')
                            ->required()
                            ->rows(3)
                            ->placeholder('Describe el motivo por el que se cancela el estudio...'),
                    ])
                    ->action(function (ImagingStudy $record, array $data): void {
                        Gate::authorize('cancelarEstudio', $record);

                        $oldStatus = $record->status;

                        $record->update([
                            'status' => 'cancelado',
                            'rejection_reason' => $data['rejection_reason'],
                        ]);

                        ImagingStatusHistory::create([
                            'imaging_study_id' => $record->id,
                            'old_status' => $oldStatus,
                            'new_status' => 'cancelado',
                            'changed_by' => auth()->id(),
                            'notes' => 'Cancelado: '.$data['rejection_reason'],
                        ]);

                        Notification::make()
                            ->title('Estudio cancelado')
                            ->body('El estudio fue cancelado y el motivo fue registrado.')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (ImagingStudy $record): bool => static::canEdit($record)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (ImagingStudy $record): bool => static::canDelete($record)),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No hay estudios de imagen')
            ->emptyStateDescription('Cree un estudio desde una orden de tipo imagen o desde este módulo.')
            ->emptyStateIcon('heroicon-o-photo')
            ->defaultSort('created_at', 'desc');
    }

    // ─── Pages ───────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImagingStudies::route('/'),
            'create' => Pages\CreateImagingStudy::route('/create'),
            'view' => Pages\ViewImagingStudy::route('/{record}'),
            'edit' => Pages\EditImagingStudy::route('/{record}/edit'),
        ];
    }

    // ─── Query con eager loading ──────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return ResponsibleClinicalStaffScoping::scopeImagingStudyQueryForPanel(
            parent::getEloquentQuery()
                ->with(['order.patient', 'exam', 'equipment', 'responsibleUser'])
        );
    }
}
