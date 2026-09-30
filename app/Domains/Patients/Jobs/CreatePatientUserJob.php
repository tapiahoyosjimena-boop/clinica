<?php

namespace App\Domains\Patients\Jobs;

use App\Domains\Patients\Mail\PatientWelcomeMail;
use App\Domains\Patients\Models\Patient;
use App\Models\User;
use App\Support\BrevoMailer;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Se ejecuta de forma síncrona al registrar un paciente (sin depender de queue:work).
 */
class CreatePatientUserJob
{
    use Queueable;

    public function __construct(public readonly Patient $patient) {}

    public function handle(): void
    {
        // Contraseña temporal: año + CN + últimos 4 dígitos de la CI
        $last4 = substr($this->patient->ci, -4);
        $tempPassword = now()->year.'CN'.$last4;

        $user = $this->patient->user_id !== null
            ? User::find($this->patient->user_id)
            : null;

        if (! $user && ! empty($this->patient->email)) {
            $user = User::where('email', $this->patient->email)->first();
        }

        if ($user) {
            $this->patient->updateQuietly(['user_id' => $user->id]);

            if (! $user->hasRole('Paciente')) {
                $user->assignRole('Paciente');
            }

            if (! $user->hasPermissionTo('results.access')) {
                $user->givePermissionTo('results.access');
            }

            $this->sendWelcomeEmail($user, $tempPassword);

            return;
        }

        // Crear el usuario
        $user = User::create([
            'name' => $this->patient->first_name.' '.$this->patient->last_name,
            'email' => $this->patient->email,
            'password' => Hash::make($tempPassword),
        ]);

        // Asignar rol Paciente y permisos directos necesarios para el portal
        $user->assignRole('Paciente');
        $user->givePermissionTo('results.access');

        // Vincular user_id al paciente (sin disparar observer de nuevo)
        $this->patient->updateQuietly(['user_id' => $user->id]);

        $this->sendWelcomeEmail($user, $tempPassword);
    }

    protected function sendWelcomeEmail(User $user, string $tempPassword): void
    {
        try {
            $mailable = new PatientWelcomeMail($this->patient, $tempPassword);
            $html = (string) $mailable->render();
            BrevoMailer::send(
                $user->email,
                $mailable->envelope()->subject,
                $html,
                config('mail.from.name', 'Clínica Norte'),
                config('mail.from.address', 'hello@example.com')
            );
        } catch (\Throwable $e) {
            Log::error('Error al enviar mail de bienvenida al paciente.', [
                'patient_id' => $this->patient->id,
                'email' => $this->patient->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
