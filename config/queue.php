<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nombre de conexión de cola predeterminada
    |--------------------------------------------------------------------------
    |
    | La cola de Laravel soporta una variedad de backends a través de una API
    | unificada, dándote acceso conveniente a cada backend usando una sintaxis
    | idéntica. La conexión de cola predeterminada se define a continuación.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Conexiones de cola
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar las opciones de conexión para cada backend de cola
    | usado por tu aplicación. Se proporciona una configuración de ejemplo para
    | cada backend soportado por Laravel. También eres libre de añadir más.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION', null),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Agrupación de trabajos
    |--------------------------------------------------------------------------
    |
    | Las siguientes opciones configuran la base de datos y la tabla que almacenan
    | la información de agrupación de trabajos. Estas opciones pueden actualizarse
    | a cualquier conexión y tabla de base de datos definida por tu aplicación.
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trabajos de cola fallidos
    |--------------------------------------------------------------------------
    |
    | Estas opciones configuran el comportamiento del registro de trabajos de cola
    | fallidos para que puedas controlar cómo y dónde se almacenan los trabajos
    | fallidos. Laravel incluye soporte para almacenar trabajos fallidos en un
    | archivo simple o en una base de datos.
    |
    | Drivers soportados: "database-uuids", "dynamodb", "file", "null"
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

];
