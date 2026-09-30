<?php

namespace App\Domains\Reportes\Services;

use App\Domains\Orders\Models\Order;
use App\Models\User;
use App\Support\ResponsibleClinicalStaffScoping;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Una fila por par (orden, examen) según order_exam. Fecha de corte: creación de la orden.
 */
final class OrderExamReportQueryService
{
    public const MAX_ROWS_PDF = 500;

    /**
     * @param  array{
     *     statuses?: array<int, string>,
     *     statuses_all?: bool,
     *     type?: string|null,
     *     type_all?: bool,
     *     patient_ids?: array<int, int>
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

        $query = Order::query()
            ->join('order_exam', 'orders.id', '=', 'order_exam.order_id')
            ->join('exams', 'order_exam.exam_id', '=', 'exams.id')
            ->join('patients', 'orders.patient_id', '=', 'patients.id')
            ->leftJoin('users', 'orders.responsible_user_id', '=', 'users.id')
            ->whereNull('exams.deleted_at')
            ->whereBetween('orders.created_at', [$from, $until])
            ->orderByDesc('orders.created_at')
            ->orderBy('exams.name')
            ->select([
                'orders.order_number',
                'orders.status as order_status',
                'orders.cancellation_reason as order_cancellation_reason',
                'orders.type as order_type',
                'orders.created_at as order_created_at',
                'exams.name as exam_name',
                'exams.price as exam_price',
                'patients.first_name as patient_first_name',
                'patients.last_name as patient_last_name',
                'users.name as responsible_name',
            ]);

        $query = ResponsibleClinicalStaffScoping::scopeOrderQueryForPanel($query);

        $statuses = $filters['statuses'] ?? [];
        if (is_array($statuses) && $statuses !== []) {
            $query->whereIn('orders.status', $statuses);
        }

        $type = $filters['type'] ?? null;
        if (is_string($type) && in_array($type, ['laboratorio', 'imagen'], true)) {
            $query->where('orders.type', $type);
        }

        $patientIds = $filters['patient_ids'] ?? [];
        if (is_array($patientIds) && $patientIds !== []) {
            $ids = array_values(array_unique(array_filter(
                array_map(static fn ($id): int => (int) $id, $patientIds),
                static fn (int $id): bool => $id > 0,
            )));
            if ($ids !== []) {
                $query->whereIn('orders.patient_id', $ids);
            }
        }

        return $query;
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
    public function countForUser(User $user, Carbon|string $dateFrom, Carbon|string $dateUntil, array $filters = []): int
    {
        return (int) $this->baseQueryForUser($user, $dateFrom, $dateUntil, $filters)->count();
    }

    /**
     * @param  array{
     *     statuses?: array<int, string>,
     *     statuses_all?: bool,
     *     type?: string|null,
     *     type_all?: bool,
     *     patient_ids?: array<int, int>
     * }  $filters
     * @return Collection<int, object{
     *     order_number: string,
     *     order_status: string,
     *     order_cancellation_reason: string|null,
     *     order_type: string,
     *     order_created_at: string|null,
     *     exam_name: string,
     *     exam_price: string|float|null,
     *     patient_first_name: string|null,
     *     patient_last_name: string|null,
     *     responsible_name: string|null,
     * }>
     */
    public function getRowsForPdf(User $user, Carbon|string $dateFrom, Carbon|string $dateUntil, array $filters = []): Collection
    {
        return $this->baseQueryForUser($user, $dateFrom, $dateUntil, $filters)
            ->limit(self::MAX_ROWS_PDF)
            ->get();
    }

    /**
     * @param  array{
     *     statuses?: array<int, string>,
     *     statuses_all?: bool,
     *     type?: string|null,
     *     type_all?: bool,
     *     patient_ids?: array<int, int>
     * }  $filters
     * @return Collection<int, object>
     */
    public function getRowsForPreview(User $user, Carbon|string $dateFrom, Carbon|string $dateUntil, array $filters = [], int $limit = 40): Collection
    {
        return $this->baseQueryForUser($user, $dateFrom, $dateUntil, $filters)
            ->limit($limit)
            ->get();
    }
}
