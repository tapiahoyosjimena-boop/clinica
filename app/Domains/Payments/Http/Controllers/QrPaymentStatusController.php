<?php

namespace App\Domains\Payments\Http\Controllers;

use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Models\PaymentMethod;
use App\Domains\Payments\Services\LibelulaService;
use App\Domains\Payments\Services\PaymentReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QrPaymentStatusController extends Controller
{
    public function __invoke(
        Invoice $invoice,
        LibelulaService $libelula,
        PaymentReceiptService $receiptService,
    ): JsonResponse {
        $invoice->refresh();

        // Respuesta rápida si el callback de Libélula ya lo procesó
        if ($invoice->status === 'pagada') {
            return response()->json(['pagada' => true, 'status' => 'pagada']);
        }

        // Si el callback no llegó (ej. desarrollo local), verificamos directamente con Libélula
        $codigoRecaudacion = $invoice->libelula_codigo_recaudacion;

        if (! $codigoRecaudacion) {
            return response()->json(['pagada' => false, 'status' => $invoice->status]);
        }

        try {
            $consulta = $libelula->consultarPago($codigoRecaudacion);

            if (! $libelula->esPagado($consulta)) {
                return response()->json(['pagada' => false, 'status' => $invoice->status]);
            }

            // Libélula confirma el pago — procesarlo igual que haría el callback
            DB::transaction(function () use ($invoice, $receiptService): void {
                $invoice = $invoice->fresh();

                if ($invoice->status === 'pagada') {
                    return;
                }

                $qrMethod = PaymentMethod::where('is_active', true)
                    ->whereRaw('LOWER(name) LIKE ?', ['%qr%'])
                    ->first();

                Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->total_amount,
                    'payment_method_id' => $qrMethod?->id,
                    'paid_at' => now(),
                    'receipt_number' => 'RCP-'.$invoice->id.'-'.strtoupper(Str::random(4)),
                    'cashier_user_id' => null,
                ]);

                $invoice->update([
                    'status' => 'pagada',
                    'libelula_status' => 'pagado',
                ]);

                $invoice->refresh();
                $invoice->load(['payments.paymentMethod', 'order.patient', 'order.exams']);
                $receiptService->generateAndStorePdf($invoice);
            });

            return response()->json(['pagada' => true, 'status' => 'pagada']);

        } catch (\Throwable $e) {
            Log::error('QrPaymentStatus: error verificando pago con Libélula', [
                'invoice_id' => $invoice->id,
                'codigo_recaudacion' => $codigoRecaudacion,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['pagada' => false, 'status' => $invoice->status]);
        }
    }
}
