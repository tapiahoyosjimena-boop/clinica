<?php

namespace App\Domains\Reportes\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

final class PanelReportPdfPreviewDownloadController
{
    public function __invoke(Request $request, string $token): Response
    {
        if ($token === '' || ! $request->user()) {
            abort(403);
        }

        $payload = Cache::pull('report_panel_pdf_preview:'.$token);
        if (! is_array($payload)) {
            abort(404, 'El enlace de descarga expiró o ya fue usado. Vuelva a pulsar «Descargar PDF».');
        }

        $userId = (int) ($payload['user_id'] ?? 0);
        if ($userId !== (int) $request->user()->id) {
            abort(403);
        }

        $binary = $payload['content'] ?? '';
        $filename = (string) ($payload['filename'] ?? 'reporte.pdf');
        if (! is_string($binary) || $binary === '') {
            abort(404);
        }

        $safeName = $this->safeAttachmentFilename($filename);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$safeName.'"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }

    private function safeAttachmentFilename(string $filename): string
    {
        $filename = basename(str_replace(["\0"], '', $filename));
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return 'reporte.pdf';
        }

        return preg_match('/\.pdf$/i', $filename) ? $filename : $filename.'.pdf';
    }
}
