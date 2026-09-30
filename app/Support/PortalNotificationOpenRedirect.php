<?php

namespace App\Support;

use App\Domains\Results\Models\Result;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Resuelve la URL de destino al abrir una notificación desde el portal médico o del paciente,
 * evitando redirecciones a /admin/* que provocarían 403 y mapeando resultados a rutas del portal.
 */
final class PortalNotificationOpenRedirect
{
    public static function targetForDoctor(User $user, ?string $storedUrl, string $fallback): string
    {
        $path = self::pathFromStoredUrl($storedUrl);
        if ($path === null) {
            return $fallback;
        }

        if (preg_match('#^/admin/results/(\d+)#', $path, $m)) {
            $result = Result::query()->with('order')->find((int) $m[1]);
            if ($result?->order && (int) $result->order->doctor_id === (int) $user->id) {
                if ($result->pdf_path && Storage::disk('public')->exists($result->pdf_path)) {
                    return route('results.doctor.pdf', ['result' => $result->id]);
                }

                return route('doctor.results');
            }

            return $fallback;
        }

        if (preg_match('#^/admin/orders/\d+#', $path)) {
            return route('doctor.patients');
        }

        if (str_starts_with($path, '/admin')) {
            return $fallback;
        }

        return self::sameApplicationUrl($storedUrl, $path, $fallback);
    }

    public static function targetForPatient(User $user, ?string $storedUrl, string $fallback): string
    {
        $path = self::pathFromStoredUrl($storedUrl);
        if ($path === null) {
            return $fallback;
        }

        $patientId = $user->patient?->id;

        if (preg_match('#^/admin/results/(\d+)#', $path, $m) && $patientId) {
            $result = Result::query()->with('order')->find((int) $m[1]);
            if ($result?->order && (int) $result->order->patient_id === (int) $patientId) {
                if ($result->pdf_path && Storage::disk('public')->exists($result->pdf_path)) {
                    return route('results.patient.pdf', ['result' => $result->id]);
                }

                return route('results.patient.portal');
            }

            return $fallback;
        }

        if (str_starts_with($path, '/admin')) {
            return $fallback;
        }

        return self::sameApplicationUrl($storedUrl, $path, $fallback);
    }

    private static function pathFromStoredUrl(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }
        $u = trim($url);
        if (str_starts_with($u, '/')) {
            return parse_url($u, PHP_URL_PATH) ?: '/';
        }

        $parsed = parse_url($u);
        if (! is_array($parsed) || empty($parsed['scheme'])) {
            return null;
        }

        $host = $parsed['host'] ?? '';
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: '';
        if ($appHost !== '' && $host !== '' && strcasecmp($host, $appHost) !== 0) {
            return null;
        }

        return isset($parsed['path']) && $parsed['path'] !== '' ? $parsed['path'] : '/';
    }

    private static function sameApplicationUrl(?string $original, string $path, string $fallback): string
    {
        if (str_starts_with($path, '/')) {
            if (str_starts_with($path, '//')) {
                return $fallback;
            }

            return url($path);
        }

        return $fallback;
    }
}
