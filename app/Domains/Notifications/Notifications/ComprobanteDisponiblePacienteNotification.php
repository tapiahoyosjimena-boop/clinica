<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Payments\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Aviso en el portal del paciente cuando el comprobante de pago (PDF) queda disponible.
 */
class ComprobanteDisponiblePacienteNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Invoice $invoice) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->invoice->loadMissing('order');

        $comprobante = $this->invoice->invoice_number ?? '—';
        $orden = $this->invoice->order?->order_number ?? '—';

        return [
            'format' => 'patient_portal',
            'title' => '🧾 Comprobante de pago disponible',
            'body' => 'El comprobante '.$comprobante.' de su orden '.$orden.' ya está disponible en su portal para verlo o descargarlo.',
            'url' => route('payments.patient.portal', [], true),
        ];
    }
}
