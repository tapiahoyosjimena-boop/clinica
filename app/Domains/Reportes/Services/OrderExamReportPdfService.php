<?php

namespace App\Domains\Reportes\Services;

use App\Domains\Patients\Models\Patient;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class OrderExamReportPdfService
{
    public function __construct(
        private readonly OrderExamReportQueryService $queryService,
    ) {}

    /**
     * @param  array{
     *     statuses?: array<int, string>,
     *     statuses_all?: bool,
     *     type?: string|null,
     *     type_all?: bool,
     *     patient_ids?: array<int, int>
     * }  $filters
     * @return array{pdf: string, rows: Collection<int, object>, total: int}
     */
    public function build(User $user, string|Carbon $dateFrom, string|Carbon $dateUntil, array $filters = []): array
    {
        $total = $this->queryService->countForUser($user, $dateFrom, $dateUntil, $filters);
        $rows = $this->queryService->getRowsForPdf($user, $dateFrom, $dateUntil, $filters);

        $from = $dateFrom instanceof Carbon ? $dateFrom : Carbon::parse($dateFrom);
        $until = $dateUntil instanceof Carbon ? $dateUntil : Carbon::parse($dateUntil);

        $pdf = Pdf::loadView('reportes.order-exams-pdf', [
            'rows' => $rows,
            'totalMatching' => $total,
            'capped' => $total > $rows->count(),
            'cap' => OrderExamReportQueryService::MAX_ROWS_PDF,
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
            return 'Bioquímico — órdenes de laboratorio asignadas (detalle por examen)';
        }
        if ($user->hasRole('Tecnólogo de Imagen')) {
            return 'Tecnólogo de imagen — órdenes de imagen asignadas (detalle por examen)';
        }
        if ($user->hasRole('Recepcionista')) {
            return 'Recepción — todas las órdenes (detalle por examen)';
        }

        return 'Administración — todas las órdenes (detalle por examen)';
    }

    /**
     * @param  array{
     *     statuses?: array<int, string>,
     *     statuses_all?: bool,
     *     type?: string|null,
     *     type_all?: bool,
     *     patient_ids?: array<int, int>
     * }  $filters
     */
    private function filterSummary(array $filters): ?string
    {
        $parts = [];
        $statusMap = [
            'pendiente' => 'Pendiente',
            'en_proceso' => 'En proceso',
            'completada' => 'Completada',
            'cancelada' => 'Cancelada',
        ];

        if (! empty($filters['statuses_all'])) {
            $parts[] = 'Estados orden: todos';
        } else {
            $statuses = $filters['statuses'] ?? [];
            if (is_array($statuses) && $statuses !== []) {
                $labels = array_map(
                    static fn (string $s): string => $statusMap[$s] ?? $s,
                    $statuses,
                );
                $parts[] = 'Estados orden: '.implode(', ', $labels);
            }
        }

        if (! empty($filters['type_all'])) {
            $parts[] = 'Tipo orden: todos';
        } else {
            $type = $filters['type'] ?? null;
            if (is_string($type) && in_array($type, ['laboratorio', 'imagen'], true)) {
                $parts[] = 'Tipo orden: '.($type === 'imagen' ? 'Imagen' : 'Laboratorio');
            }
        }

        $patientIds = $filters['patient_ids'] ?? [];
        if (is_array($patientIds) && $patientIds !== []) {
            $ids = array_values(array_unique(array_filter(
                array_map(static fn ($id): int => (int) $id, $patientIds),
                static fn (int $id): bool => $id > 0,
            )));
            if ($ids !== []) {
                $names = Patient::query()
                    ->whereIn('id', $ids)
                    ->orderBy('last_name')
                    ->get()
                    ->map(fn (Patient $p): string => trim($p->first_name.' '.$p->last_name).' ('.$p->ci.')')
                    ->all();
                $parts[] = 'Pacientes: '.implode('; ', $names);
            }
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
