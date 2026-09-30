<?php

namespace App\Domains\Orders\Observers;

use App\Domains\Notifications\Notifications\OrdenNuevaMedicoDerivanteNotification;
use App\Domains\Notifications\Notifications\OrdenNuevaResponsableNotification;
use App\Domains\Orders\Models\Order;
use App\Models\User;

/**
 * Avisos por asignación en la orden:
 *
 * - Responsable operativo: NO se notifica al crear la orden; el aviso principal es
 *   InvoiceObserver + PagoRegistradoNotification (título «📋 Nueva Orden Asignada») cuando el comprobante queda pagado.
 * - Médico derivante: sí puede avisarse al registrar o cambiar el médico (si no es la misma persona que el responsable).
 * - Si tras estar pagada se reasigna el responsable, se envía un aviso de «orden a su nombre» (un solo tipo de aviso,
 *   aunque el mismo usuario sea también médico derivante).
 */
class OrderObserver
{
    public function created(Order $order): void
    {
        $this->notifyMedicoDerivanteIfApplicable($order);
    }

    public function updated(Order $order): void
    {
        if ($order->wasChanged('doctor_id')) {
            $this->notifyMedicoDerivanteIfApplicable($order);
        }

        if ($order->wasChanged('responsible_user_id') && $this->orderInvoiceIsPaid($order)) {
            $this->notifyResponsibleReassignmentAfterPayment($order);
        }
    }

    /**
     * Comprobante de la orden en estado pagada.
     */
    private function orderInvoiceIsPaid(Order $order): bool
    {
        $order->loadMissing('invoice');

        return $order->invoice !== null && $order->invoice->status === 'pagada';
    }

    /**
     * Médico derivante: aviso al crear o al cambiar médico (no duplicar si es el mismo usuario que el responsable).
     */
    private function notifyMedicoDerivanteIfApplicable(Order $order): void
    {
        $did = $order->doctor_id;
        if ($did === null) {
            return;
        }

        $rid = $order->responsible_user_id;
        if ($rid !== null && (int) $did === (int) $rid) {
            return;
        }

        $user = User::find((int) $did);
        if ($user) {
            $user->notify(new OrdenNuevaMedicoDerivanteNotification($order));
        }
    }

    /**
     * Reasignación del responsable cuando la orden ya fue pagada (el aviso inicial lo cubre el pago).
     */
    private function notifyResponsibleReassignmentAfterPayment(Order $order): void
    {
        $rid = $order->responsible_user_id;
        if ($rid === null) {
            return;
        }

        $user = User::find((int) $rid);
        if (! $user) {
            return;
        }

        $user->notify(new OrdenNuevaResponsableNotification($order));
    }
}
