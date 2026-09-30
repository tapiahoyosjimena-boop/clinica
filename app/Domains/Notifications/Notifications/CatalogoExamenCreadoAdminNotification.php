<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Catalog\Models\Exam;
use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso cuando personal clínico con catálogo crea un examen: solo administradores.
 */
class CatalogoExamenCreadoAdminNotification extends Notification
{
    use FilamentDatabaseChannelPayload;
    use Queueable;

    public function __construct(
        private readonly Exam $exam,
        private readonly string $creadoPorNombre,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $this->exam->loadMissing('category');
        $nombre = $this->exam->name;
        $categoria = $this->exam->category?->name ?? 'Sin categoría';
        $tipo = match ($this->exam->type) {
            'laboratorio' => 'Laboratorio',
            'imagen' => 'Imagen',
            default => ucfirst((string) $this->exam->type),
        };

        return $this->filamentDatabaseMessage([
            'title' => '🧪 Nuevo examen en catálogo',
            'body' => 'Se creó el examen «'.$nombre.'» (tipo: '.$tipo.', categoría: «'.$categoria.'»). Usuario que lo registró: '.$this->creadoPorNombre.'.',
            'url' => FilamentAdminListUrls::exams(),
            'icon' => 'heroicon-o-clipboard-document-list',
        ]);
    }
}
