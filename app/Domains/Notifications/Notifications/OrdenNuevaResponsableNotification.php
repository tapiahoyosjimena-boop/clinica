<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use App\Domains\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class OrdenNuevaResponsableNotification extends Notification
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
        $this->order->loadMissing(['patient']);
        $patient = $this->order->patient?->full_name ?? '—';
        $tipo = $this->order->type === 'imagen' ? 'Imagen' : 'Laboratorio';

        return $this->filamentDatabaseMessage([
            'title' => '📋 Nueva orden asignada a usted',
            'body' => 'Se creó la orden '.$this->order->order_number.' ('.$tipo.') para el paciente '.$patient.'. Quedó como responsable para realizar el trabajo.',
            'url' => FilamentAdminListUrls::orders(),
            'icon' => 'heroicon-o-information-circle',
        ]);
    }
}
