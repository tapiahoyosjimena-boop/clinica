<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Domains\Results\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DoctorResultPdfController
{
    public function __invoke(Request $request, Result $result): StreamedResponse
    {
        $user = $request->user();

        // Solo el médico derivante de la orden puede descargar el PDF
        if (! $result->order || $result->order->doctor_id !== $user->id) {
            abort(403);
        }

        if (! $result->pdf_path || ! Storage::disk('public')->exists($result->pdf_path)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $result->pdf_path,
            'resultado-'.$result->id.'.pdf'
        );
    }
}
