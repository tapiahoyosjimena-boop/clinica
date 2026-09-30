<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Domains\Results\Models\Result;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorResultsController
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $results = Result::query()
            ->whereHas('order', fn ($q) => $q->where('doctor_id', $user->id))
            ->whereNotNull('validated_at')
            ->with(['exam', 'order.patient'])
            ->orderByDesc('validated_at')
            ->paginate(20);

        return view('doctor-portal.results', compact('results'));
    }
}
