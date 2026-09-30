<?php

namespace App\Support;

trait PortalProfilePasswordRules
{
    /**
     * @return array<string, mixed>
     */
    protected function optionalPasswordRules(): array
    {
        return [
            'change_password' => ['nullable'],
            'password' => array_merge(
                ['nullable', 'required_if:change_password,1,true'],
                PasswordPolicy::rules(required: false, confirmed: true),
            ),
            'password_confirmation' => [
                'nullable',
                'required_if:change_password,1,true',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function optionalPasswordMessages(): array
    {
        return PasswordPolicy::validationMessages();
    }
}
