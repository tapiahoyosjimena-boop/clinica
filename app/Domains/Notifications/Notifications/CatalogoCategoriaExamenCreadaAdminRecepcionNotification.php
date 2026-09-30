<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Catalog\Models\ExamCategory;
use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso cuando personal clínico con catálogo crea una categoría (catálogo de exámenes): administradores y recepcionistas.
 */
class CatalogoCategoriaExamenCreadaAdminRecepcionNotification extends Notification
{
    use FilamentDatabaseChannelPayload;
    use Queueable;

    public function __construct(
        private readonly ExamCategory $category,
        private readonly string $creadoPorNombre,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $nombre = $this->category->name;
        $tipo = match ($this->category->type) {
            'laboratorio' => 'Laboratorio',
            'imagen' => 'Imagen',
            default => ucfirst((string) $this->category->type),
        };

        return $this->filamentDatabaseMessage([
            'title' => '📚 Nuevo catálogo de exámenes',
            'body' => 'Se creó la categoría de catálogo «'.$nombre.'» (tipo: '.$tipo.'). Usuario que la registró: '.$this->creadoPorNombre.'.',
            'url' => FilamentAdminListUrls::examCategories(),
            'icon' => 'heroicon-o-book-open',
        ]);
    }
}
