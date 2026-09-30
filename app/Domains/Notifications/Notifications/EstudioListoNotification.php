<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Imaging\Models\ImagingStatusHistory;
use App\Domains\Imaging\Models\ImagingStudy;
use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class EstudioListoNotification extends Notification
{
    use FilamentDatabaseChannelPayload;
    use Queueable;

    public function __construct(
        private readonly ImagingStudy $study,
        private readonly ?string $completadoPorNombre = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $this->study->loadMissing(['order.patient', 'order.receptionist', 'exam']);

        $paciente = $this->study->order?->patient?->full_name ?? '—';
        $examen = $this->study->exam?->name ?? '—';
        $codigo = $this->study->study_code ?? '—';
        $completadoPor = $this->completadoPorNombre ?? '—';
        $registrador = ImagingStatusHistory::query()
            ->where('imaging_study_id', $this->study->id)
            ->orderBy('id')
            ->with('changedBy')
            ->first()?->changedBy?->name
            ?? $this->study->order?->receptionist?->name
            ?? '—';

        return $this->filamentDatabaseMessage([
            'title' => '🩻 Estudio de imagen completado',
            'body' => 'El estudio «'.$examen.'» (código '.$codigo.') del paciente '.$paciente.', fue completado con éxito, el personal a cargo debe cargar los resultados al sistema.'."\n"
                .'Estudio de imagen registrado originalmente por: '.$registrador.'.'."\n"
                .'Usuario asociado al cambio a completado: '.$completadoPor.'.',
            'url' => FilamentAdminListUrls::imagingStudies(),
            'icon' => 'heroicon-o-check-circle',
        ]);
    }
}
