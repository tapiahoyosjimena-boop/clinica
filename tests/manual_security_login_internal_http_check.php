<?php

/**
 * Prueba de bloqueo de login vía kernel HTTP interno (no requiere Laragon accesible).
 */

use App\Domains\Auth\Support\PortalWebLoginThrottle;
use Illuminate\Http\Request;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$email = 'lockout-internal-'.uniqid().'@example.invalid';
$password = 'wrong-'.uniqid();
$passed = 0;
$failed = 0;

$check = function (bool $ok, string $label) use (&$passed, &$failed): void {
    if ($ok) {
        echo "[OK] {$label}\n";
        $passed++;
    } else {
        echo "[FALLO] {$label}\n";
        $failed++;
    }
};

function extractCsrfFromHtml(string $html): ?string
{
    if (preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }

    return null;
}

function sessionCookieFromResponse($response): ?string
{
    foreach ($response->headers->getCookies() as $cookie) {
        if ($cookie->getName() === config('session.cookie')) {
            return $cookie->getValue();
        }
    }

    return null;
}

echo "=== 4. BLOQUEO LOGIN (HTTP interno Laravel, correo ficticio) ===\n";

$sessionCookie = null;
$token = null;

$get = Request::create('/paciente/login', 'GET');
$getResponse = $kernel->handle($get);
$check($getResponse->getStatusCode() === 200, 'GET /paciente/login → 200');
$token = extractCsrfFromHtml((string) $getResponse->getContent());
$sessionCookie = sessionCookieFromResponse($getResponse);
$check($token !== null && $sessionCookie !== null, 'Token CSRF y sesión obtenidos');

$blocked = false;
$sawRemaining = false;
$throttle = app(PortalWebLoginThrottle::class);

for ($i = 1; $i <= 6; $i++) {
    $post = Request::create('/paciente/login', 'POST', [
        '_token' => $token,
        'email' => $email,
        'password' => $password,
    ]);
    $post->headers->set('Accept', 'text/html');
    $post->cookies->set(config('session.cookie'), $sessionCookie);

    $postResponse = $kernel->handle($post);
    $newCookie = sessionCookieFromResponse($postResponse);
    if ($newCookie !== null) {
        $sessionCookie = $newCookie;
    }

    // Tras POST fallido Laravel redirige (302); los errores quedan en sesión flash.
    $follow = Request::create('/paciente/login', 'GET');
    $follow->cookies->set(config('session.cookie'), $sessionCookie);
    $pageResponse = $kernel->handle($follow);
    $body = (string) $pageResponse->getContent();

    $token = extractCsrfFromHtml($body) ?? $token;

    if (str_contains($body, 'Te quedan') && str_contains($body, 'credenciales no coinciden')) {
        $sawRemaining = true;
    }

    if (
        str_contains($body, 'Demasiados intentos fallidos')
        || str_contains($body, 'Has superado el límite')
        || str_contains($body, 'Tu acceso ha sido bloqueado temporalmente')
    ) {
        $blocked = true;
        echo "   -> Bloqueo detectado en intento #{$i}\n";
        break;
    }

    $kernel->terminate($post, $postResponse);
    $kernel->terminate($follow, $pageResponse);
}

// Verificación directa del throttle (misma clase que usa el login real)
$probe = Request::create('/paciente/login', 'POST', ['email' => $email]);
$probe->server->set('REMOTE_ADDR', '127.0.0.1');
$blockedMsg = $throttle->blockedMessage('patient', $probe, $email);
$check($blockedMsg !== null, 'PortalWebLoginThrottle confirma bloqueo para el correo de prueba');

$check($sawRemaining || $blocked, 'UI muestra avisos de intentos o bloqueo');
$check($blocked, 'Página de login muestra bloqueo temporal tras 5 fallos');

$kernel->terminate($get, $getResponse);

echo "\n=== RESUMEN HTTP INTERNO ===\n";
echo "Pasaron: {$passed} | Fallaron: {$failed}\n";
exit($failed > 0 ? 1 : 0);
