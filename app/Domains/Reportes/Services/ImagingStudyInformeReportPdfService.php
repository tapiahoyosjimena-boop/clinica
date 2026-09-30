<?php

namespace App\Domains\Reportes\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class ImagingStudyInformeReportPdfService
{
    public function __construct(
        private readonly ImagingStudyInformeReportQueryService $queryService,
    ) {}

    /**
     * @param  array{study_statuses?: array<int, string>, equipment_type?: string|null, responsible_id?: int|null, informe_statuses?: array<int, string>}  $filters
     * @return array{pdf: string, rows: Collection<int, object>, total: int}
     */
    public function build(User $user, string|Carbon $dateFrom, string|Carbon $dateUntil, array $filters = []): array
    {
        $total = $this->queryService->countForUser($user, $dateFrom, $dateUntil, $filters);
        $rows = $this->queryService->getRowsForPdf($user, $dateFrom, $dateUntil, $filters);

        $from = $dateFrom instanceof Carbon ? $dateFrom : Carbon::parse($dateFrom);
        $until = $dateUntil instanceof Carbon ? $dateUntil : Carbon::parse($dateUntil);

        $pdf = Pdf::loadView('reportes.imaging-studies-informes-pdf', [
            'rows' => $rows,
            'totalMatching' => $total,
            'capped' => $total > $rows->count(),
            'cap' => ImagingStudyInformeReportQueryService::MAX_ROWS_PDF,
            'generatedAt' => now()->timezone(config('clinic_bank.timezone', config('app.timezone'))),
            'periodFrom' => $from,
            'periodUntil' => $until,
            'requestedBy' => $user->name,
            'roleLabel' => $this->roleLabel($user),
            'filterSummary' => $this->filterSummary($filters),
        ])->setPaper('a4', 'landscape');

        return [
            'pdf' => $pdf->output(),
            'rows' => $rows,
            'total' => $total,
        ];
    }

    private function roleLabel(User $user): string
    {
        if ($user->hasRole('Tecnólogo de Imagen')) {
            return 'Tecnólogo de imagen — estudios asignados';
        }
        if ($user->hasRole('Administrador')) {
            return 'Administración — todos los estudios de imagen';
        }

        return 'Usuario con acceso a estudios de imagen';
    }

    /**
     * @param  array{study_statuses?: array<int, string>, equipment_type?: string|null, responsible_id?: int|null, informe_statuses?: array<int, string>}  $filters
     */
    private function filterSummary(array $filters): ?string
    {
        $parts = [];
        $ss = $filters['study_statuses'] ?? [];
        if (is_array($ss) && $ss !== []) {
            $parts[] = 'Estado estudio: '.implode(', ', $ss);
        }
        if (! empty($filters['equipment_type_all'])) {
            $parts[] = 'Modalidad / equipo: todas';
        } else {
            $et = $filters['equipment_type'] ?? '';
            $eqLabels = [
                'rayos_x' => 'Rayos X',
                'ecógrafo' => 'Ecografía',
                'tomógrafo' => 'Tomografía',
                'otro' => 'Otro',
            ];
            if (is_string($et) && $et !== '' && isset($eqLabels[$et])) {
                $parts[] = 'Modalidad / equipo: '.$eqLabels[$et];
            }
        }
        $rid = $filters['responsible_id'] ?? null;
        if (is_numeric($rid) && (int) $rid > 0) {
            $parts[] = 'Responsable ID: '.(int) $rid;
        }
        $inf = $filters['informe_statuses'] ?? [];
        if (is_array($inf) && $inf !== []) {
            $parts[] = 'Informe: '.implode(', ', $inf);
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
