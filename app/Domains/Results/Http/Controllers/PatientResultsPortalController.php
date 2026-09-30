<?php

namespace App\Domains\Results\Http\Controllers;

use App\Domains\Auth\Support\SystemPermissions;
use App\Domains\Results\Models\Result;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Portal del paciente: listado de resultados validados y publicados (PDF disponible).
 */
class PatientResultsPortalController
{
    public function index(Request $request): View
    {
        $user = $request->user();
        if (! $user || ! $user->can(SystemPermissions::PATIENT_RESULTS)) {
            abort(403);
        }

        $patient = $user->patient;
        if (! $patient) {
            abort(403);
        }

        $results = Result::query()
            ->whereHas('order', fn ($q) => $q->where('patient_id', $patient->id))
            ->whereNotNull('published_to_portal_at')
            ->whereNotNull('validated_at')
            ->whereNotNull('pdf_path')
            ->with(['exam', 'order'])
            ->orderByDesc('published_to_portal_at')
            ->paginate(15);

        return view('results.patient-portal', [
            'results' => $results,
        ]);
    }
}
