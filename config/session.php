<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Driver de sesión predeterminado
    |--------------------------------------------------------------------------
    |
    | Esta opción determina el driver de sesión predeterminado que se utiliza para
    | las solicitudes entrantes. Laravel soporta una variedad de opciones de
    | almacenamiento para persistir datos de sesión. El almacenamiento en base de
    | datos es una excelente opción predeterminada.
    |
    | Soportados: "file", "cookie", "database", "apc",
    |            "memcached", "redis", "dynamodb", "array"
    |
    */

    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Duración de la sesión
    |--------------------------------------------------------------------------
    |
    | Aquí puedes especificar el número de minutos que deseas que la sesión
    | permanezca inactiva antes de expirar. Si quieres que expiren inmediatamente
    | al cerrar el navegador, puedes indicarlo mediante la opción expire_on_close.
    |
    */

    'lifetime' => env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    /*
    |--------------------------------------------------------------------------
    | Cifrado de sesión
    |--------------------------------------------------------------------------
    |
    | Esta opción te permite especificar fácilmente que todos los datos de sesión
    | deben cifrarse antes de almacenarse. Todo el cifrado lo realiza Laravel
    | automáticamente y puedes usar la sesión con normalidad.
    |
    */

    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Ubicación de archivos de sesión
    |--------------------------------------------------------------------------
    |
    | Al utilizar el driver de sesión "file", los archivos de sesión se colocan
    | en disco. La ubicación de almacenamiento predeterminada se define aquí; sin
    | embargo, eres libre de proporcionar otra ubicación donde deben almacenarse.
    |
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Conexión de base de datos de sesión
    |--------------------------------------------------------------------------
    |
    | Al usar los drivers de sesión "database" o "redis", puedes especificar una
    | conexión que debe usarse para gestionar estas sesiones. Esto debe
    | corresponder a una conexión en tus opciones de configuración de base de datos.
    |
    */

    'connection' => env('SESSION_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Tabla de base de datos de sesión
    |--------------------------------------------------------------------------
    |
    | Al usar el driver de sesión "database", puedes especificar la tabla que se
    | usará para almacenar sesiones. Por supuesto, se define un valor predeterminado
    | sensato; sin embargo, puedes cambiarlo por otra tabla.
    |
    */

    'table' => env('SESSION_TABLE', 'sessions'),

    /*
    |--------------------------------------------------------------------------
    | Almacén de caché de sesión
    |--------------------------------------------------------------------------
    |
    | Al usar uno de los backends de sesión basados en caché del framework, puedes
    | definir el almacén de caché que debe usarse para almacenar los datos de sesión
    | entre solicitudes. Esto debe coincidir con uno de tus almacenes de caché definidos.
    |
    | Afecta a: "apc", "dynamodb", "memcached", "redis"
    |
    */

    'store' => env('SESSION_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Lotería de limpieza de sesiones
    |--------------------------------------------------------------------------
    |
    | Algunos drivers de sesión deben limpiar manualmente su ubicación de almacenamiento
    | para eliminar sesiones antiguas. Aquí están las probabilidades de que esto ocurra
    | en una solicitud dada. Por defecto, las probabilidades son 2 de cada 100.
    |
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Nombre de cookie de sesión
    |--------------------------------------------------------------------------
    |
    | Aquí puedes cambiar el nombre de la cookie de sesión creada por el framework.
    | Normalmente, no deberías necesitar cambiar este valor ya que hacerlo no
    | proporciona una mejora de seguridad significativa.
    |
    |
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),

    /*
    |--------------------------------------------------------------------------
    | Ruta de cookie de sesión
    |--------------------------------------------------------------------------
    |
    | La ruta de la cookie de sesión determina la ruta para la cual la cookie será
    | considerada disponible. Normalmente, será la ruta raíz de tu aplicación, pero
    | eres libre de cambiarla cuando sea necesario.
    |
    */

    'path' => env('SESSION_PATH', '/'),

    /*
    |--------------------------------------------------------------------------
    | Dominio de cookie de sesión
    |--------------------------------------------------------------------------
    |
    | Este valor determina el dominio y subdominios para los cuales la cookie de
    | sesión está disponible. Por defecto, la cookie estará disponible para el
    | dominio raíz y todos los subdominios. Normalmente, esto no debería cambiarse.
    |
    */

    'domain' => env('SESSION_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Cookies solo HTTPS
    |--------------------------------------------------------------------------
    |
    | Al establecer esta opción en true, las cookies de sesión solo se enviarán de
    | vuelta al servidor si el navegador tiene una conexión HTTPS. Esto evitará
    | que la cookie se te envíe cuando no pueda hacerse de forma segura.
    |
    */

    'secure' => env('SESSION_SECURE_COOKIE'),

    /*
    |--------------------------------------------------------------------------
    | Solo acceso HTTP
    |--------------------------------------------------------------------------
    |
    | Establecer este valor en true impedirá que JavaScript acceda al valor de la
    | cookie y la cookie solo será accesible a través del protocolo HTTP. Es poco
    | probable que debas deshabilitar esta opción.
    |
    */

    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Cookies Same-Site
    |--------------------------------------------------------------------------
    |
    | Esta opción determina cómo se comportan tus cookies en solicitudes entre sitios
    | y puede usarse para mitigar ataques CSRF. Por defecto, establecemos este valor
    | en "lax" para permitir solicitudes seguras entre sitios.
    |
    | Ver: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#samesitesamesite-value
    |
    | Soportados: "lax", "strict", "none", null
    |
    */

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    /*
    |--------------------------------------------------------------------------
    | Cookies particionadas
    |--------------------------------------------------------------------------
    |
    | Establecer este valor en true vinculará la cookie al sitio de nivel superior en
    | un contexto entre sitios. Las cookies particionadas son aceptadas por el navegador
    | cuando están marcadas como "secure" y el atributo Same-Site está establecido en "none".
    |
    */

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];
