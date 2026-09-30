<?php

namespace App\Domains\Payments\Services;

use App\Domains\Imaging\Models\ImagingStudy;
use App\Domains\Orders\Models\Order;
use App\Domains\Results\Models\Result;
use App\Domains\Samples\Models\Sample;

/**
 * Calcula, para cada examen de una orden, el paso presencial pendiente (tomar muestra,
 * presentarse al estudio de imagen o recoger resultado) que se imprime en la Ficha de Atención.
 * Se recalcula siempre "al vuelo" (nunca se guarda), así el estado mostrado es siempre el actual.
 */
class AttentionSlipService
{
    /**
     * @return array{
     *     headline: array{type: string, message: string},
     *     items: list<array{exam_name: string, exam_type: string, status_label: string, action: string, barcode: ?string, instructions: list<string>}>
     * }
     */
    public function buildForOrder(Order $order): array
    {
        $order->loadMissing(['exams.requirements', 'samples', 'imagingStudies']);

        $results = Result::query()
            ->where('order_id', $order->id)
            ->get()
            ->keyBy('exam_id');

        $items = [];
        $actions = [];

        foreach ($order->exams as $exam) {
            $isValidated = $results->get($exam->id)?->validated_at !== null;

            $item = [
                'exam_name' => $exam->name,
                'exam_type' => $exam->type,
                'barcode' => null,
                'instructions' => $exam->requirements->pluck('description')->filter()->values()->all(),
            ];

            if ($exam->type === 'laboratorio') {
                $sample = $order->samples->firstWhere('exam_id', $exam->id);
                [$item['action'], $item['status_label']] = $this->resolveLabAction($sample, $isValidated);
                $item['barcode'] = $sample?->barcode;
            } elseif ($exam->type === 'imagen') {
                $study = $order->imagingStudies->firstWhere('exam_id', $exam->id);
                [$item['action'], $item['status_label']] = $this->resolveImagingAction($study, $isValidated);
            } else {
                $item['action'] = 'sin_pendientes';
                $item['status_label'] = 'Sin información';
            }

            $actions[] = $item['action'];
            $items[] = $item;
        }

        return [
            'headline' => $this->resolveHeadline($actions),
            'items' => $items,
        ];
    }

    /**
     * Indica si la orden tiene algún paso presencial pendiente (muestra/estudio por realizar
     * o resultado ya listo para recoger). Se usa para decidir si mostrar el botón de la ficha.
     */
    public function hasPendingAction(Order $order): bool
    {
        return in_array($this->buildForOrder($order)['headline']['type'], ['pendiente', 'listo'], true);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveLabAction(?Sample $sample, bool $isValidated): array
    {
        if (! $sample) {
            return ['pendiente', 'Pendiente de toma de muestra'];
        }

        if ($sample->status === 'rechazada') {
            return ['pendiente', 'Muestra rechazada — debe volver a tomarse'];
        }

        if ($isValidated) {
            return ['listo', 'Resultado listo para recoger'];
        }

        return ['en_proceso', 'Muestra recibida — resultado en proceso'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveImagingAction(?ImagingStudy $study, bool $isValidated): array
    {
        if (! $study || $study->status === 'programado') {
            return ['pendiente', 'Pendiente de presentarse al estudio de imagen'];
        }

        if ($study->status === 'cancelado') {
            return ['sin_pendientes', 'Estudio cancelado'];
        }

        if ($isValidated) {
            return ['listo', 'Resultado listo para recoger'];
        }

        return ['en_proceso', 'Estudio realizado — resultado en proceso'];
    }

    /**
     * @param  list<string>  $actions
     * @return array{type: string, message: string}
     */
    private function resolveHeadline(array $actions): array
    {
        if (in_array('pendiente', $actions, true)) {
            return [
                'type' => 'pendiente',
                'message' => 'Debe presentarse en la clínica para completar los pasos indicados abajo.',
            ];
        }

        if (in_array('listo', $actions, true)) {
            return [
                'type' => 'listo',
                'message' => 'Sus resultados están listos. Puede recogerlos presentando esta ficha.',
            ];
        }

        if (in_array('en_proceso', $actions, true)) {
            return [
                'type' => 'en_proceso',
                'message' => 'Sus exámenes están en proceso. Aún no requiere presentarse.',
            ];
        }

        return [
            'type' => 'sin_pendientes',
            'message' => 'No hay pasos pendientes para esta orden.',
        ];
    }
}
