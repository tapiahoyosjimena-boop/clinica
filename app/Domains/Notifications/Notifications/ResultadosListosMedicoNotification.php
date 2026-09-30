<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use App\Domains\Results\Models\Result;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class ResultadosListosMedicoNotification extends Notification
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
        $patient = $this->result->order?->patient?->full_name ?? '—';
        $exam = $this->result->exam?->name ?? '—';

        return $this->filamentDatabaseMessage([
            'title' => '📄 Resultados listos de su paciente',
            'body' => 'El resultado validado del examen «'.$exam.'» corresponde al paciente '.$patient.'. Ya puede revisarlo o descargarlo desde el panel cuando lo necesite.',
            'url' => FilamentAdminListUrls::results(),
            'icon' => 'heroicon-o-check-circle',
        ]);
    }
}
