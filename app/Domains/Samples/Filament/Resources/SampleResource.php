<?php

namespace App\Domains\Samples\Filament\Resources;

use App\Domains\Orders\Models\Order;
use App\Domains\Samples\Filament\Resources\SampleResource\Pages;
use App\Domains\Samples\Models\Sample;
use App\Domains\Samples\Models\SampleStatusHistory;
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

class SampleResource extends Resource
{
    protected static ?string $model = Sample::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Muestras';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Muestra';

    protected static ?string $pluralModelLabel = 'Muestras';

    // ─── Autorización ─────────────────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('samples.access') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('samples.access') ?? false;
    }

    public static function canEdit($record): bool
    {
        if (! (auth()->user()?->can('samples.access') ?? false)) {
            return false;
        }
        if ($record instanceof Sample && $record->status === 'procesada') {
            return auth()->user()?->can('auth.access') ?? false;
        }

        return true;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('auth.access') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('samples.access') ?? false;
    }

    // ─── Formulario ──────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── Identificación de la muestra ──────────────────────────────────
            Forms\Components\Section::make('Identificación')
                ->icon('heroicon-o-beaker')
                ->schema([
                    Forms\Components\TextInput::make('barcode')
                        ->label('Código de barras')
                        ->required()
                        ->unique(table: 'samples', column: 'barcode', ignoreRecord: true)
                        ->default(fn () => Sample::generateBarcode())
                        ->prefixIcon('heroicon-o-qr-code')
                        ->maxLength(50)
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('regenerate_barcode')
                                ->icon('heroicon-m-arrow-path')
                                ->tooltip('Generar nuevo código')
                                ->action(fn (Forms\Set $set) => $set('barcode', Sample::generateBarcode()))
                        )
                        ->validationMessages([
                            'unique' => 'Este código de barras ya está en uso.',
                            'required' => 'El código de barras es obligatorio.',
                        ]),

                    // El estado se fija automáticamente en 'recibida' al crear.
                    // Solo el bioquímico puede cambiarlo mediante acciones explícitas.
                    Forms\Components\Hidden::make('status')
                        ->default('recibida')
                        ->visible(fn (string $operation) => $operation === 'create'),

                    // En edición, el estado es de solo lectura (se cambia por acciones)
                    Forms\Components\TextInput::make('status_display')
                        ->label('Estado')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($record) => match ($record?->status) {
                            'recibida' => 'Recibida',
                            'en_analisis' => 'En análisis',
                            'procesada' => 'Procesada',
                            'rechazada' => 'Rechazada',
                            default => ucfirst($record?->status ?? ''),
                        })
                        ->visible(fn (string $operation) => $operation === 'edit'),
                ])
                ->columns(2),

            // ── Asociación ────────────────────────────────────────────────────
            Forms\Components\Section::make('Orden y Examen')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema([
                    Forms\Components\Select::make('order_id')
                        ->label('Orden')
                        ->required()
                        ->options(
                            fn () => ResponsibleClinicalStaffScoping::scopeOrderQueryForPanel(
                                Order::query()
                                    ->with(['patient', 'exams' => fn ($q) => $q->where('type', 'laboratorio'), 'invoice'])
                                    ->where('type', 'laboratorio')
                                    ->whereHas('invoice', fn ($q) => $q->where('status', 'pagada'))
                            )
                                ->orderByDesc('created_at')
                                ->get()
                                ->filter(function (Order $o) {
                                    // Excluir órdenes donde todos los exámenes ya tienen muestra
                                    $totalExams = $o->exams->count();
                                    if ($totalExams === 0) {
                                        return false;
                                    }
                                    $samplesCreated = Sample::where('order_id', $o->id)->count();

                                    return $samplesCreated < $totalExams;
                                })
                                ->mapWithKeys(fn (Order $o) => [
                                    $o->id => $o->order_number
                                        .' — '
                                        .($o->patient?->first_name.' '.$o->patient?->last_name),
                                ])
                        )
                        ->searchable()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                            $set('exam_id', null);
                            if ($state) {
                                $order = Order::query()->find($state);
                                $set('bioquimico_asignado_id', $order?->responsible_user_id);
                            } else {
                                $set('bioquimico_asignado_id', null);
                            }
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

                            $order = Order::with(['exams' => fn ($q) => $q->where('type', 'laboratorio')])->find($orderId);
                            if (! $order) {
                                return [];
                            }

                            // Excluir exámenes que ya tienen muestra para esta orden
                            $usedExamIds = Sample::where('order_id', $orderId)
                                ->pluck('exam_id')
                                ->all();

                            return $order->exams
                                ->reject(fn ($exam) => in_array($exam->id, $usedExamIds))
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->native(false)
                        ->disabled(fn (Get $get) => ! $get('order_id'))
                        ->hint(function (Get $get) {
                            $orderId = $get('order_id');
                            if (! $orderId) {
                                return null;
                            }
                            $order = Order::with(['exams' => fn ($q) => $q->where('type', 'laboratorio')])->find($orderId);
                            if (! $order) {
                                return null;
                            }
                            $total = $order->exams->count();
                            $used = Sample::where('order_id', $orderId)->count();
                            $pending = $total - $used;
                            if ($pending <= 0) {
                                return 'Todos los exámenes de esta orden ya tienen muestra registrada.';
                            }

                            return "Pendientes: {$pending} de {$total} examen(es).";
                        })
                        ->hintColor(function (Get $get) {
                            $orderId = $get('order_id');
                            if (! $orderId) {
                                return null;
                            }
                            $order = Order::with(['exams' => fn ($q) => $q->where('type', 'laboratorio')])->find($orderId);
                            if (! $order) {
                                return null;
                            }
                            $total = $order->exams->count();
                            $used = Sample::where('order_id', $orderId)->count();

                            return ($total - $used) <= 0 ? 'danger' : 'info';
                        })
                        ->validationMessages(['required' => 'Debe seleccionar un examen.']),

                        Forms\Components\TextInput::make('fecha_creada')
                            ->label('Fecha de creación')
                            ->disabled()
                            ->dehydrated(true)
                            ->default(now()),

                        Forms\Components\DateTimePicker::make('hora_creacion')
                            ->label('hora de creación')
                            ->disabled()
                            ->dehydrated(true)
                            ->default(now()),
                    ])
                ->columns(2),

            // ── Recolección ───────────────────────────────────────────────────
            Forms\Components\Section::make('Recolección')
                ->icon('heroicon-o-user-circle')
                ->schema([
                    Forms\Components\Placeholder::make('coleccion_desde_orden')
                        ->label('Fecha y hora de recolección')
                        ->content(function (Get $get): string {
                            $tz = config('clinic_bank.timezone', config('app.timezone'));
                            $orderId = $get('order_id');
                            if (! $orderId) {
                                return 'Seleccione una orden para ver la fecha y hora programadas.';
                            }
                            $order = Order::query()->find($orderId);
                            $dt = $order?->getScheduledDateTime();
                            if (! $dt) {
                                return 'La orden no tiene fecha y hora de cita completas. Complete la programación en la orden antes de registrar la muestra.';
                            }

                            return 'Se registrará automáticamente según la orden: '.$dt->timezone($tz)->format('d/m/Y H:i').'.';
                        })
                        ->columnSpanFull(),

                    Forms\Components\Select::make('collected_by')
                        ->label('Recolectado por')
                        ->required()
                        ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->native(false)
                        ->default(fn () => auth()->id())
                        ->disabled()
                        ->dehydrated()
                        ->validationMessages(['required' => 'Debe indicar quién recolectó la muestra.']),

                    Forms\Components\TextInput::make('location')
                        ->label('Ubicación / Gabinete')
                        ->nullable()
                        ->prefixIcon('heroicon-o-map-pin')
                        ->maxLength(255),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notas')
                        ->nullable()
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // ── Bioquímico asignado ───────────────────────────────────────────
            Forms\Components\Section::make('Asignación de bioquímico')
                ->icon('heroicon-o-user-group')
                ->schema([
                    Forms\Components\Select::make('bioquimico_asignado_id')
                        ->label('Bioquímico asignado')
                        ->nullable()
                        ->options(
                            fn () => User::role('Bioquímico')->orderBy('name')->pluck('name', 'id')
                        )
                        ->searchable()
                        ->native(false)
                        ->placeholder('Sin asignar')
                        // En creación: siempre solo lectura (viene auto-completado desde la orden)
                        ->disabled(fn (string $operation) => $operation === 'create')
                        ->dehydrated(true),
                ])
                // Visible en creación (para mostrar el auto-completado) y en edición para personal autorizado
                ->visible(fn (string $operation) => $operation === 'create'
                    || ($operation === 'edit' && (auth()->user()?->hasAnyRole(['Administrador', 'Bioquímico', 'Recepcionista']) ?? false))
                ),
        ]);
    }

    // ─── Tabla ───────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Código de barras')
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

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'recibida' => 'Recibida',
                        'en_analisis' => 'En análisis',
                        'procesada' => 'Procesada',
                        'rechazada' => 'Rechazada',
                        default => ucfirst($state),
                    })
                    ->colors([
                        'info' => 'recibida',
                        'warning' => 'en_analisis',
                        'success' => 'procesada',
                        'danger' => 'rechazada',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('collected_at')
                    ->label('Recolectada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('collectedBy.name')
                    ->label('Recolectado por')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('bioquimicoAsignado.name')
                    ->label('Bioquímico')
                    ->placeholder('Sin asignar')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Ubicación')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('fecha_creada')
                    ->label('Fecha de creación')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('hora_creacion')
                    ->label('hora de creación')
                    ->dateTime('H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'recibida' => 'Recibida',
                        'en_analisis' => 'En análisis',
                        'procesada' => 'Procesada',
                        'rechazada' => 'Rechazada',
                    ])
                    ->native(false),

                Tables\Filters\Filter::make('collected_at')
                    ->label('Fecha de recolección')
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
                            Order::query()->with('patient')->where('type', 'laboratorio')
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

                Tables\Filters\Filter::make('mis_muestras')
                    ->label('Mis muestras')
                    ->query(fn ($query) => $query->where('bioquimico_asignado_id', auth()->id()))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('bioquimico_asignado_id')
                    ->label('Bioquímico')
                    ->options(fn () => User::role('Bioquímico')->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->native(false),
            ])
            ->actions([
                // ── Aceptar muestra (Bioquímico: recibida → en_analisis) ────────
                Tables\Actions\Action::make('aceptar_muestra')
                    ->label('Aceptar muestra')
                    ->icon('heroicon-o-beaker')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aceptar muestra')
                    ->modalDescription('La muestra pasará a estado «En análisis» y quedará asignada a usted como bioquímico.')
                    ->visible(
                        fn (Sample $record) => auth()->user()?->can('acceptSample', $record) ?? false
                    )
                    ->action(function (Sample $record): void {
                        Gate::authorize('acceptSample', $record);

                        $oldStatus = $record->status;

                        $record->update([
                            'status' => 'en_analisis',
                            'bioquimico_asignado_id' => auth()->id(),
                        ]);

                        SampleStatusHistory::create([
                            'sample_id' => $record->id,
                            'old_status' => $oldStatus,
                            'new_status' => 'en_analisis',
                            'changed_by' => auth()->id(),
                            'notes' => 'Muestra aceptada por bioquímico.',
                        ]);

                        Notification::make()
                            ->title('Muestra aceptada')
                            ->body('La muestra está ahora en análisis.')
                            ->success()
                            ->send();
                    }),

                // ── Procesar muestra (Bioquímico: en_analisis → procesada) ──────
                Tables\Actions\Action::make('procesar_muestra')
                    ->label('Marcar procesada')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Marcar como procesada')
                    ->modalDescription('Confirma que la muestra fue analizada y los resultados están listos.')
                    ->visible(
                        fn (Sample $record) => auth()->user()?->can('processSample', $record) ?? false
                    )
                    ->action(function (Sample $record): void {
                        Gate::authorize('processSample', $record);

                        $oldStatus = $record->status;

                        $record->update(['status' => 'procesada']);

                        SampleStatusHistory::create([
                            'sample_id' => $record->id,
                            'old_status' => $oldStatus,
                            'new_status' => 'procesada',
                            'changed_by' => auth()->id(),
                            'notes' => 'Muestra marcada como procesada.',
                        ]);

                        Notification::make()
                            ->title('Muestra procesada')
                            ->body('La muestra fue marcada como procesada exitosamente.')
                            ->success()
                            ->send();
                    }),

                // ── Rechazar muestra (cancelación operativa: en_analisis → rechazada) ────────
                Tables\Actions\Action::make('rechazar_muestra')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(
                        fn (Sample $record) => auth()->user()?->can('reject', $record) ?? false
                    )
                    ->form([
                        Forms\Components\Textarea::make('motivo_rechazo')
                            ->label('Motivo de rechazo')
                            ->required()
                            ->rows(3)
                            ->placeholder('Describe el motivo por el que se rechaza la muestra...'),
                    ])
                    ->action(function (Sample $record, array $data): void {
                        Gate::authorize('reject', $record);

                        $oldStatus = $record->status;

                        $record->update([
                            'status' => 'rechazada',
                            'motivo_rechazo' => $data['motivo_rechazo'],
                            'rejected_by_user_id' => auth()->id(),
                            'rejected_at' => now(),
                        ]);

                        SampleStatusHistory::create([
                            'sample_id' => $record->id,
                            'old_status' => $oldStatus,
                            'new_status' => 'rechazada',
                            'changed_by' => auth()->id(),
                            'notes' => 'Rechazada: '.$data['motivo_rechazo'],
                        ]);

                        Notification::make()
                            ->title('Muestra rechazada')
                            ->body('La muestra fue rechazada y el motivo fue registrado.')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Sample $record): bool => static::canEdit($record)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Sample $record): bool => static::canDelete($record)),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No hay muestras registradas')
            ->emptyStateDescription('Las muestras se crean a partir de órdenes de laboratorio con exámenes asociados.')
            ->emptyStateIcon('heroicon-o-beaker')
            ->defaultSort('created_at', 'desc');
    }

    // ─── Pages ───────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSamples::route('/'),
            'create' => Pages\CreateSample::route('/create'),
            'view' => Pages\ViewSample::route('/{record}'),
            'edit' => Pages\EditSample::route('/{record}/edit'),
        ];
    }

    // ─── Query con eager loading ──────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return ResponsibleClinicalStaffScoping::scopeSampleQueryForPanel(
            parent::getEloquentQuery()
                ->with(['order.patient', 'exam', 'collectedBy', 'bioquimicoAsignado'])
        );
    }
}
