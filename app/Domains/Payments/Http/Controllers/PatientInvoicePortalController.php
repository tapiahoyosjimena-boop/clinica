<?php

namespace App\Domains\Payments\Http\Controllers;

use App\Domains\Auth\Support\SystemPermissions;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Services\AttentionSlipService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientInvoicePortalController
{
    public function index(Request $request, AttentionSlipService $attentionSlip): View
    {
        $user = $request->user();
        if (! $user || ! $user->can(SystemPermissions::PATIENT_PAYMENTS)) {
            abort(403);
        }

        $patient = $user->patient;
        if (! $patient) {
            abort(403);
        }

        $invoices = Invoice::query()
            ->whereHas('order', fn ($q) => $q->where('patient_id', $patient->id))
            ->with(['order.patient', 'order.exams.requirements', 'order.samples', 'order.imagingStudies'])
            ->orderByDesc('issued_at')
            ->paginate(15);

        // Solo se ofrece la Ficha de Atención cuando la orden tiene un paso presencial pendiente.
        $attentionSlipAvailable = $invoices->getCollection()->mapWithKeys(
            fn (Invoice $invoice) => [
                $invoice->id => $invoice->status === 'pagada'
                    && $invoice->order
                    && $attentionSlip->hasPendingAction($invoice->order),
            ]
        );

        return view('payments.patient-portal', [
            'invoices' => $invoices,
            'attentionSlipAvailable' => $attentionSlipAvailable,
        ]);
    }
}
