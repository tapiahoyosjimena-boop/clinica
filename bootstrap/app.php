<?php

use App\Http\Middleware\EnsureIsDoctor;
use App\Http\Middleware\EnsureIsPatient;
use App\Http\Middleware\EnsurePortalModulePermission;
use App\Http\Middleware\RecordPageVisit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'patient' => EnsureIsPatient::class,
            'doctor' => EnsureIsDoctor::class,
            'portal.permission' => EnsurePortalModulePermission::class,
        ]);

        $middleware->web(append: [
            RecordPageVisit::class,
        ]);

        /*
         * Sin ruta nombrada «login», el middleware auth y el manejador de AuthenticationException
         * caían en route('login') y rompían portales /paciente y /medico.
         * redirectGuestsTo registra el destino en Authenticate + AuthenticationException (Laravel 11).
         */
        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->is('paciente', 'paciente/*')) {
                return route('patient.login');
            }
            if ($request->is('medico', 'medico/*')) {
                return route('doctor.login');
            }

            $referer = $request->headers->get('referer');
            if (is_string($referer)) {
                $path = (string) (parse_url($referer, PHP_URL_PATH) ?? '');
                if (str_contains($path, '/medico')) {
                    return route('doctor.login');
                }
                if (str_contains($path, '/paciente')) {
                    return route('patient.login');
                }
            }

            return route('filament.admin.auth.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
