<?php

namespace App\Domains\Results\Services;

use App\Domains\Imaging\Models\ImagingStudy;
use App\Domains\Imaging\Support\ImagingResultFileSupport;
use App\Domains\Results\Models\Result;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ResultPdfService
{
    public function generateAndStore(Result $result): void
    {
        $result->loadMissing([
            'order.patient',
            'exam',
            'responsibleUser',
            'details',
        ]);

        $isLab = $result->isLabType();
        $view = $isLab ? 'results.lab-pdf' : 'results.imaging-pdf';
        $folder = $isLab ? 'results/lab' : 'results/imaging';
        $path = $folder.'/'.$result->id.'-'.now()->format('YmdHis').'.pdf';
        $imagingAttachment = $isLab ? null : $this->resolveImagingAttachmentData($result);

        try {
            $pdfOutput = Pdf::loadView($view, [
                'result' => $result,
                'currency' => config('clinic_bank.currency', 'BOB'),
                'imagingAttachment' => $imagingAttachment,
            ])->setPaper('a4');

            Storage::disk('public')->put($path, $pdfOutput->output());
            $result->update(['pdf_path' => $path]);
        } catch (Throwable $e) {
            Log::error('ResultPdfService: PDF generation failed', [
                'result_id' => $result->id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @return array{type:string,filename:string,data_uri?:string}|null
     */
    protected function resolveImagingAttachmentData(Result $result): ?array
    {
        $study = ImagingStudy::query()
            ->where('order_id', $result->order_id)
            ->where('exam_id', $result->exam_id)
            ->where('status', 'completado')
            ->latest('id')
            ->first();

        if (! $study || ! $study->result_file || ! Storage::disk('public')->exists($study->result_file)) {
            return null;
        }

        $relativePath = $study->result_file;
        $fileName = basename($relativePath);
        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        if (! ImagingResultFileSupport::isAcceptedExtension($extension)) {
            return [
                'type' => 'unsupported',
                'filename' => $fileName,
            ];
        }

        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            default => 'image/webp',
        };

        return [
            'type' => 'image',
            'filename' => $fileName,
            'data_uri' => 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents(
                Storage::disk('public')->path($relativePath)
            )),
        ];
    }
}
