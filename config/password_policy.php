<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Política de contraseñas — Clínica Norte
    |--------------------------------------------------------------------------
    |
    | Punto único para longitud, complejidad y bloqueo por intentos fallidos
    | de login (panel /admin, /paciente/login y /medico/login).
    |
    | Los consumidores leen estos valores vía App\Support\PasswordPolicy.
    |
    */

    'min_length' => (int) env('PASSWORD_MIN_LENGTH', 8),

    'max_length' => (int) env('PASSWORD_MAX_LENGTH', 12),

    'require_uppercase' => filter_var(env('PASSWORD_REQUIRE_UPPERCASE', true), FILTER_VALIDATE_BOOL),

    'require_lowercase' => filter_var(env('PASSWORD_REQUIRE_LOWERCASE', true), FILTER_VALIDATE_BOOL),

    'require_number' => filter_var(env('PASSWORD_REQUIRE_NUMBER', true), FILTER_VALIDATE_BOOL),

    'require_symbol' => filter_var(env('PASSWORD_REQUIRE_SYMBOL', true), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Bloqueo temporal tras intentos fallidos de login
    |--------------------------------------------------------------------------
    */
    'login' => [
        'max_attempts' => (int) env('AUTH_LOGIN_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('AUTH_LOGIN_DECAY_SECONDS', 600),
    ],

];
