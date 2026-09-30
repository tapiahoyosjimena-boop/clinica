<?php

namespace App\Domains\Orders\Filament\Resources;

use App\Domains\Catalog\Models\Exam;
use App\Domains\Catalog\Models\ExamCategory;
use App\Domains\Imaging\Models\ImagingEquipment;
use App\Domains\Orders\Filament\Resources\OrderResource\Pages;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Services\OrderCancellationService;
use App\Domains\Patients\Models\Patient;
use App\Models\User;
use App\Support\DbLikeInsensitive;
use App\Support\ResponsibleClinicalStaffScoping;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Órdenes';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Orden';

    protected static ?string $pluralModelLabel = 'Órdenes';

    // ─── Autorización ─────────────────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('orders.access') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('orders.access') ?? false;
    }

    public static function canEdit($record): bool
    {
        if (! (auth()->user()?->can('orders.access') ?? false)) {
            return false;
        }
        if ($record instanceof Order && $record->status === 'completada') {
            return auth()->user()?->hasRole('Administrador') ?? false;
        }

        return true;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('Administrador') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('orders.access') ?? false;
    }

    // ─── Formulario ──────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Select::make('type')
                ->label('Tipo de examen')
                ->required()
                ->native(false)
                ->live(onBlur: false)
                ->options([
                    'laboratorio' => 'Laboratorio',
                    'imagen' => 'Imagen',
                ])
                ->default('laboratorio')
                ->prefixIcon(fn (Get $get): string => match ($get('type')) {
                    'imagen' => 'heroicon-o-camera',
                    default => 'heroicon-o-beaker',
                })
                ->afterStateUpdated(function (Set $set): void {
                    $set('exams', []);
                    $set('equipment_id', null);
                    $set('responsible_user_id', null);
                })
                ->validationMessages(['required' => 'Debe seleccionar el tipo de orden.']),

            // ── Información principal ──────────────────────────────────────────
            Forms\Components\Section::make('Información de la Orden')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema([
                    Forms\Components\TextInput::make('order_number')
                        ->label('Número de orden')
                        ->required()
                        ->unique(table: 'orders', column: 'order_number', ignoreRecord: true)
                        ->validationMessages([
                            'unique' => 'Este número de orden ya existe.',
                            'required' => 'El número de orden es obligatorio.',
                        ])
                        ->default(fn () => 'ORD-'.strtoupper(uniqid()))
                        ->prefixIcon('heroicon-o-hashtag')
                        ->maxLength(50),

                    Forms\Components\Select::make('patient_id')
                        ->label('Paciente')
                        ->required()
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search) {
                            return Patient::query()
                                ->where(fn ($q) => DbLikeInsensitive::where($q, 'first_name', $search))
                                ->orWhere(fn ($q) => DbLikeInsensitive::where($q, 'last_name', $search))
                                ->orWhere(fn ($q) => DbLikeInsensitive::where($q, 'ci', $search))
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (Patient $p) => [
                                    $p->id => "{$p->first_name} {$p->last_name} — CI: {$p->ci}",
                                ]);
                        })
                        ->getOptionLabelUsing(function ($value) {
                            $p = Patient::find($value);

                            return $p ? "{$p->first_name} {$p->last_name} — CI: {$p->ci}" : null;
                        })
                        ->native(false)
                        ->validationMessages(['required' => 'Debe seleccionar un paciente.']),

                    Forms\Components\Select::make('doctor_id')
                        ->label('Médico derivante')
                        ->nullable()
                        ->searchable()
                        ->native(false)
                        ->options(fn () => User::role('Médico')->orderBy('name')->pluck('name', 'id'))
                        ->placeholder('— (opcional)'),

                    Forms\Components\Select::make('receptionist_id')
                        ->label('Recepcionista')
                        ->required()
                        ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->native(false)
                        ->default(fn () => auth()->id())
                        ->disabled()
                        ->dehydrated()
                        ->validationMessages(['required' => 'Debe seleccionar un recepcionista.']),

                    Forms\Components\Select::make('responsible_user_id')
                        ->key('order-responsible-laboratorio')
                        ->label('Bioquímico responsable')
                        ->visible(fn (Get $get): bool => ($get('type') ?? 'laboratorio') !== 'imagen')
                        ->dehydrated(fn (Get $get): bool => ($get('type') ?? 'laboratorio') !== 'imagen')
                        ->required(fn (Get $get): bool => ($get('type') ?? 'laboratorio') !== 'imagen')
                        ->options(fn () => User::role('Bioquímico')->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->native(false)
                        ->rules(self::responsibleUserValidationRules())
                        ->validationMessages(['required' => 'Debe asignar un responsable para esta orden.']),

                    Forms\Components\Select::make('responsible_user_id')
                        ->key('order-responsible-imagen')
                        ->label('Tecnólogo de imagen responsable')
                        ->visible(fn (Get $get): bool => ($get('type') ?? 'laboratorio') === 'imagen')
                        ->dehydrated(fn (Get $get): bool => ($get('type') ?? 'laboratorio') === 'imagen')
                        ->required(fn (Get $get): bool => ($get('type') ?? 'laboratorio') === 'imagen')
                        ->options(fn () => User::role('Tecnólogo de Imagen')->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->native(false)
                        ->rules(self::responsibleUserValidationRules())
                        ->validationMessages(['required' => 'Debe asignar un responsable para esta orden.']),


                    Forms\Components\Hidden::make('fecha_creada')
                        ->default(now())
                        ->dehydrated(true),

                    Forms\Components\Hidden::make('fecha_actualizada')
                        ->default(now())
                        ->dehydrated(true)
                ])
                ->columns(2),

            // ── Programación ───────────────────────────────────────────────────
            Forms\Components\Section::make('Programación')
                ->key(fn (Get $get): string => 'scheduling-'.($get('type') ?? 'laboratorio'))
                ->icon('heroicon-o-calendar-days')
                ->schema([
                    Forms\Components\DatePicker::make('scheduled_date')
                        ->label('Fecha programada')
                        ->required()
                        ->minDate(now()->toDateString())
                        ->timezone(config('clinic_bank.timezone', config('app.timezone')))
                        ->displayFormat('d/m/Y')
                        ->native(false)
                        ->live()
                        ->rules(self::imagingScheduleSlotConflictRules())
                        ->validationMessages(['required' => 'La fecha programada es obligatoria.']),

                    Forms\Components\TimePicker::make('scheduled_time')
                        ->label('Hora programada')
                        ->required()
                        ->seconds(false)
                        ->timezone(config('clinic_bank.timezone', config('app.timezone')))
                        ->live()
                        ->rules(self::imagingScheduleSlotConflictRules())
                        ->validationMessages(['required' => 'La hora programada es obligatoria.']),

                    Forms\Components\Hidden::make('equipment_id')
                        ->visible(fn (Get $get) => $get('type') === 'imagen')
                        ->dehydrated(fn (Get $get) => $get('type') === 'imagen')
                        ->live()
                        ->required(fn (Get $get) => $get('type') === 'imagen')
                        ->validationMessages(['required' => 'Debe definirse un equipo para órdenes de imagen (se asigna al elegir el examen).']),

                    Forms\Components\Placeholder::make('imaging_equipment_display')
                        ->label('Equipo de imagen')
                        ->visible(fn (Get $get) => $get('type') === 'imagen')
                        ->content(function (Get $get): HtmlString {
                            if (($get('type') ?? '') !== 'imagen') {
                                return new HtmlString('');
                            }
                            $id = $get('equipment_id');
                            if (blank($id)) {
                                return new HtmlString(
                                    '<p class="cn-embedded-muted italic">Seleccione el examen de imagen; el equipo se asignará solo según el catálogo.</p>'
                                );
                            }
                            $equipment = ImagingEquipment::query()->find($id);
                            $name = $equipment?->name ?? '—';

                            return new HtmlString(
                                '<p class="cn-embedded-text font-medium">'.e($name).'</p>'
                                .'<p class="cn-embedded-muted mt-1" style="font-size:0.75rem;">Asignación fija según el examen. La recepción no puede cambiar el equipo desde aquí.</p>'
                            );
                        })
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // ── Exámenes asignados ─────────────────────────────────────────────
            Forms\Components\Section::make('Exámenes asignados')
                ->key(fn (Get $get): string => 'exams-section-'.($get('type') ?? 'laboratorio'))
                ->icon(fn (Get $get): string => $get('type') === 'imagen'
                    ? 'heroicon-o-camera'
                    : 'heroicon-o-beaker')
                ->description('Laboratorio: puede elegir varios exámenes. Imagen: solo un examen de imagen por orden; el equipo se toma del catálogo.')
                ->schema([
                    Forms\Components\Hidden::make('exams')
                        ->default([])
                        ->live()
                        ->rules([
                            'array',
                            fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get): void {
                                $msg = Exam::validateSelectionForOrder(
                                    (string) ($get('type') ?? 'laboratorio'),
                                    is_array($value) ? $value : []
                                );
                                if ($msg !== null && $msg !== '') {
                                    $fail($msg);
                                }
                            },
                        ])
                        ->validationMessages(['required' => 'Debe seleccionar al menos un examen.']),

                    Forms\Components\View::make('exam-selector')
                        ->key(fn (Get $get): string => 'exam-selector-'.($get('type') ?? 'laboratorio'))
                        ->viewData(fn (Get $get) => [
                            'orderType' => $get('type') ?? 'laboratorio',
                            'laboratoryCategories' => ExamCategory::query()
                                ->where('type', 'laboratorio')
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->with(['exams' => fn ($q) => $q->orderBy('name')->with('requirements')])
                                ->get(),
                            'imagingCategories' => ExamCategory::query()
                                ->where('type', 'imagen')
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->with(['exams' => fn ($q) => $q->orderBy('name')->with('requirements')])
                                ->get(),
                        ])
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * @return array<int, \Closure(Get): \Closure(string, mixed, \Closure): void>
     */
    private static function responsibleUserValidationRules(): array
    {
        return [
            function (Get $get) {
                return function (string $attribute, $value, \Closure $fail) use ($get) {
                    if (! $value) {
                        return;
                    }
                    $user = User::find($value);
                    if (! $user) {
                        $fail('El usuario seleccionado no es válido.');

                        return;
                    }
                    $type = $get('type') ?? 'laboratorio';
                    if ($type === 'imagen' && ! $user->hasRole('Tecnólogo de Imagen')) {
                        $fail('En órdenes de imagen debe asignar un Tecnólogo de Imagen.');
                    }
                    if ($type === 'laboratorio' && ! $user->hasRole('Bioquímico')) {
                        $fail('En órdenes de laboratorio debe asignar un Bioquímico.');
                    }
                };
            },
        ];
    }

    /**
     * Reglas para conflicto de agenda (órdenes imagen). Deben aplicarse a campos con wrapper de error
     * visible (fecha y hora programadas); {@see Forms\Components\Hidden} no muestra el mensaje al usuario.
     *
     * @return array<int, \Closure(Get): \Closure(string, mixed, \Closure): void>
     */
    private static function imagingScheduleSlotConflictRules(): array
    {
        return [
            fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get): void {
                if (($get('type') ?? '') !== 'imagen') {
                    return;
                }
                $examIds = is_array($get('exams')) ? $get('exams') : [];
                $equipmentRaw = $get('equipment_id');
                $equipmentId = blank($equipmentRaw)
                    ? Exam::resolveImagingEquipmentIdForOrder('imagen', array_map('intval', $examIds))
                    : (int) $equipmentRaw;
                if (! $equipmentId) {
                    return;
                }

                $routeRecord = request()->route('record');
                $ignoreId = $routeRecord instanceof Order
                    ? (int) $routeRecord->getKey()
                    : (is_numeric($routeRecord) ? (int) $routeRecord : null);

                if (Order::hasActiveImagingScheduleEquipmentExamConflict(
                    $get('scheduled_date'),
                    $get('scheduled_time'),
                    $equipmentId,
                    $examIds,
                    $ignoreId,
                )) {
                    $fail(Order::imagingScheduleConflictValidationMessage());
                }
            },
        ];
    }

    // ─── Tabla ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('N° Orden')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('patient.first_name')
                    ->label('Paciente')
                    ->formatStateUsing(fn ($state, Order $record) => $record->patient?->first_name.' '.$record->patient?->last_name
                    )
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('patient', function ($q) use ($search) {
                            DbLikeInsensitive::where($q, 'first_name', $search)
                                ->orWhere(fn ($b) => DbLikeInsensitive::where($b, 'last_name', $search));
                        });
                    })
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo de examen')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'laboratorio' => 'Laboratorio',
                        'imagen' => 'Imagen',
                        default => ucfirst($state),
                    })
                    ->colors([
                        'info' => 'laboratorio',
                        'warning' => 'imagen',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pendiente' => 'Pendiente',
                        'en_proceso' => 'En proceso',
                        'completada' => 'Completada',
                        'cancelada' => 'Cancelada',
                        default => ucfirst($state),
                    })
                    ->colors([
                        'warning' => 'pendiente',
                        'info' => 'en_proceso',
                        'success' => 'completada',
                        'danger' => 'cancelada',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('cancellation_reason')
                    ->label('Motivo cancelación')
                    ->limit(60)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),

                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Fecha prog.')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Sin programar'),

                Tables\Columns\TextColumn::make('scheduled_time')
                    ->label('Hora prog.')
                    ->time('H:i')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('receptionist.name')
                    ->label('Recepcionista')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('responsibleUser.name')
                    ->label('Responsable asignado')
                    ->placeholder('Sin asignar')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('exams_count')
                    ->label('Exámenes')
                    ->counts('exams')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('fecha_creada')
                    ->label('Fecha creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('fecha_actualizada')
                    ->label('Fecha actualizada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'en_proceso' => 'En proceso',
                        'completada' => 'Completada',
                        'cancelada' => 'Cancelada',
                    ]),

                Tables\Filters\Filter::make('sin_asignar')
                    ->label('Sin responsable asignado')
                    ->query(fn (Builder $query) => $query->whereNull('responsible_user_id')),

                Tables\Filters\Filter::make('mis_ordenes')
                    ->label('Mis órdenes')
                    ->query(fn (Builder $query) => $query->where('responsible_user_id', auth()->id())),

                Tables\Filters\Filter::make('scheduled_date')
                    ->label('Con fecha programada')
                    ->query(fn (Builder $query) => $query->whereNotNull('scheduled_date')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn (Order $record): bool => static::canEdit($record)),

                Tables\Actions\Action::make('cancelar_orden')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(
                        fn (Order $record): bool => auth()->user()?->can('cancel', $record) ?? false
                    )
                    ->form([
                        Forms\Components\Textarea::make('motivo_cancelacion')
                            ->label('Motivo de cancelación')
                            ->required()
                            ->rows(3)
                            ->placeholder('Indique el motivo por el que se cancela la orden...'),
                    ])
                    ->modalHeading('Cancelar orden')
                    ->modalDescription('No se puede cancelar si hay muestras en análisis o procesadas, o estudios de imagen ya iniciados. Si procede, las muestras solo «recibidas» y los estudios solo «programados» se anularán automáticamente.')
                    ->modalSubmitActionLabel('Enviar')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (Order $record, array $data): void {
                        try {
                            app(OrderCancellationService::class)->cancel($record, $data['motivo_cancelacion']);
                        } catch (AuthorizationException $e) {
                            Notification::make()
                                ->title('No se puede cancelar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Orden cancelada')
                            ->body('La orden fue cancelada. Se anularon muestras o estudios pendientes de inicio, si los había.')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Order $record): bool => static::canDelete($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->hasRole('Administrador') ?? false),
                ]),
            ])
            ->emptyStateHeading('No hay órdenes registradas')
            ->emptyStateDescription('Cree una orden para vincular paciente, exámenes y responsable clínico.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return ResponsibleClinicalStaffScoping::scopeOrderQueryForPanel(parent::getEloquentQuery());
    }

    // ─── Páginas ──────────────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
