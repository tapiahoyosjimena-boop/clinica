<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use App\Domains\Samples\Models\Sample;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso a administradores y recepcionista de la orden cuando una muestra queda rechazada.
 */
class MuestraRechazadaAdminRecepcionNotification extends Notification
{
    use FilamentDatabaseChannelPayload;
    use Queueable;

    public function __construct(private readonly Sample $sample) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $this->sample->loadMissing(['rejectedBy']);

        $barcode = $this->sample->barcode ?? '—';
        $rechazadoPor = $this->sample->rejectedBy?->name ?? '—';
        $motivo = trim((string) ($this->sample->motivo_rechazo ?? ''));
        $motivoTexto = $motivo !== '' ? $motivo : '—';

        return $this->filamentDatabaseMessage([
            'title' => '🧪 Muestra rechazada',
            'body' => 'La muestra con código '.$barcode.' fue rechazada por '.$rechazadoPor.'. '
                .'El usuario que rechazó la muestra dejó registrado el siguiente motivo: '.$motivoTexto.'.',
            'url' => FilamentAdminListUrls::samples(),
            'icon' => 'heroicon-o-x-circle',
        ]);
    }
}
