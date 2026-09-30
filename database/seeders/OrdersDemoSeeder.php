<?php

namespace Database\Seeders;

use App\Domains\Catalog\Models\Exam;
use App\Domains\Orders\Models\Order;
use App\Domains\Patients\Models\Patient;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Models\PaymentMethod;
use App\Domains\Payments\Services\PaymentReceiptService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 30 órdenes demo (15 laboratorio + 15 imagen), mayo 2026 (días 1–20).
 *
 * Ejecutar tras AuthSeeder, CatalogSeeder y (recomendado) PatientsDemoSeeder / DoctorsDemoSeeder:
 * php artisan db:seed --class=OrdersDemoSeeder --force
 */
class OrdersDemoSeeder extends Seeder
{
    public const ORDER_NUMBER_PREFIX = 'ORD-DEMO-2026-';

    private const TOTAL_ORDERS = 30;

    private const LAB_COUNT = 15;

    private const IMAGING_COUNT = 15;

    /** @var array<int, string> */
    private const PATIENT_CIS = [
        '12345678',
        '9686907',
        PatientsDemoSeeder::CI_MARIA,
        PatientsDemoSeeder::CI_CARLOS,
        PatientsDemoSeeder::CI_SOFIA,
    ];

    /** @var array<int, string> */
    private const SCHEDULED_TIMES = [
        '08:00',
        '08:30',
        '09:00',
        '09:30',
        '10:00',
        '10:30',
        '11:00',
        '11:30',
        '14:00',
        '14:30',
        '15:00',
        '15:30',
        '16:00',
        '16:30',
        '17:00',
    ];

    public function run(): void
    {
        $receptionist = $this->requireUser('recepcionista@tecnoweb.shop', 'Recepcionista');
        $bioquimico = $this->requireUser('bioquimico@tecnoweb.shop', 'Bioquímico');
        $tecnologo = $this->requireUser('tecnologo@tecnoweb.shop', 'Tecnólogo de Imagen');
        $medicoA = $this->requireUser('medico@tecnoweb.shop', 'Médico');
        $medicoB = $this->requireUser(DoctorsDemoSeeder::EMAIL, 'Médico demo');

        $patients = $this->resolvePatients();
        $labExams = $this->loadLabExams();
        $imagingExams = $this->loadImagingExams();

        if ($labExams->isEmpty() || $imagingExams->isEmpty()) {
            $this->command?->error('OrdersDemoSeeder: faltan exámenes activos en catálogo. Ejecute CatalogSeeder.');

            return;
        }

        $efectivo = PaymentMethod::query()->where('name', 'Efectivo')->where('is_active', true)->first();
        $qr = PaymentMethod::query()->where('name', 'QR')->where('is_active', true)->first();
        if (! $efectivo || ! $qr) {
            throw new \RuntimeException('OrdersDemoSeeder: ejecute PaymentMethodSeeder (Efectivo y QR).');
        }

        $tz = config('app.timezone', 'America/La_Paz');
        $created = 0;
        $skipped = 0;
        $paid = 0;

        for ($i = 1; $i <= self::TOTAL_ORDERS; $i++) {
            $orderNumber = self::ORDER_NUMBER_PREFIX.str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            $existing = Order::query()->where('order_number', $orderNumber)->first();
            if ($existing) {
                $order = $existing;
                $skipped++;
            } else {
                $order = null;
            }

            $isLab = $i <= self::LAB_COUNT;
            $type = $isLab ? 'laboratorio' : 'imagen';
            $indexInType = $isLab ? $i - 1 : $i - self::LAB_COUNT - 1;

            $scheduledDate = Carbon::create(2026, 5, 1, 0, 0, 0, $tz)
                ->addDays(($i - 1) % 20);

            $examIds = $isLab
                ? $this->labExamIdsForIndex($indexInType, $labExams)
                : [$imagingExams[$indexInType % $imagingExams->count()]->id];

            $equipmentId = $isLab
                ? null
                : Exam::resolveImagingEquipmentIdForOrder('imagen', $examIds);

            if (! $order) {
                $order = Order::withoutEvents(function () use (
                    $patients,
                    $i,
                    $medicoA,
                    $medicoB,
                    $receptionist,
                    $bioquimico,
                    $tecnologo,
                    $isLab,
                    $type,
                    $orderNumber,
                    $scheduledDate,
                    $equipmentId,
                    $indexInType,
                ) {
                    return Order::create([
                        'patient_id' => $patients[($i - 1) % $patients->count()]->id,
                        'doctor_id' => $i % 2 === 1 ? $medicoA->id : $medicoB->id,
                        'order_number' => $orderNumber,
                        'status' => $this->statusForIndex($indexInType),
                        'type' => $type,
                        'scheduled_date' => $scheduledDate->toDateString(),
                        'scheduled_time' => self::SCHEDULED_TIMES[$indexInType % count(self::SCHEDULED_TIMES)],
                        'receptionist_id' => $receptionist->id,
                        'responsible_user_id' => $isLab ? $bioquimico->id : $tecnologo->id,
                        'equipment_id' => $equipmentId,
                    ]);
                });

                $order->exams()->sync($examIds);
                $created++;
            }

            $order = $order->fresh(['exams', 'invoice']);
            $invoice = Invoice::ensurePendingForOrder($order) ?? $order->invoice;
            if ($invoice && $invoice->status !== 'pagada') {
                $this->registerDemoPayment(
                    $invoice,
                    $receptionist,
                    $i % 2 === 1 ? $efectivo : $qr,
                );
                $paid++;
            }
        }

        $this->command?->info("✅ OrdersDemoSeeder: {$created} órdenes creadas ({$skipped} ya existían por número).");
        $this->command?->info("   Comprobantes pagados en esta corrida: {$paid} (Efectivo/QR alternados).");
        $this->command?->info('   Lab: '.self::LAB_COUNT.' (responsable bioquimico@tecnoweb.shop)');
        $this->command?->info('   Imagen: '.self::IMAGING_COUNT.' (responsable tecnologo@tecnoweb.shop)');
        $this->command?->info('   Fechas: 01/05/2026 – 20/05/2026 · Prefijo: '.self::ORDER_NUMBER_PREFIX);
    }

