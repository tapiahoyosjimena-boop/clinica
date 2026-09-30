<?php

namespace App\Support;

class GenderOptions
{
    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'masculino' => 'Masculino',
            'femenino' => 'Femenino',
            'otro' => 'Otro',
        ];
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_keys(self::labels());
    }

    public static function label(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = strtolower(trim($value));

        return self::labels()[$normalized]
            ?? match ($normalized) {
                'm', 'male' => 'Masculino',
                'f', 'female' => 'Femenino',
                'other' => 'Otro',
                default => null,
            };
    }

    public static function validationRule(): string
    {
        return 'in:'.implode(',', self::values());
    }
}
