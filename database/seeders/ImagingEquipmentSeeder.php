<?php

namespace Database\Seeders;

use App\Domains\Imaging\Models\ImagingEquipment;
use Illuminate\Database\Seeder;

/**
 * Equipos de imagen requeridos por exámenes de tipo imagen en el catálogo.
 * Idempotente por nombre.
 */
class ImagingEquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $equipment = [
            [
                'name' => 'Rayos X Digital Philips DigitalDiagnost',
                'type' => 'rayos_x',
                'description' => 'Equipo de radiografía digital para estudios convencionales.',
                'status' => 'disponible',
            ],
            [
                'name' => 'Ecógrafo GE Voluson E10',
                'type' => 'ecógrafo',
                'description' => 'Ecógrafo de alta resolución para estudios abdominales, pélvicos y obstétricos.',
                'status' => 'disponible',
            ],
            [
                'name' => 'Tomógrafo Siemens SOMATOM 64 cortes',
                'type' => 'tomógrafo',
                'description' => 'Tomógrafo multicorte para estudios de cráneo, tórax, abdomen y columna.',
                'status' => 'disponible',
            ],
        ];

        foreach ($equipment as $item) {
            ImagingEquipment::query()->firstOrCreate(
                ['name' => $item['name']],
                $item,
            );
        }

        $this->command?->info('✅ ImagingEquipmentSeeder: '.count($equipment).' equipos de imagen verificados.');
    }
}
