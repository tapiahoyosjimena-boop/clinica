<?php

use Spatie\Permission\DefaultTeamResolver;
use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\Role;

return [

    'models' => [

        /*
         * Al usar el trait "HasPermissions" de este paquete, necesitamos saber qué
         * modelo Eloquent debe usarse para recuperar tus permisos. Por lo general,
         * es simplemente el modelo "Permission", pero puedes usar el que prefieras.
         *
         * El modelo que quieras usar como modelo Permission debe implementar el
         * contrato `Spatie\Permission\Contracts\Permission`.
         */

        'permission' => Permission::class,

        /*
         * Al usar el trait "HasRoles" de este paquete, necesitamos saber qué
         * modelo Eloquent debe usarse para recuperar tus roles. Por lo general,
         * es simplemente el modelo "Role", pero puedes usar el que prefieras.
         *
         * El modelo que quieras usar como modelo Role debe implementar el
         * contrato `Spatie\Permission\Contracts\Role`.
         */

        'role' => Role::class,

    ],

    'table_names' => [

        /*
         * Al usar el trait "HasRoles" de este paquete, necesitamos saber qué
         * tabla debe usarse para recuperar tus roles. Hemos elegido un valor
         * predeterminado básico, pero puedes cambiarlo fácilmente por cualquier tabla.
         */

        'roles' => 'roles',

        /*
         * Al usar el trait "HasPermissions" de este paquete, necesitamos saber qué
         * tabla debe usarse para recuperar tus permisos. Hemos elegido un valor
         * predeterminado básico, pero puedes cambiarlo fácilmente por cualquier tabla.
         */

        'permissions' => 'permissions',

        /*
         * Al usar el trait "HasPermissions" de este paquete, necesitamos saber qué
         * tabla debe usarse para recuperar los permisos de tus modelos. Hemos elegido
         * un valor predeterminado básico, pero puedes cambiarlo fácilmente por cualquier tabla.
         */

        'model_has_permissions' => 'model_has_permissions',

        /*
         * Al usar el trait "HasRoles" de este paquete, necesitamos saber qué
         * tabla debe usarse para recuperar los roles de tus modelos. Hemos elegido
         * un valor predeterminado básico, pero puedes cambiarlo fácilmente por cualquier tabla.
         */

        'model_has_roles' => 'model_has_roles',

        /*
         * Al usar el trait "HasRoles" de este paquete, necesitamos saber qué
         * tabla debe usarse para recuperar los permisos de tus roles. Hemos elegido
         * un valor predeterminado básico, pero puedes cambiarlo fácilmente por cualquier tabla.
         */

        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        /*
         * Cambia esto si quieres nombrar las claves foráneas pivot de forma distinta a los valores predeterminados
         */
        'role_pivot_key' => null, // predeterminado 'role_id',
        'permission_pivot_key' => null, // predeterminado 'permission_id',

        /*
         * Cambia esto si quieres nombrar la clave primaria del modelo relacionado de forma distinta a
         * `model_id`.
         *
         * Por ejemplo, esto sería útil si tus claves primarias son todas UUID. En
         * ese caso, nómbrala `model_uuid`.
         */

        'model_morph_key' => 'model_id',

        /*
         * Cambia esto si quieres usar la funcionalidad de equipos y la clave foránea
         * de tu modelo relacionado es distinta de `team_id`.
         */

        'team_foreign_key' => 'team_id',
    ],

    /*
     * Cuando es true, el método para verificar permisos se registrará en el gate.
     * Establece esto en false si quieres implementar lógica personalizada para verificar permisos.
     */

    'register_permission_check_method' => true,

    /*
     * Cuando es true, se registrará el listener del evento Laravel\Octane\Events\OperationTerminated
     * que refrescará los permisos en cada TickTerminated, TaskTerminated y RequestTerminated.
     * NOTA: No debería ser necesario en la mayoría de los casos, pero una combinación Octane/Vapor se benefició de ello.
     */
    'register_octane_reset_listener' => false,

    /*
     * Se dispararán eventos cuando se asigne o desasigne un rol o permiso:
     * \Spatie\Permission\Events\RoleAttached
     * \Spatie\Permission\Events\RoleDetached
     * \Spatie\Permission\Events\PermissionAttached
     * \Spatie\Permission\Events\PermissionDetached
     *
     * Para habilitarlo, establece en true y luego crea listeners para observar estos eventos.
     */
    'events_enabled' => false,

    /*
     * Funcionalidad de equipos.
     * Cuando es true, el paquete implementa equipos usando 'team_foreign_key'.
     * Si quieres que las migraciones registren 'team_foreign_key', debes
     * establecer esto en true antes de ejecutar la migración.
     * Si ya ejecutaste la migración, debes crear una nueva migración para añadir
     * 'team_foreign_key' a 'roles', 'model_has_roles' y 'model_has_permissions'
     * (consulta la versión más reciente del archivo de migración de este paquete)
     */

    'teams' => false,

    /*
     * Clase a usar para resolver el id del equipo de permisos
     */
    'team_resolver' => DefaultTeamResolver::class,

    /*
     * Passport Client Credentials Grant
     * Cuando es true, el paquete usará el Client de Passport para verificar permisos
     */

    'use_passport_client_credentials' => false,

    /*
     * Cuando es true, los nombres de permisos requeridos se añaden a los mensajes de excepción.
     * Esto podría considerarse una filtración de información en algunos contextos, por lo que el valor
     * predeterminado es false aquí para una seguridad óptima.
     */

    'display_permission_in_exception' => false,

    /*
     * Cuando es true, los nombres de roles requeridos se añaden a los mensajes de excepción.
     * Esto podría considerarse una filtración de información en algunos contextos, por lo que el valor
     * predeterminado es false aquí para una seguridad óptima.
     */

    'display_role_in_exception' => false,

    /*
     * Por defecto, las búsquedas de permisos con comodín están deshabilitadas.
     * Consulta la documentación para entender la sintaxis soportada.
     */

    'enable_wildcard_permission' => false,

    /*
     * Clase a usar para interpretar permisos con comodín.
     * Si necesitas modificar delimitadores, sobrescribe la clase y especifica su nombre aquí.
     */
    // 'wildcard_permission' => Spatie\Permission\WildcardPermission::class,

    /* Configuración específica de caché */

    'cache' => [

        /*
         * Por defecto, todos los permisos se almacenan en caché durante 24 horas para mejorar el rendimiento.
         * Cuando se actualizan permisos o roles, la caché se vacía automáticamente.
         */

        'expiration_time' => DateInterval::createFromDateString('24 hours'),

        /*
         * Clave de caché usada para almacenar todos los permisos.
         */

        'key' => 'spatie.permission.cache',

        /*
         * Opcionalmente puedes indicar un driver de caché específico para el almacenamiento en caché
         * de permisos y roles usando cualquiera de los drivers `store` listados en el archivo de
         * configuración cache.php. Usar 'default' aquí significa usar el valor `default` definido en cache.php.
         */

        'store' => 'default',
    ],
];
