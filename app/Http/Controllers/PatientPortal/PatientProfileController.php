<?php

namespace App\Http\Controllers\PatientPortal;

use App\Support\PortalProfilePasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatientProfileController
{
    use PortalProfilePasswordRules;

    public function show(Request $request): View
    {
        $user = $request->user();
        $patient = $user->patient;

        if (! $patient) {
            abort(403, 'No hay datos de paciente asociados a esta cuenta.');
        }

        return view('patient-portal.profile.index', compact('patient'));
    }

    public function edit(Request $request): View
    {
        $user = $request->user();
        $patient = $user->patient;

        if (! $patient) {
            abort(403, 'No hay datos de paciente asociados a esta cuenta.');
        }

        return view('patient-portal.profile.edit', compact('patient', 'user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $patient = $user->patient;

        if (! $patient) {
            abort(403, 'No hay datos de paciente asociados a esta cuenta.');
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('patients', 'email')->ignore($patient->id),
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'address' => ['nullable', 'string', 'max:500'],
            ...$this->optionalPasswordRules(),
        ], [
            'phone.required' => 'El teléfono es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.unique' => 'Ese correo ya está registrado en el sistema.',
            ...$this->optionalPasswordMessages(),
        ]);

        $patient->update([
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'address' => $validated['address'] ?? null,
        ]);

        $userPayload = ['email' => $validated['email']];

        if ($request->boolean('change_password') && filled($validated['password'] ?? null)) {
            $userPayload['password'] = Hash::make($validated['password']);
        }

        $user->update($userPayload);

        return redirect()
            ->route('patient.profile')
            ->with('status', 'Perfil actualizado correctamente.');
    }
}
