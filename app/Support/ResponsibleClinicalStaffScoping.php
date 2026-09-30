<?php

namespace App\Support;

use App\Domains\Imaging\Models\ImagingStudy;
use App\Domains\Orders\Models\Order;
use App\Domains\Results\Models\Result;
use App\Domains\Samples\Models\Sample;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Limita listados y acceso por registro a órdenes/muestras/estudios asignados al usuario
 * cuando actúa como Bioquímico (solo lab) o Tecnólogo de Imagen (solo imagen).
 * El rol Administrador no aplica restricción: ve todo el operativo.
 */
final class ResponsibleClinicalStaffScoping
{
    public static function scopeOrderQueryForPanel(Builder $query): Builder
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return $query;
        }
        if ($user->hasRole('Administrador')) {
            return $query;
        }
        if ($user->hasRole('Bioquímico')) {
            return $query->where('orders.type', 'laboratorio')->where('orders.responsible_user_id', $user->id);
        }
        if ($user->hasRole('Tecnólogo de Imagen')) {
            return $query->where('orders.type', 'imagen')->where('orders.responsible_user_id', $user->id);
        }

        return $query;
    }

    public static function scopeSampleQueryForPanel(Builder $query): Builder
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return $query;
        }
        if ($user->hasRole('Administrador') || ! $user->hasRole('Bioquímico')) {
            return $query;
        }

        return $query->whereHas('order', function (Builder $q) use ($user): void {
            $q->where('type', 'laboratorio')->where('responsible_user_id', $user->id);
        });
    }

    public static function scopeImagingStudyQueryForPanel(Builder $query): Builder
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return $query;
        }
        if ($user->hasRole('Administrador') || ! $user->hasRole('Tecnólogo de Imagen')) {
            return $query;
        }

        return $query->whereHas('order', function (Builder $q) use ($user): void {
            $q->where('type', 'imagen')->where('responsible_user_id', $user->id);
        });
    }

    /**
     * Resultados en el panel: mismo criterio que muestras/órdenes para personal clínico;
     * pacientes solo ven resultados de su propio expediente.
     */
    public static function scopeResultQueryForPanel(Builder $query): Builder
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return $query;
        }
        if ($user->hasRole('Administrador')) {
            return $query;
        }
        if ($user->hasRole('Paciente')) {
            $patientId = $user->patient?->id;
            if (! $patientId) {
                return $query->whereRaw('0 = 1');
            }

            return $query->whereHas('order', fn (Builder $q) => $q->where('patient_id', $patientId));
        }
        if ($user->hasRole('Médico')) {
            return $query->whereHas('order', fn (Builder $q) => $q->where('doctor_id', $user->id));
        }
        if ($user->hasRole('Bioquímico')) {
            return $query->whereHas('order', function (Builder $q) use ($user): void {
                $q->where('type', 'laboratorio')->where('responsible_user_id', $user->id);
            });
        }
        if ($user->hasRole('Tecnólogo de Imagen')) {
            return $query->whereHas('order', function (Builder $q) use ($user): void {
                $q->where('type', 'imagen')->where('responsible_user_id', $user->id);
            });
        }

        return $query;
    }

    public static function scopeInvoiceQueryForPanel(Builder $query): Builder
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return $query;
        }
        if ($user->hasRole('Administrador')) {
            return $query;
        }
        if ($user->hasRole('Bioquímico')) {
            return $query->whereHas('order', function (Builder $q) use ($user): void {
                $q->where('type', 'laboratorio')->where('responsible_user_id', $user->id);
            });
        }
        if ($user->hasRole('Tecnólogo de Imagen')) {
            return $query->whereHas('order', function (Builder $q) use ($user): void {
                $q->where('type', 'imagen')->where('responsible_user_id', $user->id);
            });
        }

        return $query;
    }

    public static function userMayAccessOrderInPanel(User $user, Order $order): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }
        if ($user->hasRole('Médico')) {
            return (int) $order->doctor_id === (int) $user->id;
        }
        if ($user->hasRole('Bioquímico')) {
            return $order->type === 'laboratorio'
                && (int) $order->responsible_user_id === (int) $user->id;
        }
        if ($user->hasRole('Tecnólogo de Imagen')) {
            return $order->type === 'imagen'
                && (int) $order->responsible_user_id === (int) $user->id;
        }

        return true;
    }

    public static function userMayAccessSampleInPanel(User $user, Sample $sample): bool
    {
        if ($user->hasRole('Administrador') || ! $user->hasRole('Bioquímico')) {
            return true;
        }
        $order = $sample->relationLoaded('order') ? $sample->order : $sample->order()->first();

        return $order instanceof Order && self::userMayAccessOrderInPanel($user, $order);
    }

    public static function userMayAccessImagingStudyInPanel(User $user, ImagingStudy $study): bool
    {
        if ($user->hasRole('Administrador') || ! $user->hasRole('Tecnólogo de Imagen')) {
            return true;
        }
        $order = $study->relationLoaded('order') ? $study->order : $study->order()->first();

        return $order instanceof Order && self::userMayAccessOrderInPanel($user, $order);
    }

    public static function userMayAccessResultInPanel(User $user, Result $result): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }
        if ($user->hasRole('Paciente')) {
            $patient = $user->patient;
            if (! $patient) {
                return false;
            }

            return (int) $result->order?->patient_id === (int) $patient->id;
        }
        if ($user->hasRole('Médico')) {
            $order = $result->relationLoaded('order') ? $result->order : $result->order()->first();

            return $order instanceof Order && (int) $order->doctor_id === (int) $user->id;
        }
        $order = $result->relationLoaded('order') ? $result->order : $result->order()->first();
        if (! $order instanceof Order) {
            return false;
        }

        return self::userMayAccessOrderInPanel($user, $order);
    }
}
