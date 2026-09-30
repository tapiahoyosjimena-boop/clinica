<?php

namespace Database\Seeders;

use App\Domains\Catalog\Models\Exam;
use App\Domains\Catalog\Models\ExamCategory;
use App\Domains\Imaging\Models\ImagingEquipment;
use Illuminate\Database\Seeder;

/**
 * Catálogo de categorías y exámenes (laboratorio e imagen) para despliegue en VPS.
 * Idempotente: no duplica categorías ni exámenes existentes (por nombre).
 *
 * Precio único de presentación/demo (no tarifario real de la clínica).
 */
class CatalogSeeder extends Seeder
{
    /** Precio en Bs. para todos los exámenes en entornos de demostración. */
    private const PRESENTATION_PRICE_BS = 1.00;

    public function run(): void
    {
        $categoryIds = $this->seedCategories();
        $equipmentIds = $this->equipmentIdsByName();

        $createdExams = 0;
        $skippedExams = 0;

        Exam::withoutEvents(function () use ($categoryIds, $equipmentIds, &$createdExams, &$skippedExams): void {
            foreach ($this->examDefinitions() as $exam) {
                $categoryId = $categoryIds[$exam['category_name']] ?? null;
                $equipmentId = null;

                if ($exam['type'] === 'imagen' && isset($exam['equipment_name'])) {
                    $equipmentId = $equipmentIds[$exam['equipment_name']] ?? null;
                }

                $exists = Exam::query()->where('name', $exam['name'])->exists();

                Exam::query()->updateOrCreate(
                    ['name' => $exam['name']],
                    [
                        'exam_category_id' => $categoryId,
                        'type' => $exam['type'],
                        'price' => self::PRESENTATION_PRICE_BS,
                        'imaging_equipment_id' => $equipmentId,
                    ],
                );

                if ($exists) {
                    $skippedExams++;
                } else {
                    $createdExams++;
                }
            }
        });

        $this->command?->info('✅ CatalogSeeder: '.count($categoryIds).' categorías, '.$createdExams.' exámenes nuevos, '.$skippedExams.' ya existían.');
    }

    /**
     * @return array<string, int>
     */
    protected function seedCategories(): array
    {
        $ids = [];

        foreach ($this->categoryDefinitions() as $category) {
            $record = ExamCategory::query()->firstOrCreate(
                ['name' => $category['name']],
                [
                    'type' => $category['type'],
                    'description' => $category['description'],
                    'is_active' => true,
                ],
            );

            $ids[$category['name']] = $record->id;
        }

        return $ids;
    }

    /**
     * @return array<string, int>
     */
    protected function equipmentIdsByName(): array
    {
        return ImagingEquipment::query()
            ->pluck('id', 'name')
            ->all();
    }

    /**
     * @return list<array{name: string, type: string, description: ?string}>
     */
    protected function categoryDefinitions(): array
    {
        return [
            ['name' => 'Hematología', 'type' => 'laboratorio', 'description' => 'Estudios hematológicos y hemogramas.'],
            ['name' => 'Bioquímica Clínica', 'type' => 'laboratorio', 'description' => 'Perfiles bioquímicos y metabólicos en sangre.'],
            ['name' => 'Perfil Lipídico', 'type' => 'laboratorio', 'description' => 'Colesterol, triglicéridos y fracciones lipídicas.'],
            ['name' => 'Coagulación', 'type' => 'laboratorio', 'description' => 'Tiempos de coagulación y fibrinógeno.'],
            ['name' => 'Uroanálisis', 'type' => 'laboratorio', 'description' => 'Análisis de orina y estudios urológicos.'],
            ['name' => 'Serología e Inmunología', 'type' => 'laboratorio', 'description' => 'Marcadores serológicos e infecciosos.'],
            ['name' => 'Hormonas y Tiroides', 'type' => 'laboratorio', 'description' => 'Perfil tiroideo y hormonas.'],
            ['name' => 'Radiología Convencional', 'type' => 'imagen', 'description' => 'Radiografías convencionales.'],
            ['name' => 'Ecografía', 'type' => 'imagen', 'description' => 'Estudios ecográficos.'],
            ['name' => 'Tomografía Computarizada', 'type' => 'imagen', 'description' => 'Estudios tomográficos (TAC).'],
        ];
    }

