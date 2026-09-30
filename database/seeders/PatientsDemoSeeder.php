<?php

namespace Database\Seeders;

use App\Domains\Patients\Jobs\CreatePatientUserJob;
use App\Domains\Patients\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Datos demo de pacientes para VPS (no se ejecuta en DatabaseSeeder por defecto).
 *
 * Ejecutar: php artisan db:seed --class=PatientsDemoSeeder --force
 *
 * No modifica pacientes ya existentes (p. ej. CI 12345678, 9686907).
 * Crea o actualiza 3 fichas demo identificadas por CI 70123401–70123403.
 */
class PatientsDemoSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'Paciente@2026!';

    /** CI de pacientes demo para reutilizar en OrdersDemoSeeder, etc. */
    public const CI_MARIA = '70123401';

    public const CI_CARLOS = '70123402';

    public const CI_SOFIA = '70123403';

    private const DEMO_NOTE = 'Paciente de demostración (VPS) — PatientsDemoSeeder.';

    public function run(): void
    {
        Mail::fake();

        $definitions = [
            [
                'ci' => self::CI_MARIA,
                'first_name' => 'María Elena',
                'last_name' => 'Vargas Ríos',
                'birth_date' => '1998-03-14',
                'gender' => 'femenino',
                'phone' => '70111234',
                'email' => 'paciente@tecnoweb.shop',
                'address' => 'Av. Beni #450, entre 2do y 3er anillo, Santa Cruz',
                'medical_history_notes' => self::DEMO_NOTE.' Sin alergias medicamentosas conocidas.',
            ],
            [
                'ci' => self::CI_CARLOS,
                'first_name' => 'Carlos Alberto',
                'last_name' => 'Mendoza Salazar',
                'birth_date' => '1985-11-22',
                'gender' => 'masculino',
                'phone' => '72233445',
                'email' => 'paciente1@tecnoweb.shop',
                'address' => 'Calle Ñuflo de Chávez #1280, Cochabamba',
                'medical_history_notes' => self::DEMO_NOTE.' Hipertensión controlada.',
            ],
            [
                'ci' => self::CI_SOFIA,
                'first_name' => 'Sofía Patricia',
                'last_name' => 'Quispe Mamani',
                'birth_date' => '2000-07-08',
                'gender' => 'femenino',
                'phone' => '67894561',
                'email' => 'paciente2@tecnoweb.shop',
                'address' => 'Zona Villa Fátima, Calle 15 de Abril, La Paz',
                'medical_history_notes' => self::DEMO_NOTE.' Asma leve en infancia.',
            ],
        ];

        foreach ($definitions as $data) {
            $this->seedPatient($data);
        }

        $this->command?->info('✅ PatientsDemoSeeder: 3 pacientes demo listos (portal + panel).');
        $this->command?->info('   Correos: paciente@tecnoweb.shop, paciente1@tecnoweb.shop, paciente2@tecnoweb.shop');
        $this->command?->info('   Contraseña portal (los 3): '.self::DEMO_PASSWORD);
        $this->command?->info('   CI: '.self::CI_MARIA.', '.self::CI_CARLOS.', '.self::CI_SOFIA);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function seedPatient(array $data): void
    {
        $ci = (string) $data['ci'];

        $patient = Patient::withoutEvents(function () use ($ci, $data) {
            return Patient::updateOrCreate(
                ['ci' => $ci],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'birth_date' => $data['birth_date'],
                    'gender' => $data['gender'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'address' => $data['address'],
                    'medical_history_notes' => $data['medical_history_notes'],
                ]
            );
        });

        if (! $patient->user_id) {
            (new CreatePatientUserJob($patient))->handle();
            $patient->refresh();
        }

        $user = $patient->user_id
            ? User::query()->find($patient->user_id)
            : User::query()->where('email', $data['email'])->first();

        if ($user) {
            if (! $patient->user_id) {
                $patient->updateQuietly(['user_id' => $user->id]);
            }

            $user->syncRoles(['Paciente']);
            \App\Domains\Auth\Services\UserPermissionSync::clearDirectPermissions($user);
            $user->forceFill([
                'name' => $patient->first_name.' '.$patient->last_name,
                'email' => $patient->email,
                'password' => Hash::make(self::DEMO_PASSWORD),
            ])->saveQuietly();
        }

        $this->command?->line("   · {$patient->full_name} — CI {$ci} — {$patient->email}");
    }
}
