<?php

namespace App\Domains\Payments\Observers;

use App\Domains\Notifications\Notifications\OrdenPagadaAdminNotification;
use App\Domains\Notifications\Notifications\PagoRegistradoNotification;
use App\Domains\Payments\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        if ($invoice->status !== 'pagada') {
            return;
        }

        $this->applyPaidInvoiceSideEffects($invoice);
    }

    public function updated(Invoice $invoice): void
    {
        if (! $invoice->wasChanged('status') || $invoice->status !== 'pagada') {
            return;
        }

        $this->applyPaidInvoiceSideEffects($invoice);
    }

    private function applyPaidInvoiceSideEffects(Invoice $invoice): void
    {
        $order = $invoice->order;
        if (! $order) {
            return;
        }

        if ($order->status === 'pendiente') {
            $order->update(['status' => 'en_proceso']);
        }

        if ($order->responsible_user_id) {
            $responsable = User::find($order->responsible_user_id);
            if ($responsable) {
                $invoice->loadMissing(['order.patient']);
                $responsable->notify(new PagoRegistradoNotification($invoice));
            }
        }

        $admins = User::role('Administrador')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new OrdenPagadaAdminNotification($invoice));
        }
    }
}
