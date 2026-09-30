<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Imaging\Models\ImagingEquipment;
use App\Domains\Notifications\Concerns\FilamentDatabaseChannelPayload;
use App\Domains\Notifications\Support\FilamentAdminListUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso a administradores y recepcionistas cuando cambia el estado de un equipo de imagen.
 */
class EquipoImagenEstadoCambiadoAdminRecepcionNotification extends Notification
{
    use FilamentDatabaseChannelPayload;
    use Queueable;

    public function __construct(
        private readonly ImagingEquipment $equipment,
        private readonly string $cambiadoPorNombre,
        private readonly ?string $estadoAnteriorCodigo,
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
        $estadoActualLabel = $this->equipment->status_label;
        $disponibleTexto = match ($this->equipment->status) {
            'disponible' => 'El equipo queda disponible para utilizarse.',
            'mantenimiento' => 'El equipo no está disponible para utilizarse en este momento (en mantenimiento).',
            'fuera_de_servicio' => 'El equipo no está disponible para utilizarse en este momento (fuera de servicio).',
            default => 'Consulte el estado en el listado de equipos.',
        };

        $cambioLinea = $this->estadoAnteriorCodigo !== null
            ? 'Cambio de estado: '.ImagingEquipment::statusLabel($this->estadoAnteriorCodigo).' → '.$estadoActualLabel.'. '
            : 'Estado actual del equipo: '.$estadoActualLabel.'. ';

        return $this->filamentDatabaseMessage([
            'title' => '🖥️ Estado de equipo de imagen actualizado',
            'body' => 'El equipo «'.$nombre.'» ('.$tipo.'). Usuario que modificó el estado: '.$this->cambiadoPorNombre.'. '
                .$cambioLinea.$disponibleTexto,
            'url' => FilamentAdminListUrls::imagingEquipment(),
            'icon' => 'heroicon-o-computer-desktop',
        ]);
    }
}
