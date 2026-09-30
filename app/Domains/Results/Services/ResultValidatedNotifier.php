<?php

namespace App\Domains\Results\Services;

use App\Domains\Notifications\Notifications\ResultadoCriticoNotification;
use App\Domains\Notifications\Notifications\ResultadosListosMedicoNotification;
use App\Domains\Results\Models\Result;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/**
 * Avisos al administrador / médico derivante tras confirmar un resultado con éxito
 * (PDF generado y publicación al portal / intento de correo ya ejecutados).
 *
 * No debe ejecutarse en cada guardado del borrador: evita duplicados y alinea el aviso
 * con la acción explícita «Confirmar y enviar».
 */
class ResultValidatedNotifier
{
    public function notifyAfterPublish(Result $result): void
    {
        if ($result->validated_at === null) {
            return;
        }

        $result->loadMissing(['order', 'order.patient', 'exam']);
        $doctorId = $result->order?->doctor_id;
        $doctor = $doctorId ? User::find($doctorId) : null;

        if ($result->is_critical) {
            $recipients = collect(User::role('Administrador')->get());
            if ($doctor) {
                $recipients->push($doctor);
            }
            $recipients = $recipients->filter()->unique('id')->values();
            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new ResultadoCriticoNotification($result));
            }

            return;
        }

        if ($doctor) {
            $doctor->notify(new ResultadosListosMedicoNotification($result));
        }
    }
}
