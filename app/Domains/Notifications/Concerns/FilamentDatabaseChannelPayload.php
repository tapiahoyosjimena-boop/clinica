<?php

namespace App\Domains\Notifications\Concerns;

use Filament\Notifications\Livewire\DatabaseNotifications;
use Illuminate\Notifications\Messages\DatabaseMessage;

/**
 * Filament solo muestra en la campana las filas con data->format = "filament"
 * ({@see DatabaseNotifications::getNotificationsQuery}).
 */
trait FilamentDatabaseChannelPayload
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function filamentDatabaseMessage(array $data): DatabaseMessage
    {
        return new DatabaseMessage(array_merge([
            'format' => 'filament',
            'duration' => 'persistent',
        ], $data));
    }
}
