<?php

namespace App\Domains\Imaging\Filament\Resources\ImagingStudyResource\Pages;

use App\Domains\Imaging\Filament\Resources\ImagingStudyResource;
use App\Domains\Imaging\Models\ImagingStatusHistory;
use App\Domains\Orders\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditImagingStudy extends EditRecord
{
    protected static string $resource = ImagingStudyResource::class;

    protected ?string $previousStatus = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $order = $this->record->relationLoaded('order')
            ? $this->record->order
            : Order::query()->find($data['order_id'] ?? $this->record->order_id);

        $scheduled = $order?->getScheduledDateTime();
        if ($scheduled) {
            $data['collected_at'] = $scheduled;
        }

        return $data;
    }

    protected function beforeSave(): void
    {
        $this->previousStatus = $this->record->status;
    }

    protected function afterSave(): void
    {
        $newStatus = $this->record->status;

        if ($this->previousStatus !== $newStatus) {
            ImagingStatusHistory::create([
                'imaging_study_id' => $this->record->id,
                'old_status' => $this->previousStatus,
                'new_status' => $newStatus,
                'changed_by' => auth()->id(),
                'notes' => 'Estado actualizado desde formulario de edición.',
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => ImagingStudyResource::canDelete($this->record)),
        ];
    }
}
