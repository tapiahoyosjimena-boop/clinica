<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Imaging\Models\ImagingStudy;
use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class EstudioImagenCreadoNotification extends Notification
{
    use FilamentDatabaseChannelPayload;
    use Queueable;

    public function __construct(private readonly ImagingStudy $study) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $this->study->loadMissing(['order.patient', 'exam']);

        return $this->filamentDatabaseMessage([
            'title' => '🩻 Llegó el paciente para el examen de imagen',
            'body' => 'El paciente '
                .($this->study->order?->patient?->full_name ?? '—')
                .' está presente para el estudio «'.($this->study->exam?->name ?? '—')
                .'» (código '.($this->study->study_code ?? '—')
                .'). Puede iniciar el examen de imagen según el protocolo.',
            'url' => FilamentAdminListUrls::imagingStudies(),
            'icon' => 'heroicon-o-user',
        ]);
    }
}
