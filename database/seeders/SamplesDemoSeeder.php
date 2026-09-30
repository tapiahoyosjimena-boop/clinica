<?php

namespace Database\Seeders;

use App\Domains\Orders\Models\Order;
use App\Domains\Samples\Models\Sample;
use App\Domains\Samples\Models\SampleStatusHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Muestras demo para órdenes de laboratorio ORD-DEMO-2026-* pagadas.
 * Todas en estado recibida (como registro inicial en recepción).
 *
 * Ejecutar tras OrdersDemoSeeder:
 * php artisan db:seed --class=SamplesDemoSeeder --force
 */
class SamplesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $receptionist = User::query()->where('email', 'recepcionista@tecnoweb.shop')->first();
        if (! $receptionist) {
            throw new \RuntimeException('SamplesDemoSeeder: falta recepcionista@tecnoweb.shop (AuthSeeder).');
        }

        $orders = Order::query()
            ->where('order_number', 'like', OrdersDemoSeeder::ORDER_NUMBER_PREFIX.'%')
            ->where('type', 'laboratorio')
            ->with(['exams', 'invoice'])
            ->orderBy('order_number')
            ->get();

        if ($orders->isEmpty()) {
            $this->command?->warn('SamplesDemoSeeder: no hay órdenes lab demo. Ejecute OrdersDemoSeeder.');

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

            $scheduled = $order->getScheduledDateTime();
            if (! $scheduled) {
                $this->command?->warn("SamplesDemoSeeder: orden {$order->order_number} sin cita programada; omitida.");

                continue;
            }

            foreach ($order->exams as $exam) {
                if (Sample::query()->where('order_id', $order->id)->where('exam_id', $exam->id)->exists()) {
                    $skipped++;

                    continue;
                }

                $sample = Sample::create([
                    'order_id' => $order->id,
                    'exam_id' => $exam->id,
                    'barcode' => Sample::generateBarcode(),
                    'status' => 'recibida',
                    'collected_at' => $scheduled,
                    'collected_by' => $receptionist->id,
                    'bioquimico_asignado_id' => $order->responsible_user_id,
                    'location' => 'Laboratorio — recepción demo',
                    'notes' => 'Muestra demo (recibida).',
                ]);

                SampleStatusHistory::create([
                    'sample_id' => $sample->id,
                    'old_status' => null,
                    'new_status' => 'recibida',
                    'changed_by' => $receptionist->id,
                    'notes' => 'Muestra creada.',
                ]);

                $created++;
            }
        }

        $this->command?->info("✅ SamplesDemoSeeder: {$created} muestras creadas ({$skipped} ya existían).");
        if ($unpaid > 0) {
            $this->command?->warn("   {$unpaid} órdenes lab sin comprobante pagado (ejecute pagos en OrdersDemoSeeder).");
        }
    }
}
