<?php

namespace App\Domains\Imaging\Observers;

use App\Domains\Imaging\Models\ImagingStudy;
use App\Domains\Notifications\Notifications\EstudioImagenAltaAdminNotification;
use App\Domains\Notifications\Notifications\EstudioImagenCreadoNotification;
use App\Domains\Notifications\Notifications\EstudioListoNotification;
use App\Domains\Results\Models\Result;
use App\Domains\Results\Services\ResultPendingUploadNotifier;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class ImagingStudyObserver
{
    /**
     * Si el estudio se crea ya en «paciente presente», avisar al tecnólogo.
     * En el flujo habitual el aviso se envía al pasar de programado → paciente_presente ({@see updated}).
     */
    public function created(ImagingStudy $study): void
    {
        $admins = User::role('Administrador')->get();
        if ($admins->isNotEmpty()) {
            $study->loadMissing(['order.patient', 'exam']);
            Notification::send($admins, new EstudioImagenAltaAdminNotification($study, Auth::user()?->name));
        }

        if ($study->status === 'paciente_presente') {
            $this->notifyTecnologoPacientePresenteParaEstudio($study);
        }
    }

    /**
     * Cuando un estudio cambia de estado, verifica si la orden asociada
     * debe actualizarse automáticamente.
     *
     * Regla: si ya no quedan estudios en estados activos
     * (programado/paciente_presente/en_proceso), y al menos uno fue
     * completado exitosamente → la orden pasa a "completada".
     */
    public function updated(ImagingStudy $study): void
    {
        if ($study->wasChanged('status') && $study->status === 'paciente_presente') {
            $this->notifyTecnologoPacientePresenteParaEstudio($study);
        }

        if (! $study->wasChanged('status')) {
            return;
        }

        $order = $study->order;

        if (! $order) {
            return;
        }

        // Ignorar si la orden ya está en estado terminal
        if (in_array($order->status, ['cancelada', 'completada'])) {
            return;
        }

        $totalStudies = ImagingStudy::where('order_id', $order->id)->count();

        if ($totalStudies === 0) {
            return;
        }

        // Estudios que aún están en proceso activo (no terminales)
        $pendingStudies = ImagingStudy::where('order_id', $order->id)
            ->whereIn('status', ['programado', 'paciente_presente', 'en_proceso'])
            ->count();

        // Si todos los estudios están en estado terminal (completado o cancelado)
        // y al menos uno fue completado exitosamente → orden completada
        if ($pendingStudies === 0) {
            $completedStudies = ImagingStudy::where('order_id', $order->id)
                ->where('status', 'completado')
                ->count();

            if ($completedStudies > 0) {
                $order->update(['status' => 'completada']);
            }
        }

        // Cuando el estudio pasa a "completado", crear una entrada vacía de Result
        // si todavía no existe resultado para esta orden, para que el tecnólogo
        // lo complete en el módulo de Resultados.
        if ($study->status === 'completado') {
            $exists = Result::where('order_id', $study->order_id)
                ->where('exam_id', $study->exam_id)
                ->exists();

            if (! $exists) {
                Result::create([
                    'sample_id' => null,
                    'order_id' => $study->order_id,
                    'exam_id' => $study->exam_id,
                    'bioquimico_id' => $study->responsible_user_id,
                    'is_critical' => false,
                ]);
            }

            // Notificar solo a administradores (auditoría)
            $admins = User::role('Administrador')->get();
            if ($admins->isNotEmpty()) {
                $study->loadMissing(['order.patient', 'exam']);
                Notification::send($admins, new EstudioListoNotification($study, Auth::user()?->name));
            }

            $result = Result::query()
                ->where('order_id', $study->order_id)
                ->where('exam_id', $study->exam_id)
                ->first();

            if ($result) {
                app(ResultPendingUploadNotifier::class)->notifyForImagingStudy($study, $result);
            }
        }
    }

    private function notifyTecnologoPacientePresenteParaEstudio(ImagingStudy $study): void
    {
        $order = $study->order;
        $targetId = $study->responsible_user_id ?? $order?->responsible_user_id;
        if (! $targetId) {
            return;
        }

        $user = User::find((int) $targetId);
        if (! $user) {
            return;
        }

        $study->loadMissing(['order.patient', 'exam']);
        $user->notify(new EstudioImagenCreadoNotification($study));
    }
}
