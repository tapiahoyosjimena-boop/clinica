<?php

namespace App\Domains\Imaging\Observers;

use App\Domains\Imaging\Models\ImagingEquipment;
use App\Domains\Notifications\Notifications\EquipoImagenCreadoAdminRecepcionNotification;
use App\Domains\Notifications\Notifications\EquipoImagenEstadoCambiadoAdminRecepcionNotification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class ImagingEquipmentObserver
{
    /** @var array<int, string> */
    private array $previousStatusByEquipmentObjectId = [];

    public function created(ImagingEquipment $equipment): void
    {
        $creadoPor = Auth::user()?->name ?? 'Sistema';

        $recipients = collect(User::role('Administrador')->get())
            ->merge(User::role('Recepcionista')->get())
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new EquipoImagenCreadoAdminRecepcionNotification($equipment, $creadoPor),
        );
    }

    public function updating(ImagingEquipment $equipment): void
    {
        if (! $equipment->isDirty('status')) {
            return;
        }

        $this->previousStatusByEquipmentObjectId[spl_object_id($equipment)] = (string) $equipment->getOriginal('status');
    }

    public function updated(ImagingEquipment $equipment): void
    {
        if (! $equipment->wasChanged('status')) {
            return;
        }

        $oid = spl_object_id($equipment);
        $estadoAnterior = $this->previousStatusByEquipmentObjectId[$oid] ?? null;
        unset($this->previousStatusByEquipmentObjectId[$oid]);

        $cambiadoPor = Auth::user()?->name ?? 'Sistema';

        $recipients = collect(User::role('Administrador')->get())
            ->merge(User::role('Recepcionista')->get())
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new EquipoImagenEstadoCambiadoAdminRecepcionNotification($equipment, $cambiadoPor, $estadoAnterior),
        );
    }
}
