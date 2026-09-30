<?php

namespace App\Domains\Reportes\Services;

use App\Domains\Imaging\Models\ImagingStudy;
use App\Models\User;
use App\Support\ResponsibleClinicalStaffScoping;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Estudios de imagen con datos de informe vía tabla results (misma orden y examen).
 * Periodo: creación del estudio (imaging_studies.created_at).
 */
final class ImagingStudyInformeReportQueryService
{
    public const MAX_ROWS_PDF = 500;

    /**
     * @param  array{
     *     study_statuses?: array<int, string>,
     *     equipment_type?: string|null,
     *     responsible_id?: int|null,
     *     informe_statuses?: array<int, string>,
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

        $query = ImagingStudy::query();
        $query = ResponsibleClinicalStaffScoping::scopeImagingStudyQueryForPanel($query);

        $query->join('orders', 'imaging_studies.order_id', '=', 'orders.id')
            ->join('patients', 'orders.patient_id', '=', 'patients.id')
            ->join('exams', 'imaging_studies.exam_id', '=', 'exams.id')
            ->whereNull('exams.deleted_at')
            ->leftJoin('imaging_equipment', 'imaging_studies.equipment_id', '=', 'imaging_equipment.id')
            ->leftJoin('users as tech', 'imaging_studies.responsible_user_id', '=', 'tech.id')
            ->leftJoin('results', function ($j): void {
                $j->on('results.order_id', '=', 'orders.id')
                    ->on('results.exam_id', '=', 'imaging_studies.exam_id');
            })
            ->whereBetween('imaging_studies.created_at', [$from, $until])
            ->orderByDesc('imaging_studies.created_at')
            ->select([
                'imaging_studies.id',
                'imaging_studies.study_code',
                'imaging_studies.status as study_status',
                'imaging_studies.created_at as study_created_at',
                'imaging_studies.collected_at',
                'exams.name as exam_name',
                'imaging_equipment.type as equipment_type',
                'patients.first_name as patient_first_name',
                'patients.last_name as patient_last_name',
                'tech.name as technologist_name',
                'results.published_to_portal_at',
                'results.validated_at as result_validated_at',
            ]);

        $studyStatuses = $filters['study_statuses'] ?? [];
        if (is_array($studyStatuses) && $studyStatuses !== []) {
            $allowed = ['programado', 'paciente_presente', 'en_proceso', 'completado', 'cancelado'];
            $studyStatuses = array_values(array_intersect($studyStatuses, $allowed));
            if ($studyStatuses !== []) {
                $query->whereIn('imaging_studies.status', $studyStatuses);
            }
        }

        $eqType = $filters['equipment_type'] ?? '';
        if (is_string($eqType) && $eqType !== '') {
            $allowedTypes = ['ecógrafo', 'rayos_x', 'tomógrafo', 'otro'];
            if (in_array($eqType, $allowedTypes, true)) {
                $query->where('imaging_equipment.type', $eqType);
            }
        }

        $rid = $filters['responsible_id'] ?? null;
        if (is_numeric($rid) && (int) $rid > 0) {
            $query->where('imaging_studies.responsible_user_id', (int) $rid);
        }

        $inf = $filters['informe_statuses'] ?? [];
        if (is_array($inf) && $inf !== []) {
            $allowedInf = ['pendiente', 'listo', 'publicado'];
            $inf = array_values(array_intersect($inf, $allowedInf));
            if ($inf !== []) {
                $query->where(function (Builder $q) use ($inf): void {
                    foreach ($inf as $s) {
                        if ($s === 'publicado') {
                            $q->orWhereNotNull('results.published_to_portal_at');
                        } elseif ($s === 'listo') {
                            $q->orWhere(function (Builder $qq): void {
                                $qq->whereNull('results.published_to_portal_at')
                                    ->where(function (Builder $qqq): void {
                                        $qqq->whereNotNull('results.validated_at')
                                            ->orWhere('imaging_studies.status', 'completado');
                                    });
                            });
                        } elseif ($s === 'pendiente') {
                            $q->orWhere(function (Builder $qq): void {
                                $qq->whereNull('results.published_to_portal_at')
                                    ->whereNull('results.validated_at')
                                    ->where('imaging_studies.status', '!=', 'completado');
                            });
                        }
                    }
                });
            }
        }

        return $query;
    }

    /**
     * @param  array{study_statuses?: array<int, string>, equipment_type?: string|null, responsible_id?: int|null, informe_statuses?: array<int, string>}  $filters
     */
    public function countForUser(User $user, Carbon|string $dateFrom, Carbon|string $dateUntil, array $filters = []): int
    {
        return (int) $this->baseQueryForUser($user, $dateFrom, $dateUntil, $filters)->count();
    }

    /**
     * @param  array{study_statuses?: array<int, string>, equipment_type?: string|null, responsible_id?: int|null, informe_statuses?: array<int, string>}  $filters
     * @return Collection<int, object>
     */
    public function getRowsForPdf(User $user, Carbon|string $dateFrom, Carbon|string $dateUntil, array $filters = []): Collection
    {
        return $this->baseQueryForUser($user, $dateFrom, $dateUntil, $filters)
            ->limit(self::MAX_ROWS_PDF)
            ->get();
    }

    /**
     * @param  array{study_statuses?: array<int, string>, equipment_type?: string|null, responsible_id?: int|null, informe_statuses?: array<int, string>}  $filters
     * @return Collection<int, object>
     */
    public function getRowsForPreview(User $user, Carbon|string $dateFrom, Carbon|string $dateUntil, array $filters = [], int $limit = 40): Collection
    {
        return $this->baseQueryForUser($user, $dateFrom, $dateUntil, $filters)
            ->limit($limit)
            ->get();
    }
}
