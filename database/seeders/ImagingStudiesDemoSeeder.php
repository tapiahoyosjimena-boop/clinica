<?php

namespace Database\Seeders;

use App\Domains\Imaging\Models\ImagingStudy;
use App\Domains\Imaging\Models\ImagingStatusHistory;
use App\Domains\Orders\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Estudios de imagen demo para órdenes ORD-DEMO-2026-* (tipo imagen) pagadas.
 * Un estudio por orden, todos en estado programado.
 *
 * Ejecutar tras OrdersDemoSeeder:
 * php artisan db:seed --class=ImagingStudiesDemoSeeder --force
 */
class ImagingStudiesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $receptionist = User::query()->where('email', 'recepcionista@tecnoweb.shop')->first();
        if (! $receptionist) {
            throw new \RuntimeException('ImagingStudiesDemoSeeder: falta recepcionista@tecnoweb.shop (AuthSeeder).');
        }

        $orders = Order::query()
            ->where('order_number', 'like', OrdersDemoSeeder::ORDER_NUMBER_PREFIX.'%')
            ->where('type', 'imagen')
            ->with(['exams', 'invoice'])
            ->orderBy('order_number')
            ->get();

        if ($orders->isEmpty()) {
            $this->command?->warn('ImagingStudiesDemoSeeder: no hay órdenes imagen demo. Ejecute OrdersDemoSeeder.');

            return;
        }

        $created = 0;
        $skipped = 0;
        $unpaid = 0;

        foreach ($orders as $order) {
            if ($order->invoice?->status !== 'pagada') {
                $unpaid++;

                continue;
            }

            if (ImagingStudy::query()->where('order_id', $order->id)->exists()) {
                $skipped++;

                continue;
            }

            $scheduled = $order->getScheduledDateTime();
            if (! $scheduled) {
                $this->command?->warn("ImagingStudiesDemoSeeder: orden {$order->order_number} sin cita programada; omitida.");

                continue;
            }

            $exam = $order->exams->firstWhere('type', 'imagen');
            if (! $exam) {
                $this->command?->warn("ImagingStudiesDemoSeeder: orden {$order->order_number} sin examen de imagen; omitida.");

                continue;
            }

            $equipmentId = $order->equipment_id ?? $exam->imaging_equipment_id;
            if (! $equipmentId) {
                $this->command?->warn("ImagingStudiesDemoSeeder: orden {$order->order_number} sin equipo; omitida.");

                continue;
            }

            $study = ImagingStudy::create([
                'order_id' => $order->id,
                'exam_id' => $exam->id,
                'equipment_id' => $equipmentId,
                'responsible_user_id' => $order->responsible_user_id,
                'study_code' => ImagingStudy::generateStudyCode(),
                'status' => 'programado',
                'collected_at' => $scheduled,
                'result_file' => null,
                'result_notes' => null,
                'rejection_reason' => null,
            ]);

            ImagingStatusHistory::create([
                'imaging_study_id' => $study->id,
                'old_status' => null,
                'new_status' => 'programado',
                'changed_by' => $receptionist->id,
                'notes' => 'Estudio de imagen creado.',
            ]);

            $created++;
        }

        $this->command?->info("✅ ImagingStudiesDemoSeeder: {$created} estudios creados ({$skipped} ya existían).");
        if ($unpaid > 0) {
            $this->command?->warn("   {$unpaid} órdenes imagen sin comprobante pagado (ejecute OrdersDemoSeeder).");
        }
    }
}
