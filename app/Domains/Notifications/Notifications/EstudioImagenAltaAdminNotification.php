<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Imaging\Models\ImagingStudy;
use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

/**
 * Alta de estudio de imagen: aviso a administradores.
 */
class EstudioImagenAltaAdminNotification extends Notification
{
    use FilamentDatabaseChannelPayload;
    use Queueable;

    public function __construct(
        private readonly ImagingStudy $study,
        private readonly ?string $registradoPorNombre = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $this->study->loadMissing(['order.patient', 'exam']);

        $paciente = $this->study->order?->patient?->full_name ?? '—';
        $examen = $this->study->exam?->name ?? '—';
        $codigo = $this->study->study_code ?? '—';
        $quien = $this->registradoPorNombre ?? '—';

        return $this->filamentDatabaseMessage([
            'title' => '🩻 Nuevo estudio de imagen (auditoría)',
            'body' => 'Se registró un estudio de imagen: código '.$codigo.', paciente '.$paciente.', examen «'.$examen.'». '
                .'Usuario que lo registró: '.$quien.'.',
            'url' => FilamentAdminListUrls::imagingStudies(),
            'icon' => 'heroicon-o-photo',
        ]);
    }
}
