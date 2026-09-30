<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Comprobar si la aplicación está en modo mantenimiento...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Registrar el autoloader de Composer...
require __DIR__.'/../vendor/autoload.php';

// Arrancar Laravel y procesar la petición...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
