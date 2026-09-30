<?php

namespace App\Domains\Notifications\Support;

use App\Domains\Catalog\Filament\Resources\ExamCategoryResource;
use App\Domains\Catalog\Filament\Resources\ExamResource;
use App\Domains\Imaging\Filament\Resources\ImagingEquipmentResource;
use App\Domains\Imaging\Filament\Resources\ImagingStudyResource;
use App\Domains\Orders\Filament\Resources\OrderResource;
use App\Domains\Payments\Filament\Resources\InvoiceResource;
use App\Domains\Results\Filament\Resources\ResultResource;
use App\Domains\Results\Models\Result;
use App\Domains\Samples\Filament\Resources\SampleResource;

/**
 * URLs del listado (índice) de recursos Filament del panel admin.
 * Las notificaciones en base de datos guardan esa URL en `data.url`; el panel usa ese valor al hacer clic en la fila (no Filament `Notification::fromArray()`, que no incluye `url`).
 */
final class FilamentAdminListUrls
{
    /**
     * Si en base de datos quedó guardada la URL de un registro (…/orders/5), devuelve la URL del listado.
     * Las URLs ya de índice se devuelven sin cambios.
     */
    public static function normalizeStoredUrl(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        foreach (self::listIndexUrls() as $listUrl) {
            $collapsed = self::collapseDetailPathToList($stored, $listUrl);
            if ($collapsed !== null) {
                return $collapsed;
            }
        }

        return $stored;
    }

    /** @return list<string> */
    private static function listIndexUrls(): array
    {
        return [
            self::orders(),
            self::samples(),
            self::imagingStudies(),
            self::imagingEquipment(),
            self::invoices(),
            self::results(),
            self::examCategories(),
            self::exams(),
        ];
    }

    private static function collapseDetailPathToList(string $stored, string $listUrl): ?string
    {
        $storedPath = (string) (parse_url($stored, PHP_URL_PATH) ?? '');
        $listPath = (string) (parse_url($listUrl, PHP_URL_PATH) ?? '');

        $listPath = rtrim($listPath, '/');
        $storedPath = rtrim($storedPath, '/');

        if ($listPath === '' || ! str_starts_with($storedPath, $listPath.'/')) {
            return null;
        }

        $suffix = substr($storedPath, strlen($listPath) + 1);

        return ctype_digit($suffix) ? $listUrl : null;
    }

    public static function orders(): string
    {
        return OrderResource::getUrl();
    }

    public static function samples(): string
    {
        return SampleResource::getUrl();
    }

    public static function imagingStudies(): string
    {
        return ImagingStudyResource::getUrl();
    }

    public static function imagingEquipment(): string
    {
        return ImagingEquipmentResource::getUrl();
    }

    public static function invoices(): string
    {
        return InvoiceResource::getUrl();
    }

    public static function results(): string
    {
        return ResultResource::getUrl();
    }

    public static function resultEdit(Result $result): string
    {
        return ResultResource::getUrl('edit', ['record' => $result]);
    }

    public static function examCategories(): string
    {
        return ExamCategoryResource::getUrl();
    }

    public static function exams(): string
    {
        return ExamResource::getUrl();
    }
}
