<?php

namespace App\Domains\Reportes\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class LabResultWorkflowReportPdfService
{
    public function __construct(
        private readonly LabResultWorkflowReportQueryService $queryService,
    ) {}

    /**
     * @param  array{category_ids?: array<int, int>, workflow_statuses?: array<int, string>, bioquimico_id?: int|null}  $filters
     * @return array{pdf: string, rows: Collection<int, object>, total: int}
     */
    public function build(User $user, string|Carbon $dateFrom, string|Carbon $dateUntil, array $filters = []): array
    {
        $total = $this->queryService->countForUser($user, $dateFrom, $dateUntil, $filters);
        $rows = $this->queryService->getRowsForPdf($user, $dateFrom, $dateUntil, $filters);

        $from = $dateFrom instanceof Carbon ? $dateFrom : Carbon::parse($dateFrom);
        $until = $dateUntil instanceof Carbon ? $dateUntil : Carbon::parse($dateUntil);

        $pdf = Pdf::loadView('reportes.lab-result-workflow-pdf', [
            'rows' => $rows,
            'totalMatching' => $total,
            'capped' => $total > $rows->count(),
            'cap' => LabResultWorkflowReportQueryService::MAX_ROWS_PDF,
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
        if ($user->hasRole('Bioquímico')) {
            return 'Bioquímico — resultados de laboratorio (alcance asignado)';
        }
        if ($user->hasRole('Administrador')) {
            return 'Administración — todos los resultados de laboratorio';
        }

        return 'Usuario con acceso a resultados — laboratorio';
    }

    /**
     * @param  array{category_ids?: array<int, int>, workflow_statuses?: array<int, string>, bioquimico_id?: int|null}  $filters
     */
    private function filterSummary(array $filters): ?string
    {
        $parts = [];
        $cats = $filters['category_ids'] ?? [];
        if (is_array($cats) && $cats !== []) {
            $parts[] = 'Categorías: '.count($cats).' seleccionada(s)';
        }
        $wf = $filters['workflow_statuses'] ?? [];
        if (is_array($wf) && $wf !== []) {
            $parts[] = 'Estados: '.implode(', ', $wf);
        }
        $bid = $filters['bioquimico_id'] ?? null;
        if (is_numeric($bid) && (int) $bid > 0) {
            $parts[] = 'Bioquímico ID: '.(int) $bid;
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
