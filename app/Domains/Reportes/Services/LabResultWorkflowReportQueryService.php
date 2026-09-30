<?php

namespace App\Domains\Reportes\Services;

use App\Domains\Results\Models\Result;
use App\Models\User;
use App\Support\ResponsibleClinicalStaffScoping;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Resultados de laboratorio con muestra (si existe), tiempos aproximados y estado operativo derivado.
 * Criterio de periodo: fecha de creación de la orden (alineado con otros reportes de órdenes).
 */
final class LabResultWorkflowReportQueryService
{
    public const MAX_ROWS_PDF = 500;

    /**
     * @param  array{
     *     category_ids?: array<int, int>,
     *     workflow_statuses?: array<int, string>,
     *     bioquimico_id?: int|null,
     * }  $filters
     */
    public function baseQueryForUser(User $user, Carbon|string $dateFrom, Carbon|string $dateUntil, array $filters = []): Builder
    {
        $from = $dateFrom instanceof Carbon
            ? $dateFrom->copy()->startOfDay()
            : Carbon::parse((string) $dateFrom)->startOfDay();
        $until = $dateUntil instanceof Carbon
            ? $dateUntil->copy()->endOfDay()
            : Carbon::parse((string) $dateUntil)->endOfDay();

        $query = Result::query()
            ->join('orders', 'results.order_id', '=', 'orders.id')
            ->where('orders.type', 'laboratorio')
            ->whereBetween('orders.created_at', [$from, $until])
            ->leftJoin('samples', 'results.sample_id', '=', 'samples.id')
            ->join('exams', 'results.exam_id', '=', 'exams.id')
            ->whereNull('exams.deleted_at')
            ->leftJoin('exam_categories', 'exams.exam_category_id', '=', 'exam_categories.id')
            ->join('patients', 'orders.patient_id', '=', 'patients.id')
            ->orderByDesc('orders.created_at')
            ->select([
                'results.id',
                'orders.order_number',
                'results.validated_at',
                'samples.collected_at',
                'samples.status as sample_status',
                'exams.name as exam_name',
                'exam_categories.name as category_name',
                'patients.first_name as patient_first_name',
                'patients.last_name as patient_last_name',
            ]);

        $query = ResponsibleClinicalStaffScoping::scopeResultQueryForPanel($query);

        $catIds = $filters['category_ids'] ?? [];
        if (is_array($catIds) && $catIds !== []) {
            $ids = array_values(array_filter(array_map('intval', $catIds), fn (int $id): bool => $id > 0));
            if ($ids !== []) {
                $query->whereIn('exam_categories.id', $ids);
            }
        }

        $bioId = $filters['bioquimico_id'] ?? null;
        if (is_numeric($bioId) && (int) $bioId > 0) {
            $query->where('results.bioquimico_id', (int) $bioId);
        }

        $workflow = $filters['workflow_statuses'] ?? [];
        if (is_array($workflow) && $workflow !== []) {
            $allowed = ['pendiente', 'procesando', 'validado'];
            $workflow = array_values(array_intersect($workflow, $allowed));
            if ($workflow !== []) {
                $query->where(function (Builder $q) use ($workflow): void {
                    foreach ($workflow as $w) {
                        if ($w === 'validado') {
                            $q->orWhereNotNull('results.validated_at');
                        } elseif ($w === 'procesando') {
                            $q->orWhere(function (Builder $qq): void {
                                $qq->whereNull('results.validated_at')
                                    ->whereIn('samples.status', ['en_analisis', 'procesada']);
                            });
                        } elseif ($w === 'pendiente') {
                            $q->orWhere(function (Builder $qq): void {
                                $qq->whereNull('results.validated_at')
                                    ->where(function (Builder $qqq): void {
                                        $qqq->whereNull('samples.id')
                                            ->orWhereNotIn('samples.status', ['en_analisis', 'procesada'])
                                            ->orWhereNull('samples.status');
                                    });
                            });
                        }
                    }
                });
            }
        }

        return $query;
    }

    /**
     * @param  array{category_ids?: array<int, int>, workflow_statuses?: array<int, string>, bioquimico_id?: int|null}  $filters
     */
    public function countForUser(User $user, Carbon|string $dateFrom, Carbon|string $dateUntil, array $filters = []): int
    {
        return (int) $this->baseQueryForUser($user, $dateFrom, $dateUntil, $filters)->count();
    }

    /**
     * @param  array{category_ids?: array<int, int>, workflow_statuses?: array<int, string>, bioquimico_id?: int|null}  $filters
     * @return Collection<int, object>
     */
    public function getRowsForPdf(User $user, Carbon|string $dateFrom, Carbon|string $dateUntil, array $filters = []): Collection
    {
        return $this->baseQueryForUser($user, $dateFrom, $dateUntil, $filters)
            ->limit(self::MAX_ROWS_PDF)
            ->get();
    }

    /**
     * @param  array{category_ids?: array<int, int>, workflow_statuses?: array<int, string>, bioquimico_id?: int|null}  $filters
     * @return Collection<int, object>
     */
    public function getRowsForPreview(User $user, Carbon|string $dateFrom, Carbon|string $dateUntil, array $filters = [], int $limit = 40): Collection
    {
        return $this->baseQueryForUser($user, $dateFrom, $dateUntil, $filters)
            ->limit($limit)
            ->get();
    }
}
