<?php

namespace App\Domains\Catalog\Observers;

use App\Domains\Catalog\Models\ExamCategory;
use App\Domains\Notifications\Notifications\CatalogoCategoriaExamenCreadaAdminRecepcionNotification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ExamCategoryObserver
{
    /**
     * Registra en log cuando se crea una nueva categoría de examen.
     */
    public function created(ExamCategory $examCategory): void
    {
        Log::info('Catalog: nueva categoría creada.', [
            'id' => $examCategory->id,
            'name' => $examCategory->name,
            'type' => $examCategory->type,
        ]);

        $user = Auth::user();
        if ($user === null
            || ! $user->hasAnyRole(['Bioquímico', 'Tecnólogo de Imagen'])
            || ! $user->can('catalog.access')) {
            return;
        }

        $creadoPor = $user->name;
        $recipients = collect(User::role('Administrador')->get())
            ->merge(User::role('Recepcionista')->get())
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new CatalogoCategoriaExamenCreadaAdminRecepcionNotification($examCategory, $creadoPor),
        );
    }

    /**
     * Registra en log cuando una categoría es desactivada.
     */
    public function updated(ExamCategory $examCategory): void
    {
        if ($examCategory->wasChanged('is_active') && ! $examCategory->is_active) {
            Log::warning('Catalog: categoría desactivada.', [
                'id' => $examCategory->id,
                'name' => $examCategory->name,
                'type' => $examCategory->type,
            ]);
        }
    }
}
