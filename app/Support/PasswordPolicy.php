<?php

namespace App\Support;

final class PasswordPolicy
{
    public static function minLength(): int
    {
        return max(1, (int) config('password_policy.min_length', 8));
    }

    public static function maxLength(): int
    {
        return max(self::minLength(), (int) config('password_policy.max_length', 12));
    }

    public static function loginMaxAttempts(): int
    {
        return max(1, (int) config('password_policy.login.max_attempts', 5));
    }

    public static function loginDecaySeconds(): int
    {
        return max(1, (int) config('password_policy.login.decay_seconds', 600));
    }

    /**
     * @return list<string>
     */
    public static function complexityRegexRules(): array
    {
        $rules = [];

        if (config('password_policy.require_uppercase', true)) {
            $rules[] = 'regex:/[A-Z]/';
        }

        if (config('password_policy.require_lowercase', true)) {
            $rules[] = 'regex:/[a-z]/';
        }

        if (config('password_policy.require_number', true)) {
            $rules[] = 'regex:/[0-9]/';
        }

        if (config('password_policy.require_symbol', true)) {
            $rules[] = 'regex:/[\W_]/';
        }

        return $rules;
    }

    public static function hasComplexityRules(): bool
    {
        return self::complexityRegexRules() !== [];
    }

    /**
     * Reglas Laravel estándar para validar una contraseña nueva.
     *
     * @return list<string>
     */
    public static function rules(bool $required = true, bool $confirmed = false): array
    {
        $rules = [];

        if ($required) {
            $rules[] = 'required';
        }

        $rules[] = 'min:'.self::minLength();
        $rules[] = 'max:'.self::maxLength();
        $rules = array_merge($rules, self::complexityRegexRules());

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public static function validationMessages(): array
    {
        return [
            'password.required' => 'La contraseña es obligatoria.',
            'password.required_if' => 'Ingrese la nueva contraseña.',
            'password.min' => self::messageMin(),
            'password.max' => self::messageMax(),
            'password.regex' => self::messageComplexity(),
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password_confirmation.required' => 'Debes confirmar la contraseña.',
            'password_confirmation.required_if' => 'Debe confirmar la nueva contraseña.',
        ];
    }

    /**
     * Mensajes para componentes Filament (min_length, regex, etc.).
     *
     * @return array<string, string>
     */
    public static function filamentValidationMessages(): array
    {
        return [
            'required' => 'Ingrese la nueva contraseña.',
            'min_length' => self::messageMin(),
            'min' => self::messageMin(),
            'max_length' => self::messageMax(),
            'max' => self::messageMax(),
            'regex' => self::messageComplexity(),
            'same' => 'La confirmación de contraseña no coincide.',
        ];
    }

    public static function helperText(): string
    {
        $length = 'Mínimo '.self::minLength().' y máximo '.self::maxLength().' caracteres';

        if (! self::hasComplexityRules()) {
            return $length.'.';
        }

        return $length.': '.self::complexityDescription().'.';
    }

    public static function placeholderText(): string
    {
        return 'Mínimo '.self::minLength().' caracteres';
    }

    public static function messageMin(): string
    {
        return 'La contraseña debe tener al menos '.self::minLength().' caracteres.';
    }

    public static function messageMax(): string
    {
        return 'La contraseña no puede superar los '.self::maxLength().' caracteres.';
    }

    public static function messageComplexity(): string
    {
        if (! self::hasComplexityRules()) {
            return 'La contraseña no cumple los requisitos de complejidad.';
        }

        return 'La contraseña debe contener al menos '.self::complexityDescription().'.';
    }

    private static function complexityDescription(): string
    {
        $parts = [];

        if (config('password_policy.require_uppercase', true)) {
            $parts[] = 'una mayúscula';
        }

        if (config('password_policy.require_lowercase', true)) {
            $parts[] = 'una minúscula';
        }

        if (config('password_policy.require_number', true)) {
            $parts[] = 'un número';
        }

        if (config('password_policy.require_symbol', true)) {
            $parts[] = 'un símbolo especial';
        }

        if ($parts === []) {
            return 'los requisitos configurados';
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        $last = array_pop($parts);

        return implode(', ', $parts).' y '.$last;
    }
}
