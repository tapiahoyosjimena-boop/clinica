<?php

namespace App\Support;

use App\Models\PageVisit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class PageVisitRecorder
{
    public static function record(string $routeKey): void
    {
        try {
            DB::transaction(function () use ($routeKey): void {
                $row = PageVisit::query()->where('route_key', $routeKey)->lockForUpdate()->first();

                if ($row !== null) {
                    $row->increment('hits');

                    return;
                }

                PageVisit::query()->create([
                    'route_key' => $routeKey,
                    'hits' => 1,
                ]);
            });
        } catch (QueryException $e) {
            if (! self::isUniqueConstraintViolation($e)) {
                throw $e;
            }

            PageVisit::query()->where('route_key', $routeKey)->increment('hits');
        }
    }

    private static function isUniqueConstraintViolation(QueryException $e): bool
    {
        $code = (int) ($e->errorInfo[1] ?? 0);

        if ($code === 1062) {
            return true;
        }

        if (($e->errorInfo[0] ?? '') === '23505') {
            return true;
        }

        if ($code === 2627 || $code === 2601) {
            return true;
        }

        return str_contains(strtolower($e->getMessage()), 'unique');
    }
}
