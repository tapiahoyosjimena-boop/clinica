<?php

namespace App\Domains\Samples\Filament\Resources\SampleResource\Pages;

use App\Domains\Orders\Models\Order;
use App\Domains\Samples\Filament\Resources\SampleResource;
use App\Domains\Samples\Models\SampleStatusHistory;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSample extends CreateRecord
{
    protected static string $resource = SampleResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        $order = Order::with('invoice')->find($data['order_id'] ?? null);

        if (! $order || $order->invoice?->status !== 'pagada') {
            Notification::make()
                ->title('Orden sin pago confirmado')
                ->body('No es posible registrar la muestra porque la orden aún no ha sido pagada.')
                ->danger()
                ->send();

            $this->halt();
        }

        if (! $order->getScheduledDateTime()) {
            Notification::make()
                ->title('Orden sin cita programada')
                ->body('La orden debe tener fecha y hora programadas para registrar la fecha de recolección automáticamente.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['collected_by'] = auth()->id();

        $order = Order::query()->find($data['order_id'] ?? null);
        $scheduled = $order?->getScheduledDateTime();
        if ($scheduled) {
            $data['collected_at'] = $scheduled;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function afterCreate(): void
    {
        // Registrar el estado inicial en el historial
        SampleStatusHistory::create([
            'sample_id' => $this->record->id,
            'old_status' => null,
            'new_status' => $this->record->status,
            'changed_by' => auth()->id(),
            'notes' => 'Muestra creada.',
        ]);
    }
}
