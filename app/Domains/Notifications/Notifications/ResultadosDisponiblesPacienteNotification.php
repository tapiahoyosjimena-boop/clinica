<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Results\Models\Result;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Aviso en el portal del paciente (canal database) cuando un resultado queda publicado con PDF.
 * No usa formato Filament; el portal lee title/body/url desde data.
 */
class ResultadosDisponiblesPacienteNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Result $result) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->result->loadMissing('exam');

        $exam = $this->result->exam?->name ?? 'su examen';

        return [
            'format' => 'patient_portal',
            'title' => '📄 Resultados disponibles',
            'body' => 'Ya puede ver y descargar el PDF de «'.$exam.'» en su portal.',
            'url' => route('results.patient.portal', [], true),
        ];
    }
}
