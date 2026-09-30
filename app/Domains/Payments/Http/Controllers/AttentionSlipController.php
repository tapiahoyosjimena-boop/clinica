<?php

namespace App\Domains\Payments\Http\Controllers;

use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Services\AttentionSlipService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttentionSlipController
{
    public function __invoke(Request $request, Invoice $invoice, AttentionSlipService $attentionSlip): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $patientOk = $user->can('downloadPdfAsPatient', $invoice);
        $staffOk = $user->can('view', $invoice);

        if (! $patientOk && ! $staffOk) {
            abort(403);
        }

        $invoice->loadMissing(['order.patient']);

        if (! $invoice->order) {
            abort(404);
        }

        $data = $attentionSlip->buildForOrder($invoice->order);

        $pdf = Pdf::loadView('payments.attention-slip-pdf', [
            'invoice' => $invoice,
            'order' => $invoice->order,
            'data' => $data,
            'bank' => config('clinic_bank'),
        ])->setPaper('a4');

        return $pdf->stream('ficha-atencion-'.$invoice->invoice_number.'.pdf');
    }
}
