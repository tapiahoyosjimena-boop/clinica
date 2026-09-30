<?php

namespace App\Domains\Payments\Services;

use App\Domains\Notifications\Notifications\ComprobanteDisponiblePacienteNotification;
use App\Domains\Payments\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PaymentReceiptService
{
    /**
     * Genera el PDF con DomPDF (sin wkhtmltopdf) y lo guarda en disco público.
     */
    public function generateAndStorePdf(Invoice $invoice): void
    {
        $invoice->loadMissing(['order.patient', 'order.exams', 'order.samples', 'payments.paymentMethod', 'payments.cashier']);

        $hadReceiptPdfBefore = filled($invoice->receipt_pdf_path);

        $path = 'payment-receipts/'.$invoice->id.'-'.now()->format('YmdHis').'.pdf';

        try {
            $pdf = Pdf::loadView('payments.receipt-pdf', [
                'invoice' => $invoice,
                'bank' => config('clinic_bank'),
            ])->setPaper('a4');

            Storage::disk('public')->put($path, $pdf->output());
            $invoice->update(['receipt_pdf_path' => $path]);
        } catch (Throwable $e) {
            Log::error('PaymentReceiptService: PDF generation failed', [
                'invoice_id' => $invoice->id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        if (
            ! $hadReceiptPdfBefore
            && Storage::disk('public')->exists($path)
        ) {
            $invoice->refresh();
            $invoice->loadMissing('order.patient.user');
            $user = $invoice->order?->patient?->user;
            if ($user) {
                $user->notify(new ComprobanteDisponiblePacienteNotification($invoice->fresh(['order'])));
            }
        }
    }
}
