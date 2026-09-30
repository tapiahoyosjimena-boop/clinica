<?php

namespace App\Domains\Reportes\Filament\Pages;

use App\Domains\Auth\Models\Permission;
use App\Domains\Catalog\Models\ExamCategory;
use App\Domains\Patients\Models\Patient;
use App\Domains\Reportes\Mail\PanelReportPdfMail;
use App\Domains\Reportes\Services\ImagingStudyInformeReportPdfService;
use App\Domains\Reportes\Services\ImagingStudyInformeReportQueryService;
use App\Domains\Reportes\Services\LabResultWorkflowReportPdfService;
use App\Domains\Reportes\Services\LabResultWorkflowReportQueryService;
use App\Domains\Reportes\Services\OrderExamReportPdfService;
use App\Domains\Reportes\Services\OrderExamReportQueryService;
use App\Models\User;
use App\Support\FilamentEmbeddedHtml;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use Illuminate\Support\Str;

/**
 * @property Form $form
 */
class ReportsPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Reportes';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?string $title = 'Reportes';

    protected static ?string $slug = 'reportes';

    protected static string $view = 'domains.reportes.filament.pages.reports-page';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        if (self::reportesAccessPermissionRegistered()) {
            return $user->hasPermissionTo('reportes.access');
        }

        // Instalaciones sin migración / permiso aún no creado: no llamar hasPermissionTo('reportes.access')
        // (Spatie lanza PermissionDoesNotExist si el nombre no existe en BD).
        return self::legacyMayAccessReportsPage($user);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::$shouldRegisterNavigation && static::canAccess();
    }

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);
        $opts = self::availableReportTypes($user);
        abort_if($opts === [], 403);

        $this->form->fill([
            'report_type' => array_key_first($opts),
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_until' => now()->format('Y-m-d'),
            'order_statuses' => ['todos'],
            'order_type' => 'todos',
            'report_patient_ids' => [],
            'lab_category_ids' => [],
            'lab_workflow_statuses' => [],
            'lab_bioquimico_id' => null,
            'img_study_statuses' => [],
            'img_equipment_type' => 'todos',
            'img_responsible_id' => null,
            'img_informe_statuses' => [],
            'emails' => '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form;
    }

    /**
     * @return array<string, Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema($this->getFormSchema())
                    ->statePath('data')
            ),
        ];
    }

    /**
     * @return array<int, Component>
     */
    protected function getFormSchema(): array
    {
        return [
            Section::make('Parámetros')
                ->description('Filtre por fechas, revise la vista previa, descargue el PDF de prueba o envíelo por correo.')
                ->schema([
                    Select::make('report_type')
                        ->label('Tipo de reporte')
                        ->options(fn (): array => self::availableReportTypes(auth()->user()))
                        ->required()
                        ->live()
                        ->native(false),

                    DatePicker::make('date_from')
                        ->label('Desde')
                        ->required()
                        ->native(false)
                        // Sin live(), el Placeholder de la vista previa no se re-renderiza al cambiar fechas.
                        ->live(debounce: 400),

                    DatePicker::make('date_until')
                        ->label('Hasta')
                        ->required()
                        ->native(false)
                        ->afterOrEqual('date_from')
                        ->live(debounce: 400),

                    Select::make('order_statuses')
                        ->label('Estados de la orden')
                        ->multiple()
                        ->native(false)
                        ->live()
                        ->options([
                            'todos' => 'Todos',
                            'pendiente' => 'Pendiente',
                            'en_proceso' => 'En proceso',
                            'completada' => 'Completada',
                            'cancelada' => 'Cancelada',
                        ])
                        ->default(['todos'])
                        ->afterStateUpdated(function (Set $set, ?array $state): void {
                            $state = $state ?? [];
                            if (in_array('todos', $state, true)) {
                                if (count($state) > 1) {
                                    $set('order_statuses', array_values(array_filter(
                                        $state,
                                        static fn (string $v): bool => $v !== 'todos',
                                    )));
                                } else {
                                    $set('order_statuses', ['todos']);
                                }
                            }
                        })
                        ->helperText('«Todos» incluye cualquier estado. Si elige estados concretos, se excluye «Todos».')
                        ->visible(fn (Get $get): bool => $get('report_type') === 'ordenes_examenes'),

                    Select::make('order_type')
                        ->label('Tipo de orden')
                        ->native(false)
                        ->live()
                        ->options([
                            'todos' => 'Todos',
                            'laboratorio' => 'Laboratorio',
                            'imagen' => 'Imagen',
                        ])
                        ->default('todos')
                        ->visible(fn (Get $get): bool => $get('report_type') === 'ordenes_examenes'),

                    Select::make('report_patient_ids')
                        ->label('Paciente(s) (filtro opcional)')
                        ->multiple()
                        ->searchable()
                        ->native(false)
                        ->live()
                        ->getSearchResultsUsing(function (string $search): array {
                            if (strlen(trim($search)) < 2) {
                                return [];
                            }
                            $s = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($search)).'%';

                            return Patient::query()
                                ->where(function ($q) use ($s): void {
                                    $q->where('ci', 'like', $s)
                                        ->orWhere('first_name', 'like', $s)
                                        ->orWhere('last_name', 'like', $s);
                                })
                                ->orderBy('last_name')
                                ->limit(25)
                                ->get()
                                ->mapWithKeys(fn (Patient $p): array => [
                                    (string) $p->id => $p->ci.' — '.trim($p->first_name.' '.$p->last_name),
                                ])
                                ->all();
                        })
                        ->getOptionLabelsUsing(function (array $values): array {
                            $ids = array_values(array_filter(array_map('intval', $values), static fn (int $id): bool => $id > 0));
                            if ($ids === []) {
                                return [];
                            }

                            return Patient::query()
                                ->whereIn('id', $ids)
                                ->orderBy('last_name')
                                ->get()
                                ->mapWithKeys(fn (Patient $p): array => [
                                    (string) $p->id => $p->ci.' — '.trim($p->first_name.' '.$p->last_name),
                                ])
                                ->all();
                        })
                        ->helperText('Sin selección: todos los pacientes del alcance. Puede elegir uno o varios.')
                        ->visible(fn (Get $get): bool => $get('report_type') === 'ordenes_examenes'
                            && (auth()->user()?->can('patients.access') ?? false)),

                    Select::make('lab_category_ids')
                        ->label('Áreas (categoría de examen)')
                        ->multiple()
                        ->native(false)
                        ->live()
                        ->options(fn (): array => ExamCategory::query()
                            ->where('type', 'laboratorio')
                            ->where('is_active', true)
                            ->whereNull('deleted_at')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->visible(fn (Get $get): bool => $get('report_type') === 'lab_resultados_flujo'),

                    Select::make('lab_workflow_statuses')
                        ->label('Estado del proceso')
                        ->multiple()
                        ->native(false)
                        ->live()
                        ->options([
                            'pendiente' => 'Pendiente',
                            'procesando' => 'En proceso (muestra)',
                            'validado' => 'Validado',
                        ])
                        ->visible(fn (Get $get): bool => $get('report_type') === 'lab_resultados_flujo'),

                    Select::make('lab_bioquimico_id')
                        ->label('Bioquímico (resultado)')
                        ->native(false)
                        ->live()
                        ->searchable()
                        ->options(fn (): array => User::role('Bioquímico')->orderBy('name')->pluck('name', 'id')->all())
                        ->visible(fn (Get $get): bool => $get('report_type') === 'lab_resultados_flujo'),

                    Select::make('img_study_statuses')
                        ->label('Estado del estudio')
                        ->multiple()
                        ->native(false)
                        ->live()
                        ->options([
                            'programado' => 'Programado',
                            'paciente_presente' => 'Paciente presente',
                            'en_proceso' => 'En proceso',
                            'completado' => 'Completado',
                            'cancelado' => 'Cancelado',
                        ])
                        ->visible(fn (Get $get): bool => $get('report_type') === 'imagen_estudios_informe'),

                    Select::make('img_equipment_type')
                        ->label('Modalidad / equipo')
                        ->native(false)
                        ->live()
                        ->options([
                            'todos' => 'Todas',
                            'rayos_x' => 'Rayos X',
                            'ecógrafo' => 'Ecografía',
                            'tomógrafo' => 'Tomografía',
                            'otro' => 'Otro',
                        ])
                        ->default('todos')
                        ->visible(fn (Get $get): bool => $get('report_type') === 'imagen_estudios_informe'),

                    Select::make('img_responsible_id')
                        ->label('Tecnólogo responsable')
                        ->native(false)
                        ->live()
                        ->searchable()
                        ->options(fn (): array => User::role('Tecnólogo de Imagen')->orderBy('name')->pluck('name', 'id')->all())
                        ->visible(fn (Get $get): bool => $get('report_type') === 'imagen_estudios_informe'),

                    Select::make('img_informe_statuses')
                        ->label('Estado del informe (derivado)')
                        ->multiple()
                        ->native(false)
                        ->live()
                        ->options([
                            'pendiente' => 'Pendiente',
                            'listo' => 'Listo / interpretado',
                            'publicado' => 'Publicado al portal',
                        ])
                        ->visible(fn (Get $get): bool => $get('report_type') === 'imagen_estudios_informe'),

                    Placeholder::make('vista_previa')
                        ->label('Vista previa')
                        ->content(fn (Get $get): HtmlString => $this->previewHtml($get))
                        ->columnSpanFull(),

                    Textarea::make('emails')
                        ->label('Correos destino')
                        ->rows(3)
                        ->placeholder("ejemplo@clinica.com\nejemplo2@clinica.com")
                        ->helperText('Obligatorio solo al usar «Generar PDF y enviar». Para probar el PDF use «Descargar PDF».')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('downloadReportPdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action('downloadReportPdf')
                ->tooltip('Genera el mismo PDF que se adjunta al correo y lo descarga en su equipo (sin enviar email).'),
            Action::make('generateReport')
                ->label('Generar PDF y enviar')
                ->submit('generateReport'),
        ];
    }

    public function downloadReportPdf(): void
    {
        $ctx = $this->resolveValidatedReportContext(requireEmails: false);
        if ($ctx === null) {
            return;
        }

        $file = match ($ctx['type']) {
            'ordenes_examenes' => $this->pdfFilenamePairForOrderExams($ctx['user'], $ctx['from'], $ctx['until'], $ctx['data']),
            'lab_resultados_flujo' => $this->pdfFilenamePairForLabWorkflow($ctx['user'], $ctx['from'], $ctx['until'], $ctx['data']),
            'imagen_estudios_informe' => $this->pdfFilenamePairForImagingInforme($ctx['user'], $ctx['from'], $ctx['until'], $ctx['data']),
            default => null,
        };
        if ($file === null) {
            return;
        }

        $token = (string) Str::uuid();
        Cache::put(
            'report_panel_pdf_preview:'.$token,
            [
                'user_id' => $ctx['user']->id,
                'content' => $file['pdf'],
                'filename' => $file['filename'],
            ],
            now()->addMinutes(10),
        );

        $url = route('reportes.panel-preview-pdf', ['token' => $token]);
        $this->js('window.open('.Js::from($url).', "_blank")');

        Notification::make()
            ->title('Descarga iniciada')
            ->body('Si el navegador bloqueó la ventana emergente, permita ventanas para este sitio y vuelva a intentar.')
            ->success()
            ->send();
    }

    public function generateReport(): void
    {
        $ctx = $this->resolveValidatedReportContext(requireEmails: true);
        if ($ctx === null) {
            return;
        }

        $user = $ctx['user'];
        $data = $ctx['data'];
        $type = $ctx['type'];
        $from = $ctx['from'];
        $until = $ctx['until'];
        $emails = $ctx['emails'];

        match ($type) {
            'ordenes_examenes' => $this->sendOrderExamsReport($user, $from, $until, $data, $emails),
            'lab_resultados_flujo' => $this->sendLabWorkflowReport($user, $from, $until, $data, $emails),
            'imagen_estudios_informe' => $this->sendImagingInformeReport($user, $from, $until, $data, $emails),
            default => null,
        };
    }

    /**
     * @return array{user: User, data: array<string, mixed>, type: string, from: Carbon, until: Carbon, emails: array<int, string>}|null
     */
    private function resolveValidatedReportContext(bool $requireEmails): ?array
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return null;
        }

        $data = $this->form->getState();
        $allowed = self::availableReportTypes($user);
        $type = (string) ($data['report_type'] ?? '');
        if (! array_key_exists($type, $allowed)) {
            Notification::make()
                ->title('Tipo de reporte no permitido')
                ->danger()
                ->send();

            return null;
        }

        $emails = self::parseEmailList((string) ($data['emails'] ?? ''));
        if ($requireEmails) {
            foreach ($emails as $email) {
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    Notification::make()
                        ->title('Correo no válido')
                        ->body('Revise el formato: '.$email)
                        ->danger()
                        ->send();

                    return null;
                }
            }
            if ($emails === []) {
                Notification::make()
                    ->title('Sin destinatarios')
                    ->body('Indique al menos un correo electrónico para enviar el reporte.')
                    ->danger()
                    ->send();

                return null;
            }
        }

        try {
            $from = Carbon::parse($data['date_from'])->startOfDay();
            $until = Carbon::parse($data['date_until'])->endOfDay();
        } catch (\Throwable) {
            Notification::make()
                ->title('Fechas inválidas')
                ->danger()
                ->send();

            return null;
        }
        if ($until->lt($from)) {
            Notification::make()
                ->title('Rango de fechas inválido')
                ->body('«Hasta» debe ser posterior o igual a «Desde».')
                ->danger()
                ->send();

            return null;
        }

        return [
            'user' => $user,
            'data' => $data,
            'type' => $type,
            'from' => $from,
            'until' => $until,
            'emails' => $emails,
        ];
    }

    /**
     * @return array{pdf: string, filename: string}|null
     */
    private function pdfFilenamePairForOrderExams(User $user, Carbon $from, Carbon $until, array $data): ?array
    {
        $built = $this->makeOrderExamsPdf($user, $from, $until, $data);
        if ($built === null) {
            return null;
        }

        return [
            'pdf' => $built['pdf'],
            'filename' => 'ordenes_examenes_'.$from->format('Y-m-d').'_'.$until->format('Y-m-d').'.pdf',
        ];
    }

    /**
     * @return array{pdf: string, filename: string}|null
     */
    private function pdfFilenamePairForLabWorkflow(User $user, Carbon $from, Carbon $until, array $data): ?array
    {
        $built = $this->makeLabWorkflowPdf($user, $from, $until, $data);
        if ($built === null) {
            return null;
        }

        return [
            'pdf' => $built['pdf'],
            'filename' => 'lab_proceso_'.$from->format('Y-m-d').'_'.$until->format('Y-m-d').'.pdf',
        ];
    }

    /**
     * @return array{pdf: string, filename: string}|null
     */
    private function pdfFilenamePairForImagingInforme(User $user, Carbon $from, Carbon $until, array $data): ?array
    {
        $built = $this->makeImagingInformePdf($user, $from, $until, $data);
        if ($built === null) {
            return null;
        }

        return [
            'pdf' => $built['pdf'],
            'filename' => 'imagen_informes_'.$from->format('Y-m-d').'_'.$until->format('Y-m-d').'.pdf',
        ];
    }

    /**
     * @return array{pdf: string, total: int}|null
     */
    private function makeOrderExamsPdf(User $user, Carbon $from, Carbon $until, array $data): ?array
    {
        $filters = $this->orderExamFiltersFromForm($data);
        try {
            $built = app(OrderExamReportPdfService::class)->build($user, $from, $until, $filters);

            return ['pdf' => $built['pdf'], 'total' => $built['total']];
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->title('Error al generar el PDF')
                ->body(config('app.debug') ? $e->getMessage() : 'Intente de nuevo o contacte a soporte.')
                ->danger()
                ->send();

            return null;
        }
    }

    /**
     * @return array{pdf: string, total: int}|null
     */
    private function makeLabWorkflowPdf(User $user, Carbon $from, Carbon $until, array $data): ?array
    {
        $filters = $this->labWorkflowFiltersFromForm($data);
        try {
            $built = app(LabResultWorkflowReportPdfService::class)->build($user, $from, $until, $filters);

            return ['pdf' => $built['pdf'], 'total' => $built['total']];
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->title('Error al generar el PDF')
                ->body(config('app.debug') ? $e->getMessage() : 'Intente de nuevo o contacte a soporte.')
                ->danger()
                ->send();

            return null;
        }
    }

    /**
     * @return array{pdf: string, total: int}|null
     */
    private function makeImagingInformePdf(User $user, Carbon $from, Carbon $until, array $data): ?array
    {
        $filters = $this->imagingInformeFiltersFromForm($data);
        try {
            $built = app(ImagingStudyInformeReportPdfService::class)->build($user, $from, $until, $filters);

            return ['pdf' => $built['pdf'], 'total' => $built['total']];
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->title('Error al generar el PDF')
                ->body(config('app.debug') ? $e->getMessage() : 'Intente de nuevo o contacte a soporte.')
                ->danger()
                ->send();

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $emails
     */
    private function sendOrderExamsReport(User $user, Carbon $from, Carbon $until, array $data, array $emails): void
    {
        $built = $this->makeOrderExamsPdf($user, $from, $until, $data);
        if ($built === null) {
            return;
        }
        $filename = 'ordenes_examenes_'.$from->format('Y-m-d').'_'.$until->format('Y-m-d').'.pdf';
        $subject = 'Órdenes y exámenes ('.$from->format('d/m/Y').' — '.$until->format('d/m/Y').') — Clínica Norte';
        if (! $this->dispatchReportMail(
            $emails,
            $built['pdf'],
            $filename,
            $subject,
            reportTitle: 'Órdenes y exámenes (detalle por examen)',
            reportDescription: 'Incluye paciente, examen, fechas y estado de cada ítem según los filtros seleccionados.',
            periodLabel: $from->format('d/m/Y').' — '.$until->format('d/m/Y'),
            generatedByName: $user->name,
            rowCount: $built['total'],
        )) {
            return;
        }
        Notification::make()
            ->title('Reporte enviado')
            ->body('Se envió el PDF a '.count($emails).' destinatario(s). Filas que coinciden con el filtro: '.$built['total'].'.')
            ->success()
            ->send();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $emails
     */
    private function sendLabWorkflowReport(User $user, Carbon $from, Carbon $until, array $data, array $emails): void
    {
        $built = $this->makeLabWorkflowPdf($user, $from, $until, $data);
        if ($built === null) {
            return;
        }
        $filename = 'lab_proceso_'.$from->format('Y-m-d').'_'.$until->format('Y-m-d').'.pdf';
        $subject = 'Resultados laboratorio — proceso ('.$from->format('d/m/Y').' — '.$until->format('d/m/Y').') — Clínica Norte';
        if (! $this->dispatchReportMail(
            $emails,
            $built['pdf'],
            $filename,
            $subject,
            reportTitle: 'Resultados de laboratorio — proceso y tiempos',
            reportDescription: 'Resume el flujo de muestras y resultados (recepción, análisis, validación) en el período indicado.',
            periodLabel: $from->format('d/m/Y').' — '.$until->format('d/m/Y'),
            generatedByName: $user->name,
            rowCount: $built['total'],
        )) {
            return;
        }
        Notification::make()
            ->title('Reporte enviado')
            ->body('Se envió el PDF a '.count($emails).' destinatario(s). Filas que coinciden con el filtro: '.$built['total'].'.')
            ->success()
            ->send();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $emails
     */
    private function sendImagingInformeReport(User $user, Carbon $from, Carbon $until, array $data, array $emails): void
    {
        $built = $this->makeImagingInformePdf($user, $from, $until, $data);
        if ($built === null) {
            return;
        }
        $filename = 'imagen_informes_'.$from->format('Y-m-d').'_'.$until->format('Y-m-d').'.pdf';
        $subject = 'Estudios de imagen e informes ('.$from->format('d/m/Y').' — '.$until->format('d/m/Y').') — Clínica Norte';
        if (! $this->dispatchReportMail(
            $emails,
            $built['pdf'],
            $filename,
            $subject,
            reportTitle: 'Estudios de imagen e informes',
            reportDescription: 'Detalla estudios realizados, tecnólogo responsable y estado del informe según los filtros del panel.',
            periodLabel: $from->format('d/m/Y').' — '.$until->format('d/m/Y'),
            generatedByName: $user->name,
            rowCount: $built['total'],
        )) {
            return;
        }
        Notification::make()
            ->title('Reporte enviado')
            ->body('Se envió el PDF a '.count($emails).' destinatario(s). Filas que coinciden con el filtro: '.$built['total'].'.')
            ->success()
            ->send();
    }

    /**
     * @param  array<int, string>  $emails
     */
    private function dispatchReportMail(
        array $emails,
        string $pdf,
        string $filename,
        string $subject,
        string $reportTitle,
        string $reportDescription,
        string $periodLabel,
        ?string $generatedByName = null,
        ?int $rowCount = null,
    ): bool {
        try {
            Mail::to($emails)->send(new PanelReportPdfMail(
                pdfBinary: $pdf,
                attachmentFilename: $filename,
                subjectLine: $subject,
                reportTitle: $reportTitle,
                reportDescription: $reportDescription,
                periodLabel: $periodLabel,
                generatedByName: $generatedByName,
                rowCount: $rowCount,
            ));

            return true;
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->title('Error al enviar el correo')
                ->body(config('app.debug') ? $e->getMessage() : 'Verifique la configuración de correo (MAIL_*).')
                ->danger()
                ->send();

            return false;
        }
    }

    private function previewHtml(Get $get): HtmlString
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return new HtmlString('');
        }
        $fromRaw = $get('date_from');
        $untilRaw = $get('date_until');
        $type = (string) $get('report_type');
        if (! $fromRaw || ! $untilRaw) {
            return new HtmlString(FilamentEmbeddedHtml::muted('Indique fecha desde y hasta.'));
        }
        try {
            $from = Carbon::parse($fromRaw)->startOfDay();
            $until = Carbon::parse($untilRaw)->endOfDay();
        } catch (\Throwable) {
            return new HtmlString('<p class="cn-embedded-text text-danger-600 dark:text-danger-400">Las fechas no son válidas.</p>');
        }
        if ($until->lt($from)) {
            return new HtmlString('<p class="cn-embedded-text text-danger-600 dark:text-danger-400">«Hasta» debe ser posterior o igual a «Desde».</p>');
        }

        return match ($type) {
            'ordenes_examenes' => $this->previewOrderExams($user, $from, $until, $get),
            'lab_resultados_flujo' => $this->previewLabWorkflow($user, $from, $until, $get),
            'imagen_estudios_informe' => $this->previewImagingInforme($user, $from, $until, $get),
            default => new HtmlString(FilamentEmbeddedHtml::muted('Seleccione un tipo de reporte.')),
        };
    }

    private function previewOrderExams(User $user, Carbon $from, Carbon $until, Get $get): HtmlString
    {
        $filters = $this->orderExamFiltersFromForm([
            'order_statuses' => $get('order_statuses'),
            'order_type' => $get('order_type'),
            'report_patient_ids' => $get('report_patient_ids'),
        ]);
        $svc = app(OrderExamReportQueryService::class);
        $total = $svc->countForUser($user, $from, $until, $filters);
        $rows = $svc->getRowsForPreview($user, $from, $until, $filters, 40);
        $more = max(0, $total - $rows->count());
        $warn = $total > OrderExamReportQueryService::MAX_ROWS_PDF
            ? '(máx. '.OrderExamReportQueryService::MAX_ROWS_PDF.' en PDF)'
            : null;
        $html = FilamentEmbeddedHtml::intro('Filas (orden × examen):', (string) $total, $warn);

        $headers = [
            ['N° Orden', 'left'],
            ['Paciente', 'left'],
            ['Examen', 'left'],
            ['Precio', 'right'],
            ['Fecha orden', 'left'],
            ['Estado', 'left'],
            ['Motivo cancelación', 'left'],
            ['Responsable', 'left'],
        ];
        $tz = config('clinic_bank.timezone', config('app.timezone'));
        $cur = e((string) config('clinic_bank.currency', 'BOB'));
        $tableRows = [];
        foreach ($rows as $r) {
            $patient = e(trim(($r->patient_first_name ?? '').' '.($r->patient_last_name ?? '')));
            $st = match ($r->order_status ?? '') {
                'pendiente' => 'Pendiente',
                'en_proceso' => 'En proceso',
                'completada' => 'Completada',
                'cancelada' => 'Cancelada',
                default => e((string) ($r->order_status ?? '—')),
            };
            $fd = $r->order_created_at
                ? e(Carbon::parse($r->order_created_at)->timezone($tz)->format('d/m/Y H:i'))
                : '—';
            $price = number_format((float) ($r->exam_price ?? 0), 2, ',', '.');
            $cancelReason = (($r->order_status ?? '') === 'cancelada' && filled($r->order_cancellation_reason ?? null))
                ? e((string) $r->order_cancellation_reason)
                : '—';
            $tableRows[] = [
                e($r->order_number),
                $patient,
                e($r->exam_name),
                e($price).' '.$cur,
                $fd,
                $st,
                $cancelReason,
                e($r->responsible_name ?? '—'),
            ];
        }

        $html .= FilamentEmbeddedHtml::table($headers, $tableRows, 8, 'Sin filas en el periodo.');
        if ($more > 0) {
            $html .= FilamentEmbeddedHtml::footnote('… y '.$more.' más.');
        }

        return new HtmlString($html);
    }

    private function previewLabWorkflow(User $user, Carbon $from, Carbon $until, Get $get): HtmlString
    {
        $filters = $this->labWorkflowFiltersFromForm([
            'lab_category_ids' => $get('lab_category_ids'),
            'lab_workflow_statuses' => $get('lab_workflow_statuses'),
            'lab_bioquimico_id' => $get('lab_bioquimico_id'),
        ]);
        $svc = app(LabResultWorkflowReportQueryService::class);
        $total = $svc->countForUser($user, $from, $until, $filters);
        $rows = $svc->getRowsForPreview($user, $from, $until, $filters, 40);
        $more = max(0, $total - $rows->count());
        $warn = $total > LabResultWorkflowReportQueryService::MAX_ROWS_PDF
            ? '(máx. '.LabResultWorkflowReportQueryService::MAX_ROWS_PDF.' en PDF)'
            : null;
        $html = FilamentEmbeddedHtml::intro('Filas:', (string) $total, $warn);

        $headers = ['Orden', 'Paciente', 'Examen', 'Área', 'Recepción', 'Tiempo', 'Estado'];
        $tz = config('clinic_bank.timezone', config('app.timezone'));
        $nowTz = now()->timezone($tz);
        $tableRows = [];
        foreach ($rows as $r) {
            $patient = e(trim(($r->patient_first_name ?? '').' '.($r->patient_last_name ?? '')));
            $col = $r->collected_at ? Carbon::parse($r->collected_at)->timezone($tz) : null;
            $val = $r->validated_at ? Carbon::parse($r->validated_at)->timezone($tz) : null;
            $sampleSt = $r->sample_status;
            if ($val) {
                $estado = 'Validado';
                $mins = $col ? $col->diffInMinutes($val) : null;
            } elseif (in_array($sampleSt, ['en_analisis', 'procesada'], true)) {
                $estado = 'En proceso';
                $mins = $col ? $col->diffInMinutes($nowTz) : null;
            } else {
                $estado = 'Pendiente';
                $mins = $col ? $col->diffInMinutes($nowTz) : null;
            }
            $tiempo = $mins !== null
                ? (($hours = intdiv((int) $mins, 60)) > 0 ? "{$hours}h ".((int) $mins % 60).'m' : ((int) $mins % 60).'m')
                : '—';
            $rec = $col ? e($col->format('d/m/Y H:i')) : '—';
            $tableRows[] = [
                e($r->order_number),
                $patient,
                e($r->exam_name),
                e($r->category_name ?? '—'),
                $rec,
                e($tiempo),
                e($estado),
            ];
        }

        $html .= FilamentEmbeddedHtml::table($headers, $tableRows, 7, 'Sin filas.');
        if ($more > 0) {
            $html .= FilamentEmbeddedHtml::footnote('… y '.$more.' más.');
        }

        return new HtmlString($html);
    }

    private function previewImagingInforme(User $user, Carbon $from, Carbon $until, Get $get): HtmlString
    {
        $filters = $this->imagingInformeFiltersFromForm([
            'img_study_statuses' => $get('img_study_statuses'),
            'img_equipment_type' => $get('img_equipment_type'),
            'img_responsible_id' => $get('img_responsible_id'),
            'img_informe_statuses' => $get('img_informe_statuses'),
        ]);
        $svc = app(ImagingStudyInformeReportQueryService::class);
        $total = $svc->countForUser($user, $from, $until, $filters);
        $rows = $svc->getRowsForPreview($user, $from, $until, $filters, 40);
        $more = max(0, $total - $rows->count());
        $warn = $total > ImagingStudyInformeReportQueryService::MAX_ROWS_PDF
            ? '(máx. '.ImagingStudyInformeReportQueryService::MAX_ROWS_PDF.' en PDF)'
            : null;
        $html = FilamentEmbeddedHtml::intro('Filas:', (string) $total, $warn);

        $headers = ['Paciente', 'Examen', 'Fecha', 'Tecnólogo', 'Estado estudio', 'Informe'];
        $tz = config('clinic_bank.timezone', config('app.timezone'));
        $tableRows = [];
        foreach ($rows as $r) {
            $patient = e(trim(($r->patient_first_name ?? '').' '.($r->patient_last_name ?? '')));
            $dt = $r->collected_at
                ? Carbon::parse($r->collected_at)->timezone($tz)
                : ($r->study_created_at ? Carbon::parse($r->study_created_at)->timezone($tz) : null);
            $fecha = $dt ? e($dt->format('d/m/Y H:i')) : '—';
            $pub = $r->published_to_portal_at;
            $rv = $r->result_validated_at;
            if ($pub) {
                $inf = 'Publicado';
            } elseif ($rv || ($r->study_status ?? '') === 'completado') {
                $inf = 'Listo';
            } else {
                $inf = 'Pendiente';
            }
            $studySt = match ($r->study_status ?? '') {
                'programado' => 'Programado',
                'paciente_presente' => 'Paciente presente',
                'en_proceso' => 'En proceso',
                'completado' => 'Completado',
                'cancelado' => 'Cancelado',
                default => (string) ($r->study_status ?? '—'),
            };
            $tableRows[] = [
                $patient,
                e($r->exam_name),
                $fecha,
                e($r->technologist_name ?? '—'),
                e($studySt),
                e($inf),
            ];
        }

        $html .= FilamentEmbeddedHtml::table($headers, $tableRows, 6, 'Sin filas.');
        if ($more > 0) {
            $html .= FilamentEmbeddedHtml::footnote('… y '.$more.' más.');
        }

        return new HtmlString($html);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{statuses?: array<int, string>, type?: string}
     */
    private function orderFiltersFromForm(array $data): array
    {
        $filters = [];
        $statuses = $data['order_statuses'] ?? [];
        if (! is_array($statuses)) {
            $statuses = [];
        }
        if ($statuses === [] || in_array('todos', $statuses, true)) {
            $filters['statuses_all'] = true;
        } else {
            $allowed = ['pendiente', 'en_proceso', 'completada', 'cancelada'];
            $picked = array_values(array_intersect($statuses, $allowed));
            if ($picked !== []) {
                $filters['statuses'] = $picked;
            } else {
                $filters['statuses_all'] = true;
            }
        }

        $type = $data['order_type'] ?? 'todos';
        if (is_string($type) && in_array($type, ['laboratorio', 'imagen'], true)) {
            $filters['type'] = $type;
        } else {
            $filters['type_all'] = true;
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     statuses?: array<int, string>,
     *     statuses_all?: bool,
     *     type?: string,
     *     type_all?: bool,
     *     patient_ids?: array<int, int>
     * }
     */
    private function orderExamFiltersFromForm(array $data): array
    {
        $filters = $this->orderFiltersFromForm($data);
        $patientIds = $data['report_patient_ids'] ?? [];
        if (is_array($patientIds) && $patientIds !== []) {
            $ids = array_values(array_unique(array_filter(
                array_map(static fn ($id): int => (int) $id, $patientIds),
                static fn (int $id): bool => $id > 0,
            )));
            if ($ids !== []) {
                $filters['patient_ids'] = $ids;
            }
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{category_ids?: array<int, int>, workflow_statuses?: array<int, string>, bioquimico_id?: int|null}
     */
    private function labWorkflowFiltersFromForm(array $data): array
    {
        $filters = [];
        $cats = $data['lab_category_ids'] ?? [];
        if (is_array($cats) && $cats !== []) {
            $filters['category_ids'] = array_values(array_filter(array_map('intval', $cats), fn (int $id): bool => $id > 0));
        }
        $wf = $data['lab_workflow_statuses'] ?? [];
        if (is_array($wf) && $wf !== []) {
            $allowed = ['pendiente', 'procesando', 'validado'];
            $filters['workflow_statuses'] = array_values(array_intersect($wf, $allowed));
        }
        $bio = $data['lab_bioquimico_id'] ?? null;
        if (is_numeric($bio) && (int) $bio > 0) {
            $filters['bioquimico_id'] = (int) $bio;
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{study_statuses?: array<int, string>, equipment_type?: string|null, responsible_id?: int|null, informe_statuses?: array<int, string>}
     */
    private function imagingInformeFiltersFromForm(array $data): array
    {
        $filters = [];
        $st = $data['img_study_statuses'] ?? [];
        if (is_array($st) && $st !== []) {
            $allowed = ['programado', 'paciente_presente', 'en_proceso', 'completado', 'cancelado'];
            $filters['study_statuses'] = array_values(array_intersect($st, $allowed));
        }
        $eq = $data['img_equipment_type'] ?? 'todos';
        $allowedEquipment = ['rayos_x', 'ecógrafo', 'tomógrafo', 'otro'];
        if (is_string($eq) && in_array($eq, $allowedEquipment, true)) {
            $filters['equipment_type'] = $eq;
        } else {
            $filters['equipment_type_all'] = true;
        }
        $rid = $data['img_responsible_id'] ?? null;
        if (is_numeric($rid) && (int) $rid > 0) {
            $filters['responsible_id'] = (int) $rid;
        }
        $inf = $data['img_informe_statuses'] ?? [];
        if (is_array($inf) && $inf !== []) {
            $allowedInf = ['pendiente', 'listo', 'publicado'];
            $filters['informe_statuses'] = array_values(array_intersect($inf, $allowedInf));
        }

        return $filters;
    }

    /**
     * @return array<int, string>
     */
    private static function parseEmailList(string $raw): array
    {
        return array_values(array_unique(array_filter(
            preg_split('/[\s,;]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY) ?: []
        )));
    }

    /**
     * @return array<string, string>
     */
    private static function availableReportTypes(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        if (self::reportesAccessPermissionRegistered()) {
            if (! $user->hasPermissionTo('reportes.access')) {
                return [];
            }
        } elseif (! self::legacyMayAccessReportsPage($user)) {
            return [];
        }

        return [
            'ordenes_examenes' => 'Órdenes por examen (detalle)',
            'lab_resultados_flujo' => 'Laboratorio — proceso y tiempos',
            'imagen_estudios_informe' => 'Imagen — estudios e informes',
        ];
    }

    private static function reportesAccessPermissionRegistered(): bool
    {
        return Permission::query()
            ->where('name', 'reportes.access')
            ->where('guard_name', 'web')
            ->exists();
    }

    /**
     * Criterio previo a `reportes.access`: personal de panel con al menos un módulo relacionado.
     */
    private static function legacyMayAccessReportsPage(User $user): bool
    {
        if (! $user->hasAnyRole(['Recepcionista', 'Bioquímico', 'Tecnólogo de Imagen', 'Administrador'])) {
            return false;
        }

        return $user->hasPermissionTo('orders.access')
            || $user->hasPermissionTo('results.access')
            || $user->hasPermissionTo('imaging.access');
    }
}
