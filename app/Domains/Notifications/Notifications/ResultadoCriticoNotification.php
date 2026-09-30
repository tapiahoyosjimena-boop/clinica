<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use App\Domains\Results\Models\Result;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class ResultadoCriticoNotification extends Notification
{
    use FilamentDatabaseChannelPayload;
    use Queueable;

    public function __construct(private readonly Result $result) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $orderNumber = $this->result->order?->order_number;

        return $this->filamentDatabaseMessage([
            'title' => '⚠ Resultado crítico registrado',
            'body' => 'Atención: el examen «'
                .($this->result->exam?->name ?? '—')
                .'» del paciente '
                .($this->result->order?->patient?->full_name ?? '—')
                .($orderNumber ? ' (orden '.$orderNumber.')' : '')
                .' quedó registrado como resultado crítico. Revise el detalle y las acciones clínicas indicadas.',
            'url' => FilamentAdminListUrls::results(),
            'icon' => 'heroicon-o-exclamation-triangle',
        ]);
    }
}
