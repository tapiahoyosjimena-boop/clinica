<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use App\Domains\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso a administradores cuando una orden queda cancelada (auditoría).
 */
class OrdenCanceladaAdminNotification extends Notification
{
    use FilamentDatabaseChannelPayload;
    use Queueable;

    public function __construct(private readonly Order $order) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $this->order->loadMissing('cancelledBy');

        $orderNumber = $this->order->order_number ?? '—';
        $canceladoPor = $this->order->cancelledBy?->name ?? '—';
        $motivo = trim((string) ($this->order->cancellation_reason ?? ''));
        $motivoTexto = $motivo !== '' ? $motivo : '—';

        return $this->filamentDatabaseMessage([
            'title' => '🚫 Orden cancelada',
            'body' => 'La orden '.$orderNumber.' fue cancelada por '.$canceladoPor.'. '
                .'El usuario que canceló la orden dejó registrado el siguiente motivo: '.$motivoTexto.'.',
            'url' => FilamentAdminListUrls::orders(),
            'icon' => 'heroicon-o-x-circle',
        ]);
    }
}
