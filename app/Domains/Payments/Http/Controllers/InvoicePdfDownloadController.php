<?php

namespace App\Domains\Payments\Http\Controllers;

use App\Domains\Payments\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoicePdfDownloadController
{
    public function __invoke(Request $request, Invoice $invoice): StreamedResponse|Response
    {
        if (! $invoice->receipt_pdf_path) {
            abort(404);
        }

        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $patientOk = $user->can('viewAsPatient', $invoice) && $invoice->status === 'pagada';
        $staffOk = $user->can('view', $invoice);

        if (! $patientOk && ! $staffOk) {
            abort(403);
        }

        if (! Storage::disk('public')->exists($invoice->receipt_pdf_path)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $invoice->receipt_pdf_path,
            'comprobante-'.$invoice->invoice_number.'.pdf'
        );
    }
}
