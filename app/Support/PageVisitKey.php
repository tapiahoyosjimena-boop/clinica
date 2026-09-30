<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Clave estable por pantalla (nombre de ruta preferido, si no path GET).
 */
final class PageVisitKey
{
    public static function shouldRecord(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->wantsJson()) {
            return false;
        }

        if ($request->hasHeader('X-Livewire')) {
            return false;
        }

        $path = $request->path();

        if (str_contains($path, 'livewire')) {
            return false;
        }

        if ($path === 'up' || str_starts_with($path, 'sanctum/')) {
            return false;
        }

        if (str_starts_with($path, '_debugbar')) {
            return false;
        }

        if (self::isPdfOrFileDownloadRequest($request)) {
            return false;
        }

        return true;
    }

    /**
     * No contabilizar respuestas típicas de PDF / descarga (no son “visitas” a una pantalla HTML).
     */
    private static function isPdfOrFileDownloadRequest(Request $request): bool
    {
        $path = strtolower($request->path());

        if (str_ends_with($path, '.pdf')) {
            return true;
        }

        if (preg_match('#/pdf(?:$|[/?])#', $path) === 1) {
            return true;
        }

        if (str_contains($path, '/pdf-preview/')) {
            return true;
        }

        $name = $request->route()?->getName();

        if (! is_string($name) || $name === '') {
            return false;
        }

        $lower = strtolower($name);

        if (str_ends_with($lower, '.pdf') || str_contains($lower, 'pdf-download') || str_contains($lower, 'preview-pdf')) {
            return true;
        }

        return in_array($name, [
            'results.doctor.pdf',
            'results.patient.pdf',
            'payments.invoices.pdf',
            'reportes.panel-preview-pdf',
        ], true);
    }

    public static function resolve(Request $request): ?string
    {
        if (! self::shouldRecord($request)) {
            return null;
        }

        $name = $request->route()?->getName();

        if (is_string($name) && $name !== '' && ! str_starts_with($name, 'livewire')) {
            return Str::limit($name, 180, '');
        }

        $path = ltrim($request->path(), '/');

        return $path !== '' ? Str::limit($path, 180, '') : null;
    }
}
