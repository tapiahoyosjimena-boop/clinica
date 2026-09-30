<?php

namespace App\Http\Controllers\PatientPortal;

use App\Domains\Payments\Models\Invoice;
use App\Domains\Results\Models\Result;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientDashboardController
{
    public function __invoke(Request $request): View
    {
        $user    = $request->user();
        $patient = $user->patient;

        $unreadNotifications = $user->unreadNotifications()->count();

        if (! $patient) {
            return view('patient-portal.dashboard', [
                'patient'             => null,
                'totalResults'        => 0,
                'totalInvoices'       => 0,
                'unreadNotifications' => $unreadNotifications,
                'recentResults'       => collect(),
            ]);
        }

        // Resultados publicados al portal
        $totalResults = Result::query()
            ->whereHas('order', fn ($q) => $q->where('patient_id', $patient->id))
            ->whereNotNull('published_to_portal_at')
            ->whereNotNull('validated_at')
            ->whereNotNull('pdf_path')
            ->count();

        // Comprobantes (facturas) del paciente
        $totalInvoices = Invoice::query()
            ->whereHas('order', fn ($q) => $q->where('patient_id', $patient->id))
            ->count();

        // Últimos 3 resultados disponibles
        $recentResults = Result::query()
            ->whereHas('order', fn ($q) => $q->where('patient_id', $patient->id))
            ->whereNotNull('published_to_portal_at')
            ->whereNotNull('validated_at')
            ->whereNotNull('pdf_path')
            ->with(['exam', 'order'])
            ->orderByDesc('published_to_portal_at')
            ->limit(3)
            ->get();

        return view('patient-portal.dashboard', compact(
            'patient',
            'totalResults',
            'totalInvoices',
            'unreadNotifications',
            'recentResults',
        ));
    }
}
