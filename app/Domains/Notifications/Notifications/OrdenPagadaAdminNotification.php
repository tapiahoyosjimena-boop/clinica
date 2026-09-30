<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use App\Domains\Payments\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso a administradores cuando una factura de orden queda pagada (auditoría).
 */
class OrdenPagadaAdminNotification extends Notification
{
    use FilamentDatabaseChannelPayload;
    use Queueable;

    public function __construct(private readonly Invoice $invoice) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $this->invoice->loadMissing(['order.patient', 'order.receptionist']);

        $order = $this->invoice->order;
        $orderNumber = $order?->order_number ?? '—';
        $patient = $order?->patient?->full_name ?? '—';
        $quienRegistro = $order?->receptionist?->name ?? '—';

        return $this->filamentDatabaseMessage([
            'title' => '💳 Pago de orden completado',
            'body' => 'El pago de la orden '.$orderNumber.' del paciente '.$patient.' se realizó con éxito. '
                .'Quien registró la orden en el sistema: '.$quienRegistro.'.',
            'url' => $order ? FilamentAdminListUrls::orders() : FilamentAdminListUrls::invoices(),
            'icon' => 'heroicon-o-banknotes',
        ]);
    }
}
