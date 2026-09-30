<?php

namespace App\Domains\Samples\Observers;

use App\Domains\Notifications\Notifications\MuestraAltaAdminNotification;
use App\Domains\Notifications\Notifications\MuestraCreadaNotification;
use App\Domains\Notifications\Notifications\MuestraListaNotification;
use App\Domains\Notifications\Notifications\MuestraRechazadaAdminRecepcionNotification;
use App\Domains\Results\Models\Result;
use App\Domains\Results\Services\ResultPendingUploadNotifier;
use App\Domains\Samples\Models\Sample;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class SampleObserver
{
    /**
     * Avisar al responsable/bioquímico de que hay una muestra nueva que atender.
     */
    public function created(Sample $sample): void
    {
        $admins = User::role('Administrador')->get();
        if ($admins->isNotEmpty()) {
            $sample->loadMissing(['order.patient', 'exam', 'collectedBy']);
            Notification::send($admins, new MuestraAltaAdminNotification($sample, Auth::user()?->name));
        }

        $order = $sample->order;
        if (! $order) {
            return;
        }

        $targetId = $sample->bioquimico_asignado_id ?? $order->responsible_user_id;
        if (! $targetId) {
            return;
        }

        $user = User::find((int) $targetId);
        if (! $user) {
            return;
        }

        $sample->loadMissing(['order.patient', 'exam']);
        $user->notify(new MuestraCreadaNotification($sample));
    }

    /**
     * Cuando una muestra cambia de estado, verifica si la orden asociada
     * debe actualizarse automáticamente.
     *
     * Regla: si ya no quedan muestras en estados pendientes (recibida/en_analisis),
     * la orden pasa a "completada" automáticamente.
     */
    public function updated(Sample $sample): void
    {
        if (! $sample->wasChanged('status')) {
            return;
        }

        if ($sample->status === 'rechazada') {
            $this->notifySampleRejected($sample);
        }

        $order = $sample->order;

        if (! $order) {
            return;
        }

        // Ignorar si la orden ya está cancelada o completada
        if (in_array($order->status, ['cancelada', 'completada'])) {
            return;
        }

        $totalSamples = $order->samples()->count();

        if ($totalSamples === 0) {
            return;
        }

        // Muestras que aún están en proceso activo (no terminales)
        $pendingSamples = $order->samples()
            ->whereIn('status', ['recibida', 'en_analisis'])
            ->count();

        // Si todas las muestras están en estado terminal (procesada o rechazada)
        // y al menos una fue procesada exitosamente → orden completada
        if ($pendingSamples === 0) {
            $processedSamples = $order->samples()
                ->where('status', 'procesada')
                ->count();

            if ($processedSamples > 0) {
                $order->update(['status' => 'completada']);
            }
        }

        // Cuando la muestra pasa a "procesada", crear una entrada vacía de Result
        // si todavía no existe resultado para esta muestra, para que el bioquímico
        // lo complete en el módulo de Resultados.
        if ($sample->wasChanged('status') && $sample->status === 'procesada') {
            $exists = Result::where('order_id', $sample->order_id)
                ->where('exam_id', $sample->exam_id)
                ->exists();

            if (! $exists) {
                Result::create([
                    'sample_id' => $sample->id,
                    'order_id' => $sample->order_id,
                    'exam_id' => $sample->exam_id,
                    'bioquimico_id' => $sample->bioquimico_asignado_id,
                    'is_critical' => false,
                ]);
            }

            // Notificar solo a administradores (auditoría)
            $admins = User::role('Administrador')->get();
            if ($admins->isNotEmpty()) {
                $sample->loadMissing(['order.patient', 'exam', 'collectedBy']);
                Notification::send($admins, new MuestraListaNotification($sample, Auth::user()?->name));
            }

            $result = Result::query()
                ->where('order_id', $sample->order_id)
                ->where('exam_id', $sample->exam_id)
                ->first();

            if ($result) {
                app(ResultPendingUploadNotifier::class)->notifyForSample($sample, $result);
            }
        }
    }

    /**
     * Administradores + recepcionista de la orden (si existe). Debe ejecutarse antes del return
     * que omite órdenes canceladas, para cubrir rechazos por cancelación de orden.
     */
    private function notifySampleRejected(Sample $sample): void
    {
        $sample->loadMissing(['order.receptionist', 'rejectedBy']);

        $recipients = collect(User::role('Administrador')->get());
        $receptionist = $sample->order?->receptionist;
        if ($receptionist) {
            $recipients->push($receptionist);
        }

        $unique = $recipients->unique('id')->values();
        if ($unique->isEmpty()) {
            return;
        }

        Notification::send($unique, new MuestraRechazadaAdminRecepcionNotification($sample));
    }
}
