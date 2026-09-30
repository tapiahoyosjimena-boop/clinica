<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use App\Domains\Samples\Models\Sample;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

/**
 * Alta de muestra: aviso a administradores con contexto de auditoría.
 */
class MuestraAltaAdminNotification extends Notification
{
    use FilamentDatabaseChannelPayload;
    use Queueable;

    public function __construct(
        private readonly Sample $sample,
        private readonly ?string $registradoPorNombre = null,
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
        $quien = $this->registradoPorNombre
            ?? $this->sample->collectedBy?->name
            ?? '—';

        return $this->filamentDatabaseMessage([
            'title' => '🧪 Nueva muestra (auditoría)',
            'body' => 'Se registró una muestra: código '.$barcode.', paciente '.$paciente.', examen «'.$examen.'». '
                .'Usuario que registró la muestra: '.$quien.'.',
            'url' => FilamentAdminListUrls::samples(),
            'icon' => 'heroicon-o-clipboard-document-check',
        ]);
    }
}
