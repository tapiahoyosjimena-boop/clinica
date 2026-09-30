<?php

namespace App\Domains\Catalog\Observers;

use App\Domains\Catalog\Models\Exam;
use App\Domains\Notifications\Notifications\CatalogoExamenCreadoAdminNotification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class ExamObserver
{
    public function created(Exam $exam): void
    {
        $user = Auth::user();
        if ($user === null
            || ! $user->hasAnyRole(['Bioquímico', 'Tecnólogo de Imagen'])
            || ! $user->can('catalog.access')) {
            return;
        }

        $creadoPor = $user->name;
        $recipients = User::role('Administrador')->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new CatalogoExamenCreadoAdminNotification($exam, $creadoPor),
        );
    }
}
