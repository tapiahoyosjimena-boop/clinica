<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Búsqueda LIKE case-insensitive para MySQL (LOWER + LIKE).
 */
final class DbLikeInsensitive
{
    /**
     * Aplica WHERE column LIKE pattern de forma case-insensitive.
     */
    public static function where(Builder $query, string $column, string $value): Builder
    {
        return $query->whereRaw('LOWER('.$column.') LIKE ?', ['%'.mb_strtolower($value).'%']);
    }

    /**
     * Aplica OR WHERE column LIKE pattern de forma case-insensitive.
     */
    public static function orWhere(Builder $query, string $column, string $value): Builder
    {
        return $query->orWhereRaw('LOWER('.$column.') LIKE ?', ['%'.mb_strtolower($value).'%']);
    }
}
