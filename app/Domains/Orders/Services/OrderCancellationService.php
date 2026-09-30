<?php

namespace App\Domains\Orders\Services;

use App\Domains\Imaging\Models\ImagingStatusHistory;
use App\Domains\Imaging\Models\ImagingStudy;
use App\Domains\Notifications\Notifications\OrdenCanceladaAdminNotification;
use App\Domains\Orders\Models\Order;
use App\Domains\Samples\Models\Sample;
use App\Domains\Samples\Models\SampleStatusHistory;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

final class OrderCancellationService
{
    /**
     * Cancela la orden, registra auditoría en la orden y anula hijos aún no iniciados
     * (muestras solo «recibida», estudios solo «programado»).
     *
     * @throws AuthorizationException
     */
    public function cancel(Order $order, string $cancellationReason, ?int $actingUserId = null): void
    {
        $actingUserId = $actingUserId ?? auth()->id();
        if (! $actingUserId) {
            throw new AuthorizationException('Se requiere un usuario autenticado para cancelar la orden.');
        }

        DB::transaction(function () use ($order, $cancellationReason, $actingUserId) {
            $order->refresh();

            Gate::authorize('cancel', $order);

            if ($order->hasIrreversibleLabOrImagingProgress()) {
                throw new AuthorizationException(
                    'No se puede cancelar la orden: ya hay muestras en análisis o procesadas, o estudios de imagen ya iniciados o completados.'
                );
            }

            $order->update([
                'status' => 'cancelada',
                'cancellation_reason' => $cancellationReason,
                'cancelled_by_user_id' => $actingUserId,
                'cancelled_at' => now(),
            ]);

            $reasonTrim = trim($cancellationReason);
            $orderRef = $order->order_number;

            $sampleMotivo = 'Anulación automática: orden '.$order->order_number.' cancelada.'
                .($reasonTrim !== '' ? ' Motivo en la orden: '.$reasonTrim : '');

            $samples = Sample::query()
                ->where('order_id', $order->id)
                ->where('status', 'recibida')
                ->get();

            foreach ($samples as $sample) {
                $oldStatus = $sample->status;

                $sample->update([
                    'status' => 'rechazada',
                    'motivo_rechazo' => $sampleMotivo,
                    'rejected_by_user_id' => $actingUserId,
                    'rejected_at' => now(),
                ]);

                SampleStatusHistory::create([
                    'sample_id' => $sample->id,
                    'old_status' => $oldStatus,
                    'new_status' => 'rechazada',
                    'changed_by' => $actingUserId,
                    'notes' => 'Rechazada por cancelación de la orden '.$orderRef
                        .($reasonTrim !== '' ? ' (motivo: '.$reasonTrim.')' : '.'),
                ]);
            }

            $studyNotes = 'Cancelado por cancelación de la orden '.$orderRef
                .($reasonTrim !== '' ? '. Motivo en la orden: '.$reasonTrim : '.');

            $studies = ImagingStudy::query()
                ->where('order_id', $order->id)
                ->where('status', 'programado')
                ->get();

            foreach ($studies as $study) {
                $oldStatus = $study->status;

                $study->update([
                    'status' => 'cancelado',
                    'rejection_reason' => $studyNotes,
                ]);

                ImagingStatusHistory::create([
                    'imaging_study_id' => $study->id,
                    'old_status' => $oldStatus,
                    'new_status' => 'cancelado',
                    'changed_by' => $actingUserId,
                    'notes' => $studyNotes,
                ]);
            }
        });

        $order->refresh();
        $order->loadMissing('cancelledBy');

        $admins = User::role('Administrador')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new OrdenCanceladaAdminNotification($order));
        }
    }
}
