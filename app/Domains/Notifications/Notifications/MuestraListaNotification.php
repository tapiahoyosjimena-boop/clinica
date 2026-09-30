<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use App\Domains\Samples\Models\Sample;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class MuestraListaNotification extends Notification
{
    use FilamentDatabaseChannelPayload;
    use Queueable;

    public function __construct(
        private readonly Sample $sample,
        private readonly ?string $procesadaPorNombre = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $this->sample->loadMissing(['order.patient', 'exam', 'collectedBy']);

        $paciente = $this->sample->order?->patient?->full_name ?? '—';
        $examen = $this->sample->exam?->name ?? '—';
        $barcode = $this->sample->barcode ?? '—';
        $registrador = $this->sample->collectedBy?->name ?? '—';
        $proceso = $this->procesadaPorNombre ?? '—';

        return $this->filamentDatabaseMessage([
            'title' => '🔬 Muestra analizada con éxito',
            'body' => 'La muestra con código '.$barcode.' del paciente '.$paciente.' (examen «'.$examen.'») analizada con éxito, el personal a cargo debe cargar los resultados al sistema.'."\n"
                .'Muestra registrada originalmente por: '.$registrador.'.'."\n"
                .'Usuario asociado al cambio a procesada: '.$proceso.'.',
            'url' => FilamentAdminListUrls::samples(),
            'icon' => 'heroicon-o-check-circle',
        ]);
    }
}
