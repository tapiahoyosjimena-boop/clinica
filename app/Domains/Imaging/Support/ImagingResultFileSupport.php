<?php

namespace App\Domains\Imaging\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ImagingResultFileSupport
{
    /** @var list<string> */
    public const ACCEPTED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/bmp',
    ];

    /** @var list<string> */
    public const ACCEPTED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
        'bmp',
    ];

    public static function isAcceptedExtension(string $extension): bool
    {
        return in_array(strtolower($extension), self::ACCEPTED_EXTENSIONS, true);
    }

    /**
     * Ruta web relativa al disco public (p. ej. /storage/imaging-results/archivo.jpg).
     * Usar en vistas embebidas del panel para no depender de APP_URL.
     */
    public static function publicWebUrl(string $relativePath): string
    {
        $relativePath = str_replace('\\', '/', ltrim($relativePath, '/'));

        return '/storage/'.$relativePath;
    }

    /**
     * @param  array<string, mixed>|string|null  $uploaded
     */
    public static function normalizeUploadedPath(array|string|null $uploaded): ?string
    {
        if (blank($uploaded)) {
            return null;
        }

        if (is_array($uploaded)) {
            $first = reset($uploaded);

            return is_string($first) && $first !== '' ? $first : null;
        }

        return is_string($uploaded) && $uploaded !== '' ? $uploaded : null;
    }

    public static function assertOptionalUploadIsImage(?string $relativePath, string $field = 'result_file'): void
    {
        if (blank($relativePath)) {
            return;
        }

        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        if (! self::isAcceptedExtension($extension)) {
            throw ValidationException::withMessages([
                $field => 'Solo se permiten archivos de imagen (JPG, PNG, WebP, GIF, BMP).',
            ]);
        }

        if (! Storage::disk('public')->exists($relativePath)) {
            return;
        }

        $mime = Storage::disk('public')->mimeType($relativePath);
        if ($mime !== null && $mime !== false && ! in_array($mime, self::ACCEPTED_MIME_TYPES, true)) {
            throw ValidationException::withMessages([
                $field => 'El archivo debe ser una imagen válida (JPG, PNG, WebP, GIF, BMP).',
            ]);
        }
    }
}
