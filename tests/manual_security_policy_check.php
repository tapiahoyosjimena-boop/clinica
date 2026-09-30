<?php

/**
 * Verificación manual de política de seguridad (sin cambiar contraseñas reales).
 * Ejecutar: php tests/manual_security_policy_check.php
 */

use App\Support\PasswordPolicy;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$passed = 0;
$failed = 0;

function check(bool $ok, string $label): void
{
    global $passed, $failed;
    if ($ok) {
        echo "[OK] {$label}\n";
        $passed++;
    } else {
        echo "[FALLO] {$label}\n";
        $failed++;
    }
}

echo "=== 1. CONFIGURACION (password_policy.php) ===\n";
check(PasswordPolicy::minLength() === 8, 'Longitud minima = 8');
check(PasswordPolicy::maxLength() === 12, 'Longitud maxima = 12');
check(PasswordPolicy::loginMaxAttempts() === 5, 'Bloqueo: max intentos = 5');
check(PasswordPolicy::loginDecaySeconds() === 600, 'Bloqueo: ventana = 600 s');
check(count(PasswordPolicy::complexityRegexRules()) === 4, 'Complejidad: 4 reglas regex activas');

echo "\n=== 2. LONGITUD Y COMPLEJIDAD (Validator, sin guardar en BD) ===\n";

$validate = function (string $password): bool {
    return ! Validator::make(
        ['password' => $password],
        ['password' => PasswordPolicy::rules(required: true, confirmed: false)]
    )->fails();
};

check($validate('abc') === false, 'Rechaza "abc" (muy corta)');
check($validate('abcdefgh') === false, 'Rechaza "abcdefgh" (sin complejidad)');
check($validate('Abcdef1!') === true, 'Acepta "Abcdef1!" (8 chars, cumple todo)');
check($validate('Abcdef1!extra') === false, 'Rechaza 13 caracteres (supera max 12)');
check($validate('ABCDEF1!') === false, 'Rechaza sin minuscula');
check($validate('abcdef1!') === false, 'Rechaza sin mayuscula');
check($validate('Abcdefgh!') === false, 'Rechaza sin numero');
check($validate('Abcdefg1') === false, 'Rechaza sin simbolo');

echo "\n=== 3. BLOQUEO LOGIN (RateLimiter, sin credenciales reales) ===\n";

$testKey = 'security-test:'.uniqid('', true);
RateLimiter::clear($testKey);
$max = PasswordPolicy::loginMaxAttempts();
$decay = PasswordPolicy::loginDecaySeconds();

for ($i = 1; $i <= $max; $i++) {
    RateLimiter::hit($testKey, $decay);
}

check(
    RateLimiter::tooManyAttempts($testKey, $max),
    "Tras {$max} intentos fallidos simulados, cuenta bloqueada"
);

$remaining = RateLimiter::availableIn($testKey);
check($remaining > 0 && $remaining <= $decay, "Tiempo de bloqueo activo ({$remaining}s restantes de {$decay}s)");

RateLimiter::clear($testKey);
check(
    ! RateLimiter::tooManyAttempts($testKey, $max),
    'Tras limpiar rate limiter, bloqueo se levanta'
);

echo "\n=== RESUMEN ===\n";
echo "Pasaron: {$passed} | Fallaron: {$failed}\n";
exit($failed > 0 ? 1 : 0);
