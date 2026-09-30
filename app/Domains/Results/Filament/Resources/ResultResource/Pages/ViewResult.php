<?php

namespace App\Domains\Results\Filament\Resources\ResultResource\Pages;

use App\Domains\Results\Filament\Resources\ResultResource;
use App\Domains\Results\Services\ResultDeliveryService;
use App\Domains\Results\Services\ResultPdfService;
use App\Domains\Results\Services\ResultValidatedNotifier;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ViewResult extends ViewRecord
{
    protected static string $resource = ResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => static::getResource()::canEdit($this->record)),

            Actions\Action::make('descargar_pdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (): bool => $this->record->validated_at !== null
                    && filled($this->record->pdf_path)
                    && Gate::allows('view', $this->record))
                ->action(function () {
                    if (! $this->record->pdf_path || ! Storage::disk('public')->exists($this->record->pdf_path)) {
                        Notification::make()
                            ->title('PDF no disponible')
                            ->body('No se encontró el PDF del resultado. Vuelva a generarlo o contacte al administrador.')
                            ->warning()
                            ->send();

                        return null;
                    }

                    return Storage::disk('public')->download(
                        $this->record->pdf_path,
                        'resultado-'.$this->record->id.'.pdf'
                    );
                }),

            Actions\Action::make('confirmar_resultado')
                ->label('Confirmar y enviar')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirmar resultado')
                ->modalDescription('Se validará el resultado, se generará el PDF y se publicará en el portal del paciente. Si el paciente tiene correo registrado, se enviará una copia por email. ¿Desea continuar?')
                ->visible(fn () => $this->record->validated_at === null
                    && $this->record->hasCompleteData()
                    && Gate::allows('update', $this->record))
                ->before(function (Actions\Action $action): void {
                    $this->record->loadMissing('details');

                    if ($this->record->isImagingType()) {
                        $text = $this->record->details->firstWhere('parameter_name', 'Informe')?->value;
                        if (! filled($text)) {
                            Notification::make()
                                ->title('No se puede confirmar el resultado')
                                ->body('Debe ingresar el informe del estudio antes de confirmar. Use la opción "Editar".')
                                ->warning()
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }

                        return;
                    }

                    if ($this->record->isLabType()) {
                        if ($this->record->labParameterDetails()->isEmpty() || ! filled($this->record->labInformeText())) {
                            Notification::make()
                                ->title('No se puede confirmar el resultado')
                                ->body('Debe cargar los valores del análisis y el informe clínico antes de confirmar. Use la opción "Editar".')
                                ->warning()
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }
                    }
                })
                ->action(function () {
                    try {
                        DB::transaction(function (): void {
                            $this->record->update(['validated_at' => now()]);
                            $this->record->refresh();
                            $this->record->load(['order.patient', 'exam', 'responsibleUser', 'details']);

                            app(ResultPdfService::class)->generateAndStore($this->record);
                            $this->record->refresh();
                        });

                        $recordFresh = $this->record->fresh();
                        $delivery = app(ResultDeliveryService::class)->publishAndNotify($recordFresh);
                        app(ResultValidatedNotifier::class)->notifyAfterPublish($recordFresh->fresh());

                        $parts = ['El PDF fue generado correctamente.'];
                        if ($delivery['portal_published'] ?? false) {
                            $parts[] = 'El resultado quedó disponible en el portal del paciente.';
                        }
                        if ($delivery['email_sent'] ?? false) {
                            $parts[] = 'Se envió una copia al correo del paciente.';
                        } elseif (! empty($delivery['email_skipped_reason'])) {
                            $parts[] = 'Correo: '.$delivery['email_skipped_reason'];
                        }

                        Notification::make()
                            ->title('Resultado confirmado')
                            ->body(implode(' ', $parts))
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Error al confirmar resultado')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
