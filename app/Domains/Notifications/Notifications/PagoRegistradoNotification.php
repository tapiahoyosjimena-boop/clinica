<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use App\Domains\Payments\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class PagoRegistradoNotification extends Notification
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
        // El destinatario es el responsable clínico (p. ej. tecnólogo/bioquímico): debe ir al listado de órdenes,
        // no a comprobantes (payments.access), que suele estar restringido a caja/administración.
        $listUrl = $this->invoice->order_id !== null
            ? FilamentAdminListUrls::orders()
            : FilamentAdminListUrls::invoices();

        return $this->filamentDatabaseMessage([
            'title' => '📋 Nueva Orden Asignada',
            'body' => 'La orden '
                .($this->invoice->order?->order_number ?? '—')
                .' del paciente '
                .($this->invoice->order?->patient?->full_name ?? '—')
                .' queda a su cargo como responsable.',
            'url' => $listUrl,
            'icon' => 'heroicon-o-clipboard-document-list',
        ]);
    }
}
