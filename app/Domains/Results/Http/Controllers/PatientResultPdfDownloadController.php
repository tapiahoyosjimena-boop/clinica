<?php

namespace App\Domains\Results\Http\Controllers;

use App\Domains\Results\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientResultPdfDownloadController
{
    public function __invoke(Request $request, Result $result): StreamedResponse
    {
        if (! $request->user()?->can('viewAsPatient', $result)) {
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
