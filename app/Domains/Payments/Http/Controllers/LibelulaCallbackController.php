<?php

namespace App\Domains\Payments\Http\Controllers;

use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Models\PaymentMethod;
use App\Domains\Payments\Services\LibelulaService;
use App\Domains\Payments\Services\PaymentReceiptService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LibelulaCallbackController extends Controller
{
    public function __invoke(
        Request $request,
        LibelulaService $libelula,
        PaymentReceiptService $receiptService,
    ) {
        $transactionId = $request->query('transaction_id');

        if (! $transactionId) {
            Log::warning('LibelulaCallback: llamada sin transaction_id');

            return response()->json(['error' => 'transaction_id requerido'], 400);
        }

        $invoice = Invoice::where('libelula_transaction_id', $transactionId)->first();

        if (! $invoice) {
            Log::warning('LibelulaCallback: no se encontró invoice para transaction_id', [
                'transaction_id' => $transactionId,
            ]);

            // Responder 200 para que Libélula no reintente indefinidamente.
            return response()->json(['ok' => true]);
        }

        // Idempotencia: si ya está pagada no se hace nada más.
        if ($invoice->status === 'pagada') {
            return response()->json(['ok' => true]);
        }

        // Verificar el estado real con Libélula (nunca confiar solo en el parámetro recibido).
        $codigoRecaudacion = $invoice->libelula_codigo_recaudacion;

        if (! $codigoRecaudacion) {
            // Sin código de recaudación no podemos verificar; asumir pagado si Libélula llamó.
            Log::warning('LibelulaCallback: sin codigo_recaudacion en invoice, se asume pagado', [
                'invoice_id' => $invoice->id,
            ]);
        } else {
            try {
                $consulta = $libelula->consultarPago($codigoRecaudacion);
            } catch (\Throwable $e) {
                Log::error('LibelulaCallback: error al consultar pago', [
                    'transaction_id' => $transactionId,
                    'codigo_recaudacion' => $codigoRecaudacion,
                    'message' => $e->getMessage(),
                ]);

                return response()->json(['error' => 'Error al verificar pago'], 500);
            }

            if (! $libelula->esPagado($consulta)) {
                $estado = $consulta['datos']['estado'] ?? $consulta['estado'] ?? 'no_confirmado';
                $invoice->update(['libelula_status' => $estado]);

                return response()->json(['ok' => true]);
            }
        }

        // El pago está confirmado: aplicar el mismo cierre que el pago manual.
        try {
            DB::transaction(function () use ($invoice, $receiptService): void {
                $invoice = $invoice->fresh();

                // Segunda verificación dentro de la transacción (prevenir condición de carrera).
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
                    'cashier_user_id' => null, // pago confirmado automáticamente por Libélula
                ]);

                $invoice->update([
                    'status' => 'pagada',
                    'libelula_status' => 'pagado',
                ]);

                $invoice->refresh();
                $invoice->load(['payments.paymentMethod', 'order.patient', 'order.exams']);
                $receiptService->generateAndStorePdf($invoice);
            });
        } catch (\Throwable $e) {
            Log::error('LibelulaCallback: error al cerrar el pago', [
                'transaction_id' => $transactionId,
                'invoice_id' => $invoice->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Error interno al procesar el pago'], 500);
        }

        Log::info('LibelulaCallback: pago confirmado y procesado', [
            'transaction_id' => $transactionId,
            'invoice_id' => $invoice->id,
        ]);

        return response()->json(['ok' => true]);
    }
}
