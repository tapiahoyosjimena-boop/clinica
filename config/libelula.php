<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Libélula — Pasarela de Pagos
    |--------------------------------------------------------------------------
    |
    | Credenciales y endpoints para la integración con la API de Libélula.
    | Configura LIBELULA_APPKEY y LIBELULA_API_URL en el archivo .env.
    |
    */

    'appkey' => env('LIBELULA_APPKEY'),

    'api_url' => env('LIBELULA_API_URL', 'https://api.libelula.bo'),

];
