<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Imaging\Models\ImagingEquipment;
use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso a administradores y recepcionistas cuando se registra un equipo de imagen.
 */
class EquipoImagenCreadoAdminRecepcionNotification extends Notification
{
    use FilamentDatabaseChannelPayload;
    use Queueable;

    public function __construct(
        private readonly ImagingEquipment $equipment,
        private readonly string $creadoPorNombre,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $nombre = $this->equipment->name;
        $tipo = $this->equipment->type_label;
        $estadoLabel = $this->equipment->status_label;
        $disponibleTexto = match ($this->equipment->status) {
            'disponible' => 'Sí, está disponible para utilizarse.',
            'mantenimiento' => 'No está disponible para utilizarse en este momento (en mantenimiento).',
            'fuera_de_servicio' => 'No está disponible para utilizarse en este momento (fuera de servicio).',
            default => 'Consulte el estado en el listado de equipos.',
        };

        return $this->filamentDatabaseMessage([
            'title' => '🖥️ Nuevo equipo de imagen',
            'body' => 'Se registró el equipo «'.$nombre.'» ('.$tipo.'). Usuario que lo creó: '.$this->creadoPorNombre.'. '
                .'Estado registrado: '.$estadoLabel.'. '.$disponibleTexto,
            'url' => FilamentAdminListUrls::imagingEquipment(),
            'icon' => 'heroicon-o-computer-desktop',
        ]);
    }
}