    /**
     * @return list<array{name: string, type: string, category_name: string, equipment_name?: string}>
     */
    protected function examDefinitions(): array
    {
        $lab = 'laboratorio';
        $img = 'imagen';
        $rx = 'Rayos X Digital Philips DigitalDiagnost';
        $eco = 'Ecógrafo GE Voluson E10';
        $tac = 'Tomógrafo Siemens SOMATOM 64 cortes';

        return [
            // Hematología
            ['name' => 'Hemograma completo', 'type' => $lab, 'category_name' => 'Hematología'],
            ['name' => 'Hemoglobina y hematocrito', 'type' => $lab, 'category_name' => 'Hematología'],
            ['name' => 'Velocidad de sedimentación globular (VSG)', 'type' => $lab, 'category_name' => 'Hematología'],
            ['name' => 'Recuento de plaquetas', 'type' => $lab, 'category_name' => 'Hematología'],

            // Bioquímica Clínica
            ['name' => 'Glucosa en ayunas', 'type' => $lab, 'category_name' => 'Bioquímica Clínica'],
            ['name' => 'Urea y creatinina', 'type' => $lab, 'category_name' => 'Bioquímica Clínica'],
            ['name' => 'Perfil hepático (TGO, TGP, bilirrubinas)', 'type' => $lab, 'category_name' => 'Bioquímica Clínica'],
            ['name' => 'Ácido úrico', 'type' => $lab, 'category_name' => 'Bioquímica Clínica'],
            ['name' => 'Proteínas totales y albúmina', 'type' => $lab, 'category_name' => 'Bioquímica Clínica'],

            // Perfil Lipídico
            ['name' => 'Colesterol total', 'type' => $lab, 'category_name' => 'Perfil Lipídico'],
            ['name' => 'HDL colesterol', 'type' => $lab, 'category_name' => 'Perfil Lipídico'],
            ['name' => 'LDL colesterol', 'type' => $lab, 'category_name' => 'Perfil Lipídico'],
            ['name' => 'Triglicéridos', 'type' => $lab, 'category_name' => 'Perfil Lipídico'],
            ['name' => 'Perfil lipídico completo', 'type' => $lab, 'category_name' => 'Perfil Lipídico'],

            // Coagulación
            ['name' => 'Tiempo de protrombina (TP / INR)', 'type' => $lab, 'category_name' => 'Coagulación'],
            ['name' => 'TTPA (tiempo parcial de tromboplastina)', 'type' => $lab, 'category_name' => 'Coagulación'],
            ['name' => 'Fibrinógeno', 'type' => $lab, 'category_name' => 'Coagulación'],
            ['name' => 'Tiempo de sangría', 'type' => $lab, 'category_name' => 'Coagulación'],

            // Uroanálisis
            ['name' => 'Examen general de orina', 'type' => $lab, 'category_name' => 'Uroanálisis'],
            ['name' => 'Urocultivo con antibiograma', 'type' => $lab, 'category_name' => 'Uroanálisis'],
            ['name' => 'Microalbuminuria en orina', 'type' => $lab, 'category_name' => 'Uroanálisis'],
            ['name' => 'Creatinina en orina de 24 horas', 'type' => $lab, 'category_name' => 'Uroanálisis'],

            // Serología e Inmunología
            ['name' => 'VDRL / RPR (sífilis)', 'type' => $lab, 'category_name' => 'Serología e Inmunología'],
            ['name' => 'HIV ELISA (VIH)', 'type' => $lab, 'category_name' => 'Serología e Inmunología'],
            ['name' => 'HBsAg (Hepatitis B)', 'type' => $lab, 'category_name' => 'Serología e Inmunología'],
            ['name' => 'Anti-HCV (Hepatitis C)', 'type' => $lab, 'category_name' => 'Serología e Inmunología'],
            ['name' => 'Proteína C reactiva (PCR)', 'type' => $lab, 'category_name' => 'Serología e Inmunología'],

            // Hormonas y Tiroides
            ['name' => 'TSH (hormona estimulante de tiroides)', 'type' => $lab, 'category_name' => 'Hormonas y Tiroides'],
            ['name' => 'T4 libre', 'type' => $lab, 'category_name' => 'Hormonas y Tiroides'],
            ['name' => 'T3 total', 'type' => $lab, 'category_name' => 'Hormonas y Tiroides'],
            ['name' => 'Prolactina', 'type' => $lab, 'category_name' => 'Hormonas y Tiroides'],
            ['name' => 'Cortisol matutino', 'type' => $lab, 'category_name' => 'Hormonas y Tiroides'],

            // Radiología Convencional
            ['name' => 'Radiografía de tórax PA y lateral', 'type' => $img, 'category_name' => 'Radiología Convencional', 'equipment_name' => $rx],
            ['name' => 'Radiografía de columna lumbar AP y lateral', 'type' => $img, 'category_name' => 'Radiología Convencional', 'equipment_name' => $rx],
            ['name' => 'Radiografía de cráneo', 'type' => $img, 'category_name' => 'Radiología Convencional', 'equipment_name' => $rx],
            ['name' => 'Radiografía de pelvis', 'type' => $img, 'category_name' => 'Radiología Convencional', 'equipment_name' => $rx],
            ['name' => 'Radiografía de mano', 'type' => $img, 'category_name' => 'Radiología Convencional', 'equipment_name' => $rx],

            // Ecografía
            ['name' => 'Ecografía abdominal total', 'type' => $img, 'category_name' => 'Ecografía', 'equipment_name' => $eco],
            ['name' => 'Ecografía renal', 'type' => $img, 'category_name' => 'Ecografía', 'equipment_name' => $eco],
            ['name' => 'Ecografía pélvica', 'type' => $img, 'category_name' => 'Ecografía', 'equipment_name' => $eco],
            ['name' => 'Ecografía obstétrica', 'type' => $img, 'category_name' => 'Ecografía', 'equipment_name' => $eco],
            ['name' => 'Ecografía de tiroides', 'type' => $img, 'category_name' => 'Ecografía', 'equipment_name' => $eco],

            // Tomografía Computarizada
            ['name' => 'TAC de cráneo simple', 'type' => $img, 'category_name' => 'Tomografía Computarizada', 'equipment_name' => $tac],
            ['name' => 'TAC de tórax', 'type' => $img, 'category_name' => 'Tomografía Computarizada', 'equipment_name' => $tac],
            ['name' => 'TAC de abdomen', 'type' => $img, 'category_name' => 'Tomografía Computarizada', 'equipment_name' => $tac],
            ['name' => 'TAC de columna lumbar', 'type' => $img, 'category_name' => 'Tomografía Computarizada', 'equipment_name' => $tac],
        ];
    }
}
