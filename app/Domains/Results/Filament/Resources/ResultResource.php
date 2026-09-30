<?php

namespace App\Domains\Results\Filament\Resources;

use App\Domains\Catalog\Models\ExamParameter;
use App\Domains\Imaging\Models\ImagingStudy;
use App\Domains\Imaging\Support\ImagingResultFileSupport;
use App\Domains\Orders\Models\Order;
use App\Domains\Results\Filament\Resources\ResultResource\Pages;
use App\Domains\Results\Models\Result;
use App\Domains\Results\Services\ResultDeliveryService;
use App\Domains\Results\Services\ResultPdfService;
use App\Domains\Results\Services\ResultValidatedNotifier;
use App\Domains\Results\Support\LabResultCriticalEvaluator;
use App\Models\User;
use App\Support\FilamentEmbeddedHtml;
use App\Support\ResponsibleClinicalStaffScoping;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ResultResource extends Resource
{
    protected static ?string $model = Result::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Resultados';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Resultado';

    protected static ?string $pluralModelLabel = 'Resultados';

    // ─── Autorización ─────────────────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('results.access') ?? false;
    }

    public static function canCreate(): bool
    {
        // Los resultados se generan al completar muestras (lab) o estudios de imagen.
        return false;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return Gate::forUser($user)->allows('update', $record);
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canView($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return Gate::forUser($user)->allows('view', $record);
    }

    // ─── Formulario ──────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        // El selector de orden determina el tipo; a partir de ahí se elige el esquema de cada flujo.
        $orderSection = Forms\Components\Section::make('Orden')
            ->icon('heroicon-o-clipboard-document-list')
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->label('Orden')
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->live()
                    ->options(function (?Model $record) {
                        if ($record instanceof Result) {
                            $record->loadMissing('order.patient');
                            $o = $record->order;
                            if ($o) {
                                return [$o->id => $o->order_number.' — '.($o->patient?->full_name ?? '—')];
                            }

                            return [];
                        }

                        $user = auth()->user();

                        // Bioquímico: órdenes laboratorio con muestra procesada sin resultado aún
                        if ($user?->hasRole('Bioquímico')) {
                            $ordersWithResult = Result::pluck('order_id')->all();

                            return Order::query()
                                ->where('type', 'laboratorio')
                                ->where('responsible_user_id', $user->id)
                                ->whereHas('samples', fn ($q) => $q->where('status', 'procesada'))
                                ->whereNotIn('id', $ordersWithResult)
                                ->with('patient')
                                ->orderByDesc('created_at')
                                ->get()
                                ->mapWithKeys(fn (Order $o) => [
                                    $o->id => $o->order_number.' — '.($o->patient?->full_name ?? '—'),
                                ]);
                        }

                        // Tecnólogo: órdenes imagen con estudio completado sin resultado aún
                        if ($user?->hasRole('Tecnólogo de Imagen')) {
                            $ordersWithResult = Result::pluck('order_id')->all();

                            return Order::query()
                                ->where('type', 'imagen')
                                ->where('responsible_user_id', $user->id)
                                ->whereHas('imagingStudies', fn ($q) => $q->where('status', 'completado'))
                                ->whereNotIn('id', $ordersWithResult)
                                ->with('patient')
                                ->orderByDesc('created_at')
                                ->get()
                                ->mapWithKeys(fn (Order $o) => [
                                    $o->id => $o->order_number.' — '.($o->patient?->full_name ?? '—'),
                                ]);
                        }

                        // Admin: todas las órdenes sin resultado
                        $ordersWithResult = Result::pluck('order_id')->all();

                        return Order::query()
                            ->whereNotIn('id', $ordersWithResult)
                            ->with('patient')
                            ->orderByDesc('created_at')
                            ->get()
                            ->mapWithKeys(fn (Order $o) => [
                                $o->id => $o->order_number
                                    .' ['.$o->type.'] — '
                                    .($o->patient?->full_name ?? '—'),
                            ]);
                    })
                    ->disabled(fn (?Model $record): bool => $record instanceof Result)
                    ->dehydrated()
                    ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                        if (! $state) {
                            $set('exam_id', null);
                            $set('sample_id', null);
                            $set('bioquimico_id', null);

                            return;
                        }
                        $order = Order::with(['exams', 'samples', 'imagingStudies'])->find($state);
                        if (! $order) {
                            return;
                        }

                        if ($order->type === 'imagen') {
                            $exam = $order->exams->where('type', 'imagen')->first();
                            $set('exam_id', $exam?->id);

                            $study = $order->imagingStudies->where('status', 'completado')->first();
                            $set('bioquimico_id', $study?->responsible_user_id ?? auth()->id());
                        } else {
                            $sample = $order->samples->where('status', 'procesada')->first();
                            $set('sample_id', $sample?->id);
                            $set('exam_id', $sample?->exam_id);
                            $set('bioquimico_id', $sample?->bioquimico_asignado_id ?? auth()->id());
                        }
                    })
                    ->validationMessages(['required' => 'Debe seleccionar una orden.']),

                Forms\Components\Select::make('exam_id')
                    ->label('Examen')
                    ->required()
                    ->native(false)
                    ->options(function (Forms\Get $get, ?Model $record) {
                        if ($record instanceof Result) {
                            $record->loadMissing('exam');
                            $exam = $record->exam;

                            return $exam ? [$exam->id => $exam->name] : [];
                        }

                        $orderId = $get('order_id');
                        if (! $orderId) {
                            return [];
                        }
                        $order = Order::with('exams')->find($orderId);

                        return $order?->exams->pluck('name', 'id') ?? [];
                    })
                    ->disabled(fn (Forms\Get $get, ?Model $record): bool => $record instanceof Result
                        || ! $get('order_id'))
                    ->dehydrated()
                    ->validationMessages(['required' => 'Debe seleccionar un examen.']),

                Forms\Components\Hidden::make('sample_id'),

                Forms\Components\Select::make('bioquimico_id')
                    ->label(fn (Forms\Get $get) => Order::find($get('order_id'))?->type === 'imagen'
                        ? 'Tecnólogo responsable'
                        : 'Bioquímico responsable'
                    )
                    ->options(function (Forms\Get $get) {
                        $type = Order::find($get('order_id'))?->type;

                        return match ($type) {
                            'imagen' => User::role('Tecnólogo de Imagen')->orderBy('name')->pluck('name', 'id'),
                            default => User::role('Bioquímico')->orderBy('name')->pluck('name', 'id'),
                        };
                    })
                    ->native(false)
                    ->disabled()
                    ->dehydrated()
                    ->required(),
            ])
            ->columns(2);

        return $form->schema([
            $orderSection,
            ...static::labFormSchema(),
            ...static::imagingFormSchema(),
        ]);
    }

    // ─── Esquema laboratorio ──────────────────────────────────────────────────

    /**
     * Secciones exclusivas del flujo de laboratorio (muestra procesada → valores de parámetros + criticidad automática).
     *
     * @return array<int, Forms\Components\Section>
     */
    protected static function labFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Valores de parámetros')
                ->icon('heroicon-o-beaker')
                ->visible(fn (Forms\Get $get) => Order::find($get('order_id'))?->type === 'laboratorio')
                ->schema(function (Forms\Get $get): array {
                    $orderId = $get('order_id');
                    if (! $orderId) {
                        return [Forms\Components\Placeholder::make('hint')->content('Seleccione una orden primero.')];
                    }

                    $order = Order::with('exams.category')->find($orderId);
                    $examId = $get('exam_id');
                    $exam = $order?->exams->firstWhere('id', $examId);

                    if (! $exam) {
                        return [Forms\Components\Placeholder::make('hint')->content('Seleccione un examen para ver sus parámetros.')];
                    }

                    $params = ExamParameter::where('exam_category_id', $exam->exam_category_id)
                        ->orderBy('name')
                        ->get();

                    if ($params->isEmpty()) {
                        return [Forms\Components\Placeholder::make('hint')
                            ->content('Este examen no tiene parámetros definidos en el catálogo.')];
                    }

                    return $params->map(fn (ExamParameter $p) => Forms\Components\TextInput::make('param_'.$p->id)
                        ->label($p->name.($p->unit ? ' ('.$p->unit.')' : ''))
                        ->required()
                        ->numeric()
                        ->live(onBlur: true)
                        ->hint(
                            $p->reference_min !== null || $p->reference_max !== null
                                ? 'Ref: '.(($p->reference_min !== null ? (int) $p->reference_min : '?')).' – '.(($p->reference_max !== null ? (int) $p->reference_max : '?'))
                                  .($p->critical_min !== null || $p->critical_max !== null
                                      ? ' | Crítico: '.(($p->critical_min !== null ? (int) $p->critical_min : '?')).' – '.(($p->critical_max !== null ? (int) $p->critical_max : '?'))
                                      : '')
                                : null
                        )
                    )->all();
                })
                ->columns(2),

            Forms\Components\Section::make('Informe clínico')
                ->icon('heroicon-o-document-text')
                ->description('Interpretación breve para el paciente según los valores de la muestra.')
                ->visible(fn (Forms\Get $get) => Order::find($get('order_id'))?->type === 'laboratorio')
                ->schema([
                    Forms\Components\Textarea::make('lab_informe')
                        ->label('Informe')
                        ->rows(5)
                        ->required(fn (Forms\Get $get) => Order::find($get('order_id'))?->type === 'laboratorio')
                        ->helperText('Explique de forma clara qué indican los parámetros para este paciente.')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Criticidad (automática)')
                ->icon('heroicon-o-exclamation-triangle')
                ->description('El sistema asigna el estado según los rangos críticos del catálogo; no puede modificarse manualmente.')
                ->schema([
                    Forms\Components\Placeholder::make('critical_explanation')
                        ->label('Estado y motivo')
                        ->columnSpanFull()
                        ->content(function (Forms\Get $get, ?Model $record) {
                            $html = static::labCriticalStatusHtml($get, $record);

                            return $html ? new HtmlString($html) : '';
                        }),
                ])
                ->visible(fn (Forms\Get $get) => Order::find($get('order_id'))?->type === 'laboratorio'),

            Forms\Components\Section::make('Reactivos Consumidos')
                ->icon('heroicon-o-beaker')
                ->description('Registre los reactivos y las cantidades utilizadas para procesar esta orden de laboratorio.')
                ->visible(fn (Forms\Get $get) => Order::find($get('order_id'))?->type === 'laboratorio')
                ->schema([
                    Forms\Components\Repeater::make('reagents_used')
                        ->label('Reactivos')
                        ->schema([
                            Forms\Components\Select::make('reagent_id')
                                ->label('Reactivo')
                                ->options(\App\Domains\Reactivos\Models\Reagent::orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->native(false),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->required()
                                ->minValue(1),
                        ])
                        ->columns(2)
                        ->default([])
                        ->columnSpanFull()
                ]),
        ];
    }

    // ─── Esquema imagen ───────────────────────────────────────────────────────

    /**
     * Secciones exclusivas del flujo de imagen (estudio completado → archivo adjunto + informe + criticidad manual).
     *
     * @return array<int, Forms\Components\Section>
     */
    protected static function imagingFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Informe / hallazgos')
                ->icon('heroicon-o-photo')
                ->visible(fn (Forms\Get $get) => Order::find($get('order_id'))?->type === 'imagen')
                ->schema([
                    Forms\Components\Placeholder::make('imaging_file_preview')
                        ->label('Archivo del estudio completado')
                        ->columnSpanFull()
                        ->content(function (Forms\Get $get): HtmlString {
                            return new HtmlString(
                                static::imagingAttachmentHtmlFromKeys(
                                    $get('order_id'),
                                    $get('exam_id')
                                )
                            );
                        }),
                    Forms\Components\Textarea::make('imaging_informe')
                        ->label('Informe / hallazgos')
                        ->required(fn (Forms\Get $get) => Order::find($get('order_id'))?->type === 'imagen')
                        ->helperText('Redacte aquí la interpretación clínica del estudio. El archivo adjunto se muestra arriba como referencia.')
                        ->rows(6)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Criticidad del hallazgo')
                ->icon('heroicon-o-exclamation-triangle')
                ->description('Marque esta opción si el estudio revela un hallazgo crítico que requiere atención inmediata (fractura compleja, masa, etc.). La alerta al administrador (y al médico derivante, si existe) se envía una sola vez al usar «Confirmar y enviar», cuando el PDF ya se generó y se publicó al portal.')
                ->visible(fn (Forms\Get $get) => Order::find($get('order_id'))?->type === 'imagen')
                ->schema([
                    Forms\Components\Toggle::make('is_critical')
                        ->label('Hallazgo crítico')
                        ->helperText('Active este interruptor si el resultado del estudio de imagen requiere atención urgente.')
                        ->onColor('danger')
                        ->offColor('gray')
                        ->onIcon('heroicon-m-exclamation-triangle')
                        ->offIcon('heroicon-m-check-circle')
                        ->default(false)
                        ->dehydrated()
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * HTML seguro (valores escapados) para el bloque de criticidad en el formulario.
     */
    public static function labCriticalStatusHtml(Forms\Get $get, ?Model $record): string
    {
        $orderId = $get('order_id');
        $examId = $get('exam_id');
        if (! $orderId || ! $examId) {
            return '<p class="cn-embedded-muted">Seleccione orden y examen.</p>';
        }

        $order = Order::with('exams')->find($orderId);
        if ($order?->type !== 'laboratorio') {
            return '';
        }

        $exam = $order->exams->firstWhere('id', $examId);
        if (! $exam) {
            return '';
        }

        $params = ExamParameter::query()
            ->where('exam_category_id', $exam->exam_category_id)
            ->orderBy('name')
            ->get();

        $paramValues = [];
        foreach ($params as $p) {
            $paramValues[$p->id] = $get('param_'.$p->id);
        }

        $eval = LabResultCriticalEvaluator::evaluate($exam, $paramValues);

        $blocks = [];
        if ($eval['is_critical']) {
            $blocks[] = '<p class="cn-embedded-text"><strong>'.e('Clasificación automática:').'</strong> '
                .'<span class="font-semibold text-danger-600 dark:text-danger-400">'.e('Crítico').'</span></p>';
            $blocks[] = '<p class="cn-embedded-text font-medium mt-2">'.e('Motivo:').'</p>'
                .'<ul class="cn-embedded-card__list mt-1">';
            foreach ($eval['reasons'] as $reason) {
                $blocks[] = '<li>'.e($reason).'</li>';
            }
            $blocks[] = '</ul>';
        } else {
            $blocks[] = '<p class="cn-embedded-text"><strong>'.e('Clasificación automática:').'</strong> '
                .e('no crítico').'</p>';
            $blocks[] = '<p class="cn-embedded-muted mt-2">'
                .e('Ningún valor supera los límites críticos definidos en el catálogo para este examen.')
                .'</p>';
        }

        if ($record instanceof Result) {
            $saved = $record->is_critical ? 'crítico' : 'no crítico';
            $blocks[] = '<p class="cn-embedded-muted mt-3" style="font-size:0.75rem;">'
                .e('Último estado guardado en el sistema: '.$saved.'. Tras pulsar «Guardar cambios», se recalculará según los valores anteriores.')
                .'</p>';
        }

        return implode('', $blocks);
    }

    // ─── Tabla ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('N° Orden')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('order.patient.full_name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('order.type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'imagen' ? 'Imagen' : 'Laboratorio')
                    ->color(fn ($state) => $state === 'imagen' ? 'warning' : 'info'),

                Tables\Columns\TextColumn::make('exam.name')
                    ->label('Examen')
                    ->searchable(),

                Tables\Columns\TextColumn::make('is_critical')
                    ->label('Criticidad')
                    ->badge()
                    ->formatStateUsing(fn (?bool $state): string => $state ? 'Crítico' : 'No crítico')
                    ->color(fn (?bool $state): string => $state ? 'danger' : 'gray')
                    ->tooltip('Los resultados nuevos son «No crítico» hasta que el sistema recalcule al guardar valores (laboratorio).'),

                Tables\Columns\TextColumn::make('validated_at')
                    ->label('Validado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Pendiente'),

                Tables\Columns\TextColumn::make('responsibleUser.name')
                    ->label('Responsable')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('order_type')
                    ->label('Tipo de orden')
                    ->options(['laboratorio' => 'Laboratorio', 'imagen' => 'Imagen'])
                    ->query(fn (Builder $query, array $data) => $data['value']
                        ? $query->whereHas('order', fn ($q) => $q->where('type', $data['value']))
                        : $query),

                Tables\Filters\Filter::make('is_critical')
                    ->label('Solo críticos')
                    ->query(fn (Builder $query) => $query->where('is_critical', true))
                    ->toggle(),

                Tables\Filters\Filter::make('pendiente_validacion')
                    ->label('Pendientes de validación')
                    ->query(fn (Builder $query) => $query->whereNull('validated_at'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Result $record) => static::canEdit($record)),

                Tables\Actions\Action::make('confirmar_resultado')
                    ->label('Confirmar y enviar')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar resultado')
                    ->modalDescription('Se validará el resultado, se generará el PDF y se publicará en el portal del paciente. Si el paciente tiene correo registrado, se enviará una copia por email. Usted también podrá descargar el PDF en este equipo. ¿Desea continuar?')
                    ->visible(fn (Result $record) => $record->validated_at === null
                        && $record->hasCompleteData()
                        && Gate::allows('update', $record))
                    ->before(function (Result $record, Tables\Actions\Action $action): void {
                        $record->loadMissing('details');

                        if ($record->isImagingType()) {
                            $text = $record->details->firstWhere('parameter_name', 'Informe')?->value;
                            if (! filled($text)) {
                                Notification::make()
                                    ->title('No se puede confirmar el resultado')
                                    ->body('Debe ingresar el informe del estudio antes de confirmar. Use la opción "Editar".')
                                    ->warning()
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                            }

                            return;
                        }

                        if ($record->isLabType()) {
                            if ($record->labParameterDetails()->isEmpty() || ! filled($record->labInformeText())) {
                                Notification::make()
                                    ->title('No se puede confirmar el resultado')
                                    ->body('Debe cargar los valores del análisis y el informe clínico antes de confirmar. Use la opción "Editar".')
                                    ->warning()
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                            }
                        }
                    })
                    ->action(function (Result $record) {
                        try {
                            DB::transaction(function () use ($record): void {
                                $record->update(['validated_at' => now()]);
                                $record->refresh();
                                $record->load(['order.patient', 'exam', 'responsibleUser', 'details']);

                                app(ResultPdfService::class)->generateAndStore($record);
                                $record->refresh();
                            });

                            $recordFresh = $record->fresh();
                            $delivery = app(ResultDeliveryService::class)->publishAndNotify($recordFresh);
                            app(ResultValidatedNotifier::class)->notifyAfterPublish($recordFresh->fresh());

                            $parts = ['El PDF fue generado correctamente.'];
                            if ($delivery['portal_published'] ?? false) {
                                $parts[] = 'El resultado quedó disponible en el portal del paciente.';
                            }
                            if ($delivery['email_sent'] ?? false) {
                                $parts[] = 'Se envió una copia al correo del paciente.';
                            } elseif (! empty($delivery['email_skipped_reason'])) {
                                $parts[] = 'Correo: '.$delivery['email_skipped_reason'];
                            }

                            Notification::make()
                                ->title('Resultado confirmado')
                                ->body(implode(' ', $parts))
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Error al confirmar resultado')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('descargar_pdf')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn (Result $record): bool => $record->validated_at !== null
                        && filled($record->pdf_path))
                    ->action(function (Result $record) {
                        if (! $record->pdf_path || ! Storage::disk('public')->exists($record->pdf_path)) {
                            Notification::make()
                                ->title('PDF no disponible')
                                ->body('No se encontró el PDF del resultado. Vuelva a generarlo o contacte al administrador.')
                                ->warning()
                                ->send();

                            return null;
                        }

                        return Storage::disk('public')->download(
                            $record->pdf_path,
                            'resultado-'.$record->id.'.pdf'
                        );
                    }),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No hay resultados cargados')
            ->emptyStateDescription('Los resultados se generan desde muestras (laboratorio) o estudios de imagen asociados a órdenes.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->defaultSort('created_at', 'desc');
    }

    // ─── Infolist (detalle) ───────────────────────────────────────────────────

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Resultado')
                ->icon('heroicon-o-document-check')
                ->description('Datos generales del resultado, orden asociada y estado de validación.')
                ->schema([
                    TextEntry::make('order.order_number')->label('N° Orden'),
                    TextEntry::make('order.type')
                        ->label('Tipo')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state === 'imagen' ? 'Imagen' : 'Laboratorio')
                        ->color(fn ($state) => $state === 'imagen' ? 'warning' : 'info'),
                    TextEntry::make('exam.name')->label('Examen'),
                    TextEntry::make('order.patient.full_name')->label('Paciente'),
                    TextEntry::make('responsibleUser.name')->label('Responsable'),
                    TextEntry::make('validated_at')->label('Validado')->dateTime('d/m/Y H:i')->placeholder('Pendiente'),
                    TextEntry::make('is_critical')
                        ->label('Criticidad')
                        ->badge()
                        ->formatStateUsing(fn (?bool $state): string => $state ? 'Crítico' : 'No crítico')
                        ->color(fn (?bool $state): string => $state ? 'danger' : 'gray')
                        ->helperText('Aquí se indica si el resultado es crítico para disparar una alerta de emergencia.'),
                ])
                ->columns(['default' => 1, 'sm' => 2]),

            Section::make('Parámetros')
                ->visible(fn ($record): bool => $record->isLabType())
                ->schema([
                    TextEntry::make('lab_params_table')
                        ->label('')
                        ->html()
                        ->state('render')
                        ->columnSpanFull()
                        ->formatStateUsing(function (Result $record) {
                            $rows = $record->labParameterDetails();
                            if ($rows->isEmpty()) {
                                return '—';
                            }

                            $tableRows = $rows->map(fn ($d) => [
                                e($d->parameter_name),
                                e($d->value),
                                e($d->unit ?? '—'),
                                e($d->reference_min !== null ? (int) $d->reference_min : '?')
                                    .' – '
                                    .e($d->reference_max !== null ? (int) $d->reference_max : '?'),
                            ])->all();

                            return FilamentEmbeddedHtml::table(
                                [
                                    'Parámetro',
                                    ['Valor', 'right'],
                                    'Unidad',
                                    'Rango de referencia',
                                ],
                                $tableRows,
                                4,
                                'Sin parámetros.',
                            );
                        }),
                ]),

            Section::make('Informe')
                ->visible(fn ($record): bool => $record->isLabType())
                ->schema([
                    TextEntry::make('lab_informe_view')
                        ->label('')
                        ->html()
                        ->state('render')
                        ->columnSpanFull()
                        ->formatStateUsing(function (Result $record): HtmlString|string {
                            $t = $record->labInformeText();

                            return $t ? new HtmlString(nl2br(e($t))) : '—';
                        }),
                ]),

            Section::make('Informe / hallazgos')
                ->visible(fn ($record): bool => $record->isImagingType())
                ->schema([
                    TextEntry::make('imaging_informe_display')
                        ->label('')
                        ->html()
                        ->state('render')
                        ->columnSpanFull()
                        ->formatStateUsing(function (Result $record) {
                            $text = $record->details->firstWhere('parameter_name', 'Informe')?->value;

                            return $text ? nl2br(e($text)) : '—';
                        }),
                ]),

            Section::make('Archivo adjunto del estudio de imagen')
                ->visible(fn ($record): bool => $record->isImagingType())
                ->schema([
                    TextEntry::make('imaging_attachment_preview')
                        ->label('')
                        // Sin ->html(): si no, Filament aplica sanitizeHtml y elimina <iframe> (vista previa PDF).
                        ->state('render')
                        ->columnSpanFull()
                        ->formatStateUsing(function ($record): HtmlString {
                            return new HtmlString(
                                static::imagingAttachmentHtmlFromRecord($record)
                            );
                        }),
                ]),
        ]);
    }

    // ─── Query ────────────────────────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return ResponsibleClinicalStaffScoping::scopeResultQueryForPanel(
            parent::getEloquentQuery()
                ->with(['order.patient', 'exam', 'responsibleUser', 'details'])
        );
    }

    // ─── Páginas ──────────────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResults::route('/'),
            'view' => Pages\ViewResult::route('/{record}'),
            'edit' => Pages\EditResult::route('/{record}/edit'),
        ];
    }

    protected static function hasCompletedImagingAttachment(mixed $orderId, mixed $examId): bool
    {
        return static::resolveCompletedImagingStudy($orderId, $examId)?->result_file !== null;
    }

    protected static function resolveCompletedImagingStudy(mixed $orderId, mixed $examId): ?ImagingStudy
    {
        if (! $orderId || ! $examId) {
            return null;
        }

        return ImagingStudy::query()
            ->where('order_id', (int) $orderId)
            ->where('exam_id', (int) $examId)
            ->where('status', 'completado')
            ->latest('id')
            ->first();
    }

    protected static function imagingAttachmentHtmlFromKeys(mixed $orderId, mixed $examId): string
    {
        $study = static::resolveCompletedImagingStudy($orderId, $examId);

        if (! $study || ! $study->result_file) {
            return '<p class="cn-embedded-muted">No hay archivo adjunto cargado para este estudio.</p>';
        }

        $path = $study->result_file;
        if (! Storage::disk('public')->exists($path)) {
            return '<p class="text-sm text-warning-600">El archivo adjunto fue registrado, pero no se encontró en almacenamiento.</p>';
        }

        $url = e(ImagingResultFileSupport::publicWebUrl($path));
        $fileName = e(basename($path));
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $link = '<a href="'.$url.'" target="_blank" rel="noopener" class="text-primary-600 underline">Abrir / descargar archivo adjunto ('.$fileName.')</a>';

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'], true)) {
            return $link.'<div class="mt-2"><img src="'.$url.'" alt="Vista previa del estudio" class="cn-embedded-img-preview" /></div>';
        }

        return $link.'<p class="text-sm text-warning-600" style="margin-top:10px;">'
            .'El archivo no es una imagen compatible (solo JPG, PNG, WebP, GIF o BMP). '
            .'Los PDF ya no se admiten como adjunto del estudio.</p>';
    }

    protected static function imagingAttachmentHtmlFromRecord(Result $record): string
    {
        return static::imagingAttachmentHtmlFromKeys($record->order_id, $record->exam_id);
    }
}
