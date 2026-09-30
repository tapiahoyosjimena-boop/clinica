<?php

namespace Database\Seeders;

use App\Domains\Catalog\Models\ExamCategory;
use App\Domains\Catalog\Models\ExamParameter;
use Illuminate\Database\Seeder;

/**
 * Parámetros analíticos para categorías de laboratorio del catálogo Clínica Norte.
 * Rangos de referencia orientativos para adultos (valores enteros según esquema actual).
 * Idempotente: no duplica parámetros ya existentes por categoría + nombre.
 */
class LaboratoryExamParametersSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = $this->parameterDefinitions();
        $created = 0;
        $skipped = 0;

        foreach ($definitions as $categoryName => $parameters) {
            $category = ExamCategory::query()
                ->where('name', $categoryName)
                ->where('type', 'laboratorio')
                ->first();

            if ($category === null) {
                $this->command?->warn("Categoría no encontrada (laboratorio): {$categoryName}");

                continue;
            }

            foreach ($parameters as $parameter) {
                $exists = ExamParameter::query()
                    ->where('exam_category_id', $category->id)
                    ->where('name', $parameter['name'])
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                ExamParameter::query()->create([
                    'exam_category_id' => $category->id,
                    ...$parameter,
                ]);
                $created++;
            }
        }

        $this->command?->info("LaboratoryExamParametersSeeder: {$created} parámetro(s) creado(s), {$skipped} omitido(s) (ya existían).");
    }

    /**
     * @return array<string, list<array{name: string, unit: ?string, reference_min: ?int, reference_max: ?int, critical_min: ?int, critical_max: ?int}>>
     */
    protected function parameterDefinitions(): array
    {
        return [
            'Hematología' => [
                ['name' => 'Hemoglobina', 'unit' => 'g/dL', 'reference_min' => 12, 'reference_max' => 17, 'critical_min' => 7, 'critical_max' => 20],
                ['name' => 'Hematocrito', 'unit' => '%', 'reference_min' => 36, 'reference_max' => 52, 'critical_min' => 20, 'critical_max' => 60],
                ['name' => 'Eritrocitos', 'unit' => '×10⁶/µL', 'reference_min' => 4, 'reference_max' => 6, 'critical_min' => 2, 'critical_max' => 8],
                ['name' => 'VCM', 'unit' => 'fL', 'reference_min' => 80, 'reference_max' => 100, 'critical_min' => 60, 'critical_max' => 120],
                ['name' => 'HCM', 'unit' => 'pg', 'reference_min' => 27, 'reference_max' => 33, 'critical_min' => 20, 'critical_max' => 40],
                ['name' => 'CHCM', 'unit' => 'g/dL', 'reference_min' => 32, 'reference_max' => 36, 'critical_min' => 28, 'critical_max' => 38],
                ['name' => 'Leucocitos', 'unit' => '×10³/µL', 'reference_min' => 4, 'reference_max' => 11, 'critical_min' => 1, 'critical_max' => 30],
                ['name' => 'Neutrófilos', 'unit' => '%', 'reference_min' => 40, 'reference_max' => 70, 'critical_min' => 10, 'critical_max' => 90],
                ['name' => 'Linfocitos', 'unit' => '%', 'reference_min' => 20, 'reference_max' => 45, 'critical_min' => 5, 'critical_max' => 80],
                ['name' => 'Monocitos', 'unit' => '%', 'reference_min' => 2, 'reference_max' => 10, 'critical_min' => 0, 'critical_max' => 20],
                ['name' => 'Eosinófilos', 'unit' => '%', 'reference_min' => 1, 'reference_max' => 6, 'critical_min' => 0, 'critical_max' => 15],
                ['name' => 'Basófilos', 'unit' => '%', 'reference_min' => 0, 'reference_max' => 2, 'critical_min' => 0, 'critical_max' => 5],
                ['name' => 'Plaquetas', 'unit' => '×10³/µL', 'reference_min' => 150, 'reference_max' => 450, 'critical_min' => 50, 'critical_max' => 1000],
                ['name' => 'VSG', 'unit' => 'mm/h', 'reference_min' => 0, 'reference_max' => 20, 'critical_min' => 0, 'critical_max' => 100],
            ],

            'Bioquímica Clínica' => [
                ['name' => 'Glucosa en ayunas', 'unit' => 'mg/dL', 'reference_min' => 70, 'reference_max' => 100, 'critical_min' => 40, 'critical_max' => 400],
                ['name' => 'Urea', 'unit' => 'mg/dL', 'reference_min' => 15, 'reference_max' => 45, 'critical_min' => 5, 'critical_max' => 100],
                ['name' => 'Creatinina', 'unit' => 'mg/dL', 'reference_min' => 1, 'reference_max' => 2, 'critical_min' => 0, 'critical_max' => 10],
                ['name' => 'TGO / AST', 'unit' => 'U/L', 'reference_min' => 10, 'reference_max' => 40, 'critical_min' => 0, 'critical_max' => 500],
                ['name' => 'TGP / ALT', 'unit' => 'U/L', 'reference_min' => 7, 'reference_max' => 56, 'critical_min' => 0, 'critical_max' => 500],
                ['name' => 'Bilirrubina total', 'unit' => 'mg/dL', 'reference_min' => 0, 'reference_max' => 1, 'critical_min' => 0, 'critical_max' => 10],
                ['name' => 'Bilirrubina directa', 'unit' => 'mg/dL', 'reference_min' => 0, 'reference_max' => 1, 'critical_min' => 0, 'critical_max' => 5],
                ['name' => 'Ácido úrico', 'unit' => 'mg/dL', 'reference_min' => 4, 'reference_max' => 7, 'critical_min' => 1, 'critical_max' => 10],
                ['name' => 'Proteínas totales', 'unit' => 'g/dL', 'reference_min' => 6, 'reference_max' => 8, 'critical_min' => 4, 'critical_max' => 10],
                ['name' => 'Albúmina', 'unit' => 'g/dL', 'reference_min' => 4, 'reference_max' => 5, 'critical_min' => 2, 'critical_max' => 6],
            ],

            'Perfil Lipídico' => [
                ['name' => 'Colesterol total', 'unit' => 'mg/dL', 'reference_min' => 0, 'reference_max' => 199, 'critical_min' => 0, 'critical_max' => 300],
                ['name' => 'HDL colesterol', 'unit' => 'mg/dL', 'reference_min' => 40, 'reference_max' => 60, 'critical_min' => 20, 'critical_max' => 100],
                ['name' => 'LDL colesterol', 'unit' => 'mg/dL', 'reference_min' => 0, 'reference_max' => 99, 'critical_min' => 0, 'critical_max' => 190],
                ['name' => 'Triglicéridos', 'unit' => 'mg/dL', 'reference_min' => 0, 'reference_max' => 149, 'critical_min' => 0, 'critical_max' => 500],
                ['name' => 'VLDL colesterol', 'unit' => 'mg/dL', 'reference_min' => 0, 'reference_max' => 30, 'critical_min' => 0, 'critical_max' => 80],
            ],

            'Coagulación' => [
                ['name' => 'INR', 'unit' => 'índice', 'reference_min' => 1, 'reference_max' => 2, 'critical_min' => 0, 'critical_max' => 5],
                ['name' => 'Tiempo de protrombina', 'unit' => '%', 'reference_min' => 70, 'reference_max' => 100, 'critical_min' => 30, 'critical_max' => 150],
                ['name' => 'TTPA', 'unit' => 'seg', 'reference_min' => 25, 'reference_max' => 35, 'critical_min' => 15, 'critical_max' => 60],
                ['name' => 'Fibrinógeno', 'unit' => 'mg/dL', 'reference_min' => 200, 'reference_max' => 400, 'critical_min' => 100, 'critical_max' => 700],
                ['name' => 'Tiempo de sangría', 'unit' => 'min', 'reference_min' => 1, 'reference_max' => 3, 'critical_min' => 0, 'critical_max' => 10],
            ],

            'Uroanálisis' => [
                ['name' => 'Densidad urinaria', 'unit' => '×10⁻³', 'reference_min' => 1005, 'reference_max' => 1030, 'critical_min' => 1000, 'critical_max' => 1040],
                ['name' => 'pH urinario', 'unit' => 'pH', 'reference_min' => 5, 'reference_max' => 8, 'critical_min' => 4, 'critical_max' => 9],
                ['name' => 'Proteínas en orina', 'unit' => 'mg/dL', 'reference_min' => 0, 'reference_max' => 15, 'critical_min' => 0, 'critical_max' => 300],
                ['name' => 'Glucosa en orina', 'unit' => 'mg/dL', 'reference_min' => 0, 'reference_max' => 15, 'critical_min' => 0, 'critical_max' => 500],
                ['name' => 'Leucocitos en sedimento', 'unit' => '/campo', 'reference_min' => 0, 'reference_max' => 5, 'critical_min' => 0, 'critical_max' => 50],
                ['name' => 'Eritrocitos en sedimento', 'unit' => '/campo', 'reference_min' => 0, 'reference_max' => 3, 'critical_min' => 0, 'critical_max' => 50],
                ['name' => 'Microalbuminuria', 'unit' => 'mg/L', 'reference_min' => 0, 'reference_max' => 30, 'critical_min' => 0, 'critical_max' => 300],
                ['name' => 'Creatinina en orina', 'unit' => 'mg/dL', 'reference_min' => 20, 'reference_max' => 250, 'critical_min' => 0, 'critical_max' => 500],
            ],

            'Serología e Inmunología' => [
                ['name' => 'Proteína C reactiva (PCR)', 'unit' => 'mg/L', 'reference_min' => 0, 'reference_max' => 5, 'critical_min' => 0, 'critical_max' => 100],
                ['name' => 'VDRL — título', 'unit' => 'dilución', 'reference_min' => 0, 'reference_max' => 1, 'critical_min' => 0, 'critical_max' => 32],
                ['name' => 'HIV — índice S/CO', 'unit' => 'índice', 'reference_min' => 0, 'reference_max' => 1, 'critical_min' => 0, 'critical_max' => 10],
                ['name' => 'HBsAg — índice S/CO', 'unit' => 'índice', 'reference_min' => 0, 'reference_max' => 1, 'critical_min' => 0, 'critical_max' => 10],
                ['name' => 'Anti-HCV — índice S/CO', 'unit' => 'índice', 'reference_min' => 0, 'reference_max' => 1, 'critical_min' => 0, 'critical_max' => 10],
            ],

            'Hormonas y Tiroides' => [
                ['name' => 'TSH', 'unit' => 'µUI/mL', 'reference_min' => 0, 'reference_max' => 4, 'critical_min' => 0, 'critical_max' => 20],
                ['name' => 'T4 libre', 'unit' => 'ng/dL', 'reference_min' => 1, 'reference_max' => 2, 'critical_min' => 0, 'critical_max' => 5],
                ['name' => 'T3 total', 'unit' => 'ng/dL', 'reference_min' => 80, 'reference_max' => 200, 'critical_min' => 40, 'critical_max' => 400],
                ['name' => 'Prolactina', 'unit' => 'ng/mL', 'reference_min' => 4, 'reference_max' => 30, 'critical_min' => 0, 'critical_max' => 200],
                ['name' => 'Cortisol matutino', 'unit' => 'µg/dL', 'reference_min' => 6, 'reference_max' => 23, 'critical_min' => 2, 'critical_max' => 60],
            ],
        ];
    }
}