    private function requireUser(string $email, string $label): User
    {
        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            throw new \RuntimeException("OrdersDemoSeeder: no existe usuario {$label} ({$email}). Ejecute AuthSeeder.");
        }

        return $user;
    }

    /**
     * @return Collection<int, Patient>
     */
    private function resolvePatients(): Collection
    {
        $patients = collect();

        foreach (self::PATIENT_CIS as $ci) {
            $patient = Patient::query()->where('ci', $ci)->first();
            if ($patient) {
                $patients->push($patient);
            }
        }

        if ($patients->isEmpty()) {
            throw new \RuntimeException(
                'OrdersDemoSeeder: no hay pacientes. Registre al menos uno o ejecute PatientsDemoSeeder.'
            );
        }

        return $patients;
    }

    /**
     * @return Collection<int, Exam>
     */
    private function loadLabExams(): Collection
    {
        return Exam::query()
            ->where('type', 'laboratorio')
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, Exam>
     */
    private function loadImagingExams(): Collection
    {
        return Exam::query()
            ->where('type', 'imagen')
            ->whereNotNull('imaging_equipment_id')
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, Exam>  $labExams
     * @return array<int, int>
     */
    private function labExamIdsForIndex(int $index, Collection $labExams): array
    {
        $count = $labExams->count();
        $howMany = ($index % 3) + 1;
        $ids = [];

        for ($j = 0; $j < $howMany; $j++) {
            $ids[] = (int) $labExams[($index + $j) % $count]->id;
        }

        return array_values(array_unique($ids));
    }

    private function statusForIndex(int $indexInType): string
    {
        if ($indexInType < 5) {
            return 'pendiente';
        }

        if ($indexInType < 10) {
            return 'en_proceso';
        }

        return 'completada';
    }

    /**
     * Registra pago y marca comprobante pagada (flujo real: observer + PDF).
     */
    private function registerDemoPayment(
        Invoice $invoice,
        User $cashier,
        PaymentMethod $method,
    ): void {
        $receiptService = app(PaymentReceiptService::class);

        DB::transaction(function () use ($invoice, $cashier, $method, $receiptService): void {
            $invoice = $invoice->fresh(['order']);
            if ($invoice->status === 'pagada') {
                return;
            }

            $paidAt = $invoice->order?->getScheduledDateTime() ?? now();

            Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total_amount,
                'payment_method_id' => $method->id,
                'paid_at' => $paidAt,
                'receipt_number' => 'RCP-'.$invoice->id.'-'.strtoupper(Str::random(4)),
                'cashier_user_id' => $cashier->id,
            ]);

            $invoiceUpdate = ['status' => 'pagada'];
            if (strcasecmp($method->name, 'QR') === 0) {
                $invoiceUpdate['libelula_status'] = 'pagado';
            }

            $invoice->update($invoiceUpdate);
            $invoice->refresh();
            $invoice->load(['payments.paymentMethod', 'payments.cashier', 'order.patient', 'order.exams']);

            try {
                $receiptService->generateAndStorePdf($invoice);
            } catch (\Throwable $e) {
                $this->command?->warn(
                    "OrdersDemoSeeder: PDF no generado para comprobante #{$invoice->id} ({$e->getMessage()})"
                );
            }
        });
    }
}
