<?php

namespace App\Support;

use App\Domains\Patients\Models\Patient;

class ProfileDisplaySupport
{
    public static function initialsFromName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return '?';
        }

        $first = mb_substr($parts[0], 0, 1);
        $second = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : mb_substr($parts[0], 1, 1);

        return mb_strtoupper($first.$second);
    }

    public static function initialsFromPatient(Patient $patient): string
    {
        return mb_strtoupper(
            mb_substr($patient->first_name, 0, 1).mb_substr($patient->last_name, 0, 1)
        );
    }
}
