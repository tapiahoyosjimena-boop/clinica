<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use App\Domains\Results\Models\Result;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

/**
 * Avisa al bioquímico o tecnólogo responsable que puede registrar el resultado en el panel.
 */
class ResultadoPendienteCargaResponsableNotification extends Notification
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
        $this->result->loadMissing(['order.patient', 'exam']);

        $paciente = $this->result->order?->patient?->full_name ?? '—';
        $examen = $this->result->exam?->name ?? '—';
        $orden = $this->result->order?->order_number ?? '—';
        $isImagen = ($this->result->order?->type ?? '') === 'imagen';

        if ($isImagen) {
            $title = '📋 Registre el resultado de imagen';
            $body = 'El estudio «'.$examen.'» de la orden '.$orden.' (paciente '.$paciente.') fue completado. '
                .'Ingrese al módulo de Resultados, elabore el informe correspondiente y confírmelo en el sistema.';
            $icon = 'heroicon-o-camera';
        } else {
            $title = '📋 Registre el resultado de laboratorio';
            $body = 'La muestra del examen «'.$examen.'» de la orden '.$orden.' (paciente '.$paciente.') fue procesada. '
                .'Ingrese al módulo de Resultados, complete el resultado correspondiente y confírmelo en el sistema.';
            $icon = 'heroicon-o-beaker';
        }

        return $this->filamentDatabaseMessage([
            'title' => $title,
            'body' => $body,
            'url' => FilamentAdminListUrls::resultEdit($this->result),
            'icon' => $icon,
        ]);
    }
}
