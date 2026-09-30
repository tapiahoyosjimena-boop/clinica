<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use App\Domains\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class OrdenNuevaMedicoDerivanteNotification extends Notification
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
            'title' => '🩺 Nueva orden con su participación',
            'body' => 'Se registró la orden '.$this->order->order_number.' ('.$tipo.') del paciente '.$patient.'. Figura como médico derivante: podrá revisar el proceso y los resultados cuando estén disponibles.',
            'url' => FilamentAdminListUrls::orders(),
            'icon' => 'heroicon-o-information-circle',
        ]);
    }
}
