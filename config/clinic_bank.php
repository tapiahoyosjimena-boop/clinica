<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Zona horaria de la clínica (citas, recolección, estudios)
    |--------------------------------------------------------------------------
    |
    | Fecha y hora programada en órdenes se guardan como reloj local del
    | consultorio. Debe coincidir con la zona usada en los DateTimePicker
    | de Filament para evitar desfases (ej. 10:00 en orden → 06:00 en muestra).
    |
    */
    'timezone' => env('CLINIC_TIMEZONE', env('APP_TIMEZONE', 'America/La_Paz')),

    /*
    |--------------------------------------------------------------------------
    | Datos bancarios para comprobantes (QR estático / transferencia)
    |--------------------------------------------------------------------------
    */
    'bank_name' => env('CLINIC_BANK_NAME', 'Banco'),
    'account_holder' => env('CLINIC_ACCOUNT_HOLDER', 'Clínica Norte'),
    'account_number' => env('CLINIC_ACCOUNT_NUMBER', ''),
    'account_type' => env('CLINIC_ACCOUNT_TYPE', 'Cuenta corriente'),
    'currency' => env('CLINIC_CURRENCY', 'BOB'),

];
