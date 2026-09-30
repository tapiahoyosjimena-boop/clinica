<?php

namespace App\Domains\Results\Support;

use App\Domains\Catalog\Models\Exam;
use App\Domains\Catalog\Models\ExamParameter;

class LabResultCriticalEvaluator
{
    /**
     * @param  array<int|string, mixed>  $paramIdToValue
     * @return array{is_critical: bool, reasons: list<string>}
     */
    public static function evaluate(Exam $exam, array $paramIdToValue): array
    {
        $params = ExamParameter::query()
            ->where('exam_category_id', $exam->exam_category_id)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $reasons = [];
        foreach ($paramIdToValue as $paramId => $rawValue) {
            $param = $params->get((int) $paramId);
            if (! $param || ! is_numeric($rawValue)) {
                continue;
            }
            $val = (float) $rawValue;
            $unitSuffix = $param->unit !== null && $param->unit !== '' ? ' '.$param->unit : '';

            if ($param->critical_min !== null && $val < (float) $param->critical_min) {
                $reasons[] = sprintf(
                    '%s: el valor informado (%s%s) está por debajo del límite crítico inferior (%d%s).',
                    $param->name,
                    $rawValue,
                    $unitSuffix,
                    (int) $param->critical_min,
                    $unitSuffix
                );
            }
            if ($param->critical_max !== null && $val > (float) $param->critical_max) {
                $reasons[] = sprintf(
                    '%s: el valor informado (%s%s) supera el límite crítico superior (%d%s).',
                    $param->name,
                    $rawValue,
                    $unitSuffix,
                    (int) $param->critical_max,
                    $unitSuffix
                );
            }
        }

        return [
            'is_critical' => $reasons !== [],
            'reasons' => $reasons,
        ];
    }
}
