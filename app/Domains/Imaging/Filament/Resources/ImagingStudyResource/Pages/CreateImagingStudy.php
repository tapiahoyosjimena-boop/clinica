<?php

namespace App\Domains\Imaging\Filament\Resources\ImagingStudyResource\Pages;

use App\Domains\Imaging\Filament\Resources\ImagingStudyResource;
use App\Domains\Imaging\Models\ImagingStatusHistory;
use App\Domains\Orders\Models\Order;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateImagingStudy extends CreateRecord
{
    protected static string $resource = ImagingStudyResource::class;

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
                ->body('No es posible registrar el estudio porque la orden aún no ha sido pagada.')
                ->danger()
                ->send();

            $this->halt();
        }

        if (! $order->getScheduledDateTime()) {
            Notification::make()
                ->title('Orden sin cita programada')
                ->body('La orden debe tener fecha y hora programadas para registrar la fecha del estudio automáticamente.')
                ->danger()
                ->send();

            $this->halt();
        }

        $order->load(['exams' => fn ($q) => $q->where('type', 'imagen')]);
        if ($order->exams->isEmpty()) {
            Notification::make()
                ->title('Orden sin examen de imagen')
                ->body('La orden no tiene un examen de imagen asociado. Revise la orden en el módulo de órdenes.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $order = Order::with(['exams' => fn ($q) => $q->where('type', 'imagen')])->find($data['order_id'] ?? null);
        $exam = $order?->exams->first();
        if ($exam) {
            $data['exam_id'] = $exam->id;
        }

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
        ImagingStatusHistory::create([
            'imaging_study_id' => $this->record->id,
            'old_status' => null,
            'new_status' => $this->record->status,
            'changed_by' => auth()->id(),
            'notes' => 'Estudio de imagen creado.',
        ]);
    }
}
