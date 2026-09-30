<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use App\Domains\Samples\Models\Sample;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class MuestraCreadaNotification extends Notification
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
        $this->sample->loadMissing(['order.patient', 'exam']);

        return $this->filamentDatabaseMessage([
            'title' => '🧪 Llegó la muestra esperada',
            'body' => 'Hay una nueva muestra asociada a la orden prevista: paciente '
                .($this->sample->order?->patient?->full_name ?? '—')
                .', examen «'.($this->sample->exam?->name ?? '—')
                .'», código de muestra '.($this->sample->barcode ?? '—')
                .'. Puede ejecutar el análisis según corresponda.',
            'url' => FilamentAdminListUrls::samples(),
            'icon' => 'heroicon-o-clipboard-document-check',
        ]);
    }
}
