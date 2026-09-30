<?php

namespace App\Services;

use App\Domains\Catalog\Filament\Resources\ExamResource;
use App\Domains\Catalog\Models\Exam;
use App\Domains\Orders\Filament\Resources\OrderResource;
use App\Domains\Orders\Models\Order;
use App\Domains\Patients\Filament\Resources\PatientResource;
use App\Domains\Patients\Models\Patient;
use App\Domains\Results\Models\Result;
use App\Models\User;
use App\Support\ResponsibleClinicalStaffScoping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

final class HomeSearchService
{
    private const LIMIT = 8;

    /**
     * @return list<array{type: string, title: string, subtitle: string|null, url: string|null}>
     */
    public function search(?User $user, string $query): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        if (! $user) {
            return $this->searchPublic($query);
        }

        if ($user->hasRole('Paciente')) {
            return $this->searchForPatient($user, $query);
        }

        if ($user->hasRole('Médico')) {
            return $this->searchForDoctor($user, $query);
        }

        if ($user->hasAnyRole([
            'Administrador',
            'Recepcionista',
            'Bioquímico',
            'Tecnólogo de Imagen',
        ])) {
            return $this->searchForStaff($user, $query);
        }

        return $this->searchPublic($query);
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string|null, url: string|null}>
     */
    private function searchPublic(string $query): array
    {
        $like = $this->likePattern($query);

        return Exam::query()
            ->where(fn (Builder $q) => $this->whereColumnLikeInsensitive($q, 'name', $like))
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Exam $exam): array => [
                'type' => 'examen',
                'title' => $exam->name,
                'subtitle' => $this->examTypeLabel($exam->type),
                'url' => null,
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string|null, url: string|null}>
     */
    private function searchForStaff(User $user, string $query): array
    {
        $results = [];
        $like = $this->likePattern($query);

        if ($user->can('patients.access')) {
            $patients = Patient::query()
                ->where(fn (Builder $q) => $this->applyPatientNameOrCiSearch($q, $like))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(self::LIMIT)
                ->get();

            foreach ($patients as $patient) {
                if (! Gate::forUser($user)->allows('view', $patient)) {
                    continue;
                }

                $results[] = [
                    'type' => 'paciente',
                    'title' => $patient->full_name,
                    'subtitle' => 'CI '.$patient->ci,
                    'url' => PatientResource::getUrl('view', ['record' => $patient]),
                ];
            }
        }

        if ($user->can('orders.access')) {
            $orders = ResponsibleClinicalStaffScoping::scopeOrderQueryForPanel(
                Order::query()->with('patient')
            )
                ->where(function (Builder $q) use ($like): void {
                    $this->whereColumnLikeInsensitive($q, 'order_number', $like);
                    $q->orWhereHas('patient', fn (Builder $pq) => $this->applyPatientNameOrCiSearch($pq, $like));
                })
                ->orderByDesc('created_at')
                ->limit(self::LIMIT)
                ->get();

            foreach ($orders as $order) {
                if (! Gate::forUser($user)->allows('view', $order)) {
                    continue;
                }

                $results[] = [
                    'type' => 'orden',
                    'title' => $order->order_number,
                    'subtitle' => $order->patient?->full_name.' · '.$this->orderStatusLabel($order->status),
                    'url' => OrderResource::getUrl('view', ['record' => $order]),
                ];
            }
        }

        if ($user->can('catalog.access')) {
            $exams = Exam::query()
                ->where(fn (Builder $q) => $this->whereColumnLikeInsensitive($q, 'name', $like))
                ->orderBy('name')
                ->limit(self::LIMIT)
                ->get();

            foreach ($exams as $exam) {
                $results[] = [
                    'type' => 'examen',
                    'title' => $exam->name,
                    'subtitle' => $this->examTypeLabel($exam->type),
                    'url' => ExamResource::getUrl('view', ['record' => $exam]),
                ];
            }
        }

        return array_slice($results, 0, self::LIMIT * 2);
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string|null, url: string|null}>
     */
    private function searchForPatient(User $user, string $query): array
    {
        $patient = $user->patient;
        if (! $patient) {
            return [];
        }

        $like = $this->likePattern($query);
        $results = [];

        $orders = Order::query()
            ->where('patient_id', $patient->id)
            ->where(fn (Builder $q) => $this->whereColumnLikeInsensitive($q, 'order_number', $like))
            ->orderByDesc('created_at')
            ->limit(self::LIMIT)
            ->get();

        foreach ($orders as $order) {
            $results[] = [
                'type' => 'orden',
                'title' => $order->order_number,
                'subtitle' => $this->orderStatusLabel($order->status),
                'url' => route('results.patient.portal'),
            ];
        }

        $labResults = Result::query()
            ->whereHas('order', fn ($q) => $q->where('patient_id', $patient->id))
            ->whereNotNull('published_to_portal_at')
            ->whereHas('exam', fn (Builder $q) => $this->whereColumnLikeInsensitive($q, 'name', $like))
            ->with(['exam', 'order'])
            ->orderByDesc('published_to_portal_at')
            ->limit(self::LIMIT)
            ->get();

        foreach ($labResults as $result) {
            $results[] = [
                'type' => 'resultado',
                'title' => $result->exam?->name ?? 'Resultado',
                'subtitle' => 'Orden '.$result->order?->order_number,
                'url' => route('results.patient.pdf', $result),
            ];
        }

        return array_slice($results, 0, self::LIMIT * 2);
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string|null, url: string|null}>
     */
    private function searchForDoctor(User $user, string $query): array
    {
        $like = $this->likePattern($query);
        $results = [];

        $patients = Patient::query()
            ->whereHas('orders', fn ($q) => $q->where('doctor_id', $user->id))
            ->where(fn (Builder $q) => $this->applyPatientNameOrCiSearch($q, $like))
            ->orderBy('last_name')
            ->limit(self::LIMIT)
            ->get();

        foreach ($patients as $patient) {
            $results[] = [
                'type' => 'paciente',
                'title' => $patient->full_name,
                'subtitle' => 'CI '.$patient->ci,
                'url' => route('doctor.patients'),
            ];
        }

        $orders = Order::query()
            ->where('doctor_id', $user->id)
            ->with('patient')
            ->where(function (Builder $q) use ($like): void {
                $this->whereColumnLikeInsensitive($q, 'order_number', $like);
                $q->orWhereHas('patient', fn (Builder $pq) => $this->applyPatientNameOrCiSearch($pq, $like));
            })
            ->orderByDesc('created_at')
            ->limit(self::LIMIT)
            ->get();

        foreach ($orders as $order) {
            $results[] = [
                'type' => 'orden',
                'title' => $order->order_number,
                'subtitle' => $order->patient?->full_name.' · '.$this->orderStatusLabel($order->status),
                'url' => route('doctor.results'),
            ];
        }

        return array_slice($results, 0, self::LIMIT * 2);
    }

    private function likePattern(string $query): string
    {
        return '%'.addcslashes($query, '%_\\').'%';
    }

    private function whereColumnLikeInsensitive(Builder $query, string $column, string $like): void
    {
        $query->whereRaw('LOWER('.$column.') LIKE ?', [mb_strtolower($like)]);
    }

    private function applyPatientNameOrCiSearch(Builder $query, string $like): void
    {
        $query->where(function (Builder $q) use ($like): void {
            $q->where(fn (Builder $b) => $this->whereColumnLikeInsensitive($b, 'ci', $like))
                ->orWhere(fn (Builder $b) => $this->whereColumnLikeInsensitive($b, 'first_name', $like))
                ->orWhere(fn (Builder $b) => $this->whereColumnLikeInsensitive($b, 'last_name', $like));
        });
    }

    private function examTypeLabel(?string $type): string
    {
        return match ($type) {
            'laboratorio' => 'Laboratorio',
            'imagen' => 'Imagen',
            default => 'Examen',
        };
    }

    private function orderStatusLabel(?string $status): string
    {
        return match ($status) {
            'pendiente' => 'Pendiente',
            'en_proceso' => 'En proceso',
            'completada' => 'Completada',
            'cancelada' => 'Cancelada',
            default => (string) $status,
        };
    }
}
