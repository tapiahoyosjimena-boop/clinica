<?php

use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Valores predeterminados de autenticación
    |--------------------------------------------------------------------------
    |
    | Esta opción define el "guard" de autenticación predeterminado y el
    | "broker" de restablecimiento de contraseña de tu aplicación. Puedes
    | cambiar estos valores según sea necesario, pero son un buen punto de
    | partida para la mayoría de las aplicaciones.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards de autenticación
    |--------------------------------------------------------------------------
    |
    | A continuación, puedes definir cada guard de autenticación de tu aplicación.
    | Por supuesto, se ha definido una excelente configuración predeterminada
    | que utiliza almacenamiento de sesión junto con el proveedor de usuarios Eloquent.
    |
    | Todos los guards de autenticación tienen un proveedor de usuarios, que define
    | cómo se recuperan los usuarios de tu base de datos u otro sistema de
    | almacenamiento usado por la aplicación. Normalmente, se utiliza Eloquent.
    |
    | Soportados: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Proveedores de usuarios
    |--------------------------------------------------------------------------
    |
    | Todos los guards de autenticación tienen un proveedor de usuarios, que define
    | cómo se recuperan los usuarios de tu base de datos u otro sistema de
    | almacenamiento usado por la aplicación. Normalmente, se utiliza Eloquent.
    |
    | Si tienes múltiples tablas o modelos de usuarios, puedes configurar múltiples
    | proveedores para representar el modelo / tabla. Estos proveedores pueden
    | asignarse a cualquier guard de autenticación adicional que hayas definido.
    |
    | Soportados: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Restablecimiento de contraseñas
    |--------------------------------------------------------------------------
    |
    | Estas opciones de configuración especifican el comportamiento de la funcionalidad
    | de restablecimiento de contraseña de Laravel, incluyendo la tabla utilizada
    | para almacenar tokens y el proveedor de usuarios invocado para recuperar usuarios.
    |
    | El tiempo de expiración es el número de minutos que cada token de restablecimiento
    | será considerado válido. Esta característica de seguridad mantiene los tokens de
    | corta duración para que tengan menos tiempo de ser adivinados. Puedes cambiarlo según sea necesario.
    |
    | La configuración de throttle es el número de segundos que un usuario debe esperar
    | antes de generar más tokens de restablecimiento de contraseña. Esto evita que el
    | usuario genere rápidamente una cantidad muy grande de tokens de restablecimiento.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tiempo de espera de confirmación de contraseña
    |--------------------------------------------------------------------------
    |
    | Aquí puedes definir la cantidad de segundos antes de que expire la ventana
    | de confirmación de contraseña y se pida al usuario que vuelva a ingresar su
    | contraseña en la pantalla de confirmación. Por defecto, el tiempo de espera
    | dura tres horas.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

    /*
    |--------------------------------------------------------------------------
    | Login web: panel Filament y portales (paciente / médico)
    |--------------------------------------------------------------------------
    |
    | Los valores efectivos están en config/password_policy.php → login.
    | Variables de entorno: AUTH_LOGIN_MAX_ATTEMPTS, AUTH_LOGIN_DECAY_SECONDS.
    |
    | Este bloque se mantiene por compatibilidad; el código lee password_policy.
    */
    'login_throttle' => [
        'max_attempts' => (int) env('AUTH_LOGIN_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('AUTH_LOGIN_DECAY_SECONDS', 600),
    ],

];
