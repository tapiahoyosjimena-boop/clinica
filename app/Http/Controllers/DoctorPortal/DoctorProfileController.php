<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Support\GenderOptions;
use App\Support\PortalProfilePasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DoctorProfileController
{
    use PortalProfilePasswordRules;

    public function show(Request $request): View
    {
        $user = $request->user()->load('roles');

        return view('doctor-portal.profile', compact('user'));
    }

    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('doctor-portal.profile-edit', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'gender' => ['nullable', GenderOptions::validationRule()],
            ...$this->optionalPasswordRules(),
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.unique' => 'Ese correo ya está registrado en el sistema.',
            'gender.in' => 'Seleccione un sexo válido.',
            ...$this->optionalPasswordMessages(),
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'] ?? null,
        ];

        if ($request->boolean('change_password') && filled($validated['password'] ?? null)) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        return redirect()
            ->route('doctor.profile')
            ->with('status', 'Perfil actualizado correctamente.');
    }
}
