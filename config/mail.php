<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mailer predeterminado
    |--------------------------------------------------------------------------
    |
    | Esta opción controla el mailer predeterminado que se usa para enviar todos los
    | correos electrónicos, a menos que se especifique explícitamente otro mailer
    | al enviar el mensaje. Todos los mailers adicionales pueden configurarse
    | dentro del array "mailers". Se proporcionan ejemplos de cada tipo de mailer.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Configuraciones de mailers
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar todos los mailers usados por tu aplicación junto con
    | sus respectivas configuraciones. Se han configurado varios ejemplos para ti
    | y eres libre de añadir los tuyos según lo requiera tu aplicación.
    |
    | Laravel soporta una variedad de drivers de "transporte" de correo que pueden
    | usarse al entregar un email. Puedes especificar cuál estás usando para tus
    | mailers a continuación. También puedes añadir mailers adicionales si es necesario.
    |
    | Soportados: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "log", "array", "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'encryption' => env('MAIL_ENCRYPTION'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
            // Symfony Mailer: evita STARTTLS automático (útil con Postfix local en puerto 25).
            'auto_tls' => env('MAIL_AUTO_TLS', true),
            // Aceptar certificados autofirmados si STARTTLS sigue activo (solo desarrollo local).
            'verify_peer' => env('MAIL_VERIFY_PEER', true),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Dirección "From" global
    |--------------------------------------------------------------------------
    |
    | Puedes desear que todos los correos enviados por tu aplicación se envíen desde
    | la misma dirección. Aquí puedes especificar un nombre y dirección que se
    | usen globalmente para todos los correos enviados por tu aplicación.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

];
