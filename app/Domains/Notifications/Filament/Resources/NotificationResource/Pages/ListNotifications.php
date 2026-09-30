<?php

namespace App\Domains\Notifications\Filament\Resources\NotificationResource\Pages;

use App\Domains\Notifications\Filament\Resources\NotificationResource;
use Filament\Actions;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Pages\ListRecords;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('marcar_todas_leidas')
                ->label('Marcar todas como leídas')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function (): void {
                    auth()->user()?->unreadNotifications()->update(['read_at' => now()]);

                    FilamentNotification::make()
                        ->title('Todas las notificaciones marcadas como leídas')
                        ->success()
                        ->send();

                    $this->resetTable();
                }),
            Actions\Action::make('eliminar_todas')
                ->label('Eliminar todas')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Eliminar todas las notificaciones')
                ->modalDescription('Se borrarán de forma permanente todas las notificaciones asociadas a su usuario (leídas y no leídas). Esta acción no se puede deshacer.')
                ->modalSubmitActionLabel('Sí, eliminar todas')
                ->visible(fn (): bool => auth()->user()?->can('notifications.access') ?? false)
                ->action(function (): void {
                    $user = auth()->user();
                    if ($user === null) {
                        return;
                    }

                    $deleted = $user->notifications()->delete();

                    FilamentNotification::make()
                        ->title($deleted > 0 ? 'Notificaciones eliminadas' : 'Sin notificaciones que eliminar')
                        ->body($deleted > 0
                            ? "Se eliminaron {$deleted} notificación(es) de su bandeja."
                            : 'No tenía notificaciones registradas.')
                        ->success()
                        ->send();

                    $this->resetTable();
                }),
        ];
    }
}
