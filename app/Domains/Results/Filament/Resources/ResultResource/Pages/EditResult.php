<?php

namespace App\Domains\Results\Filament\Resources\ResultResource\Pages;

use App\Domains\Catalog\Models\ExamParameter;
use App\Domains\Results\Filament\Resources\ResultResource;
use App\Domains\Results\Models\Result;
use App\Domains\Results\Models\ResultDetail;
use App\Domains\Results\Support\LabResultCriticalEvaluator;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class EditResult extends EditRecord
{
    protected static string $resource = ResultResource::class;

    /** @var array<int, mixed> */
    protected array $pendingLabParamValues = [];

    protected ?string $pendingLabInforme = null;

    protected ?string $pendingImagingInforme = null;

    protected ?bool $pendingImagingIsCritical = null;

    protected array $pendingReagentsUsed = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),

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
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing(['details', 'order', 'exam']);

        if ($this->record->order?->type === 'laboratorio') {
            $data['lab_informe'] = $this->record->labInformeText() ?? '';
            $data['reagents_used'] = \App\Domains\Reactivos\Models\StockMovement::where('order_id', $this->record->order_id)
                ->where('type', 'salida')
                ->get()
                ->map(fn ($m) => [
                    'reagent_id' => $m->reagent_id,
                    'quantity' => $m->quantity,
                ])
                ->toArray();
        }

        if ($this->record->order?->type === 'imagen') {
            $data['imaging_informe'] = $this->record->details->firstWhere('parameter_name', 'Informe')?->value ?? '';
            // Rellenar el toggle con el valor ya guardado en BD.
            $data['is_critical'] = (bool) $this->record->is_critical;

            return $data;
        }

        $exam = $this->record->exam;
        if ($exam) {
            $paramsByName = ExamParameter::where('exam_category_id', $exam->exam_category_id)
                ->get()
                ->keyBy('name');
            foreach ($this->record->details as $detail) {
                $param = $paramsByName->get($detail->parameter_name);
                if ($param) {
                    $data['param_'.$param->id] = $detail->value;
                }
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingLabParamValues = [];
        $this->pendingLabInforme = null;
        $this->pendingImagingInforme = null;
        $this->pendingImagingIsCritical = null;
        $this->pendingReagentsUsed = [];

        if (array_key_exists('reagents_used', $data)) {
            $this->pendingReagentsUsed = $data['reagents_used'];
            unset($data['reagents_used']);
        }

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'param_')) {
                $this->pendingLabParamValues[(int) str_replace('param_', '', $key)] = $value;
                unset($data[$key]);
            }
        }

        if ($this->record->order?->type === 'laboratorio') {
            if (array_key_exists('lab_informe', $data)) {
                $this->pendingLabInforme = $data['lab_informe'];
                unset($data['lab_informe']);
            }
        }

        if (array_key_exists('imaging_informe', $data)) {
            $this->pendingImagingInforme = $data['imaging_informe'];
            unset($data['imaging_informe']);
        }

        // Para imagen guardamos el valor del toggle antes de sacarlo del array de datos del modelo.
        // Para laboratorio lo sacamos también: afterSave() lo recalcula con el evaluador.
        if (array_key_exists('is_critical', $data)) {
            $this->pendingImagingIsCritical = (bool) $data['is_critical'];
            unset($data['is_critical']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->loadMissing('order.exams');
        $order = $this->record->order;
        if (! $order) {
            return;
        }

        if ($order->type === 'laboratorio'
            && ($this->pendingLabParamValues !== [] || $this->pendingLabInforme !== null)
        ) {
            $exam = $order->exams->firstWhere('id', $this->record->exam_id);
            $params = ExamParameter::where('exam_category_id', $exam?->exam_category_id)->get()->keyBy('id');

            $this->record->details()->delete();

            foreach ($this->pendingLabParamValues as $paramId => $value) {
                $param = $params->get($paramId);
                ResultDetail::create([
                    'result_id' => $this->record->id,
                    'parameter_name' => $param?->name ?? 'Parámetro '.$paramId,
                    'value' => (string) $value,
                    'unit' => $param?->unit,
                    'reference_min' => $param?->reference_min !== null ? (int) $param->reference_min : null,
                    'reference_max' => $param?->reference_max !== null ? (int) $param->reference_max : null,
                ]);
            }

            if (filled($this->pendingLabInforme)) {
                ResultDetail::create([
                    'result_id' => $this->record->id,
                    'parameter_name' => Result::PARAMETER_NAME_LAB_INFORME,
                    'value' => $this->pendingLabInforme,
                ]);
            }

            $this->record->refresh();
        }

        if ($order->type === 'imagen') {
            if (filled($this->pendingImagingInforme)) {
                $this->record->details()->delete();
                ResultDetail::create([
                    'result_id' => $this->record->id,
                    'parameter_name' => 'Informe',
                    'value' => $this->pendingImagingInforme,
                ]);
            }

            // Imagen: criticidad manual, viene directamente del toggle del formulario.
            if ($this->pendingImagingIsCritical !== null) {
                $this->record->update(['is_critical' => $this->pendingImagingIsCritical]);
            }
        }

        if ($order->type === 'laboratorio') {
            // Lab: criticidad calculada automáticamente a partir de los rangos críticos del catálogo.
            $exam = $order->exams->firstWhere('id', $this->record->exam_id);
            if ($exam) {
                $values = $this->pendingLabParamValues !== []
                    ? $this->pendingLabParamValues
                    : $this->paramValuesFromRecordDetails();
                $eval = LabResultCriticalEvaluator::evaluate($exam, $values);
                $this->record->update(['is_critical' => $eval['is_critical']]);
            }

            // Revertir stock antiguo de movimientos de salida vinculados a esta orden
            $oldMovements = \App\Domains\Reactivos\Models\StockMovement::where('order_id', $order->id)
                ->where('type', 'salida')
                ->get();

            \DB::transaction(function () use ($oldMovements, $order) {
                // Revertimos stock sumando de nuevo al reactivo
                foreach ($oldMovements as $mov) {
                    $reagent = \App\Domains\Reactivos\Models\Reagent::find($mov->reagent_id);
                    if ($reagent) {
                        $reagent->increment('stock_quantity', $mov->quantity);
                    }
                    $mov->delete();
                }

                // Ahora procesamos los nuevos
                foreach ($this->pendingReagentsUsed as $item) {
                    $reagentId = $item['reagent_id'];
                    $quantity = (int) $item['quantity'];

                    $reagent = \App\Domains\Reactivos\Models\Reagent::find($reagentId);
                    if (! $reagent) {
                        throw new \RuntimeException("El reactivo seleccionado no existe.");
                    }

                    if ($reagent->stock_quantity < $quantity) {
                        throw new \RuntimeException("Stock insuficiente para el reactivo '{$reagent->name}'. Stock disponible: {$reagent->stock_quantity} {$reagent->unit}.");
                    }

                    // Descontar
                    $reagent->decrement('stock_quantity', $quantity);

                    // Registrar movimiento
                    \App\Domains\Reactivos\Models\StockMovement::create([
                        'reagent_id' => $reagentId,
                        'order_id' => $order->id,
                        'type' => 'salida',
                        'quantity' => $quantity,
                        'movement_date' => now(),
                        'user_id' => auth()->id(),
                        'notes' => "Consumido en el procesamiento de la orden #{$order->order_number}.",
                    ]);
                }
            });
        }
    }

    /**
     * @return array<int, mixed>
     */
    protected function paramValuesFromRecordDetails(): array
    {
        $this->record->loadMissing('details', 'exam');
        $exam = $this->record->exam;
        if (! $exam) {
            return [];
        }

        $paramsByName = ExamParameter::query()
            ->where('exam_category_id', $exam->exam_category_id)
            ->get()
            ->keyBy('name');

        $out = [];
        foreach ($this->record->details as $detail) {
            $param = $paramsByName->get($detail->parameter_name);
            if ($param) {
                $out[$param->id] = $detail->value;
            }
        }

        return $out;
    }
}
