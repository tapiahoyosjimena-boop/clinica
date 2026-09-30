<?php

/**
 * Prueba HTTP de bloqueo de login (sin tocar contraseñas reales).
 * Usa un correo ficticio para no afectar cuentas existentes.
 */

$baseUrl = getenv('TEST_BASE_URL') ?: 'http://localhost:8000';
$loginPath = '/paciente/login';
$email = 'security-lockout-test-'.time().'@example.invalid';
$password = 'wrong-password-'.uniqid();

function httpRequest(string $method, string $url, ?string $cookieFile, ?array $post = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_TIMEOUT => 15,
    ]);

    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $headerSize = 0;
    if (is_string($raw) && preg_match("/\r\n\r\n/", $raw)) {
        $parts = explode("\r\n\r\n", $raw, 2);
        $headerSize = strlen($parts[0]) + 4;
    }

    $body = is_string($raw) ? substr($raw, $headerSize) : '';

    return ['code' => $code, 'body' => $body, 'error' => $err];
}

function extractCsrf(string $html): ?string
{
    if (preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }

    return null;
}

$cookieFile = tempnam(sys_get_temp_dir(), 'cn_login_test_');
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

echo "=== 4. BLOQUEO HTTP (portal paciente, correo ficticio) ===\n";
echo "URL base: {$baseUrl}\n";
echo "Email de prueba: {$email}\n";

$get = httpRequest('GET', $baseUrl.$loginPath, $cookieFile, null);

if ($get['error'] !== '') {
    echo "[SKIP] No se pudo conectar: {$get['error']}\n";
    echo "Asegurate de que Laragon sirve en {$baseUrl}\n";
    @unlink($cookieFile);
    exit(2);
}

$check($get['code'] === 200, 'GET /paciente/login responde 200');

$token = extractCsrf($get['body']);
$check($token !== null, 'Se obtuvo token CSRF del formulario');

$blockedDetected = false;
$attemptsRemainingSeen = false;

for ($i = 1; $i <= 6; $i++) {
    if ($token === null) {
        break;
    }

    $post = httpRequest('POST', $baseUrl.$loginPath, $cookieFile, [
        '_token' => $token,
        'email' => $email,
        'password' => $password,
    ]);

    $body = $post['body'];

    if (str_contains($body, 'Te quedan') || str_contains($body, 'bloquead') || str_contains($body, 'Demasiados intentos')) {
        $attemptsRemainingSeen = true;
    }

    if (
        str_contains($body, 'Demasiados intentos fallidos')
        || str_contains($body, 'bloqueado temporalmente')
        || str_contains($body, 'Has superado el límite')
    ) {
        $blockedDetected = true;
        echo "   -> Bloqueo detectado en intento #{$i}\n";
        break;
    }

    // Refrescar token tras redirect back
    $newToken = extractCsrf($body);
    if ($newToken !== null) {
        $token = $newToken;
    }
}

$check($attemptsRemainingSeen, 'Mensajes de intentos restantes visibles antes del bloqueo');
$check($blockedDetected, 'Tras varios fallos, login muestra bloqueo temporal');

@unlink($cookieFile);

echo "\n=== RESUMEN HTTP ===\n";
echo "Pasaron: {$passed} | Fallaron: {$failed}\n";
exit($failed > 0 ? 1 : 0);
