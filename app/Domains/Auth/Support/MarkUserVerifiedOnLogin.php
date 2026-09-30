<?php

namespace App\Domains\Auth\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Marca email_verified_at tras un inicio de sesión exitoso (columna «Verificado» en el admin).
 */
class MarkUserVerifiedOnLogin
{
    public static function apply(?Authenticatable $user): void
    {
        if (! $user instanceof User) {
            return;
        }

        if ($user->email_verified_at !== null) {
            return;
        }

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();
    }
}
