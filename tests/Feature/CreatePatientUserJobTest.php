<?php

use App\Domains\Patients\Jobs\CreatePatientUserJob;
use App\Domains\Patients\Models\Patient;
use App\Models\User;
use App\Support\BrevoMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('mail.from.name', 'Clínica Norte');
    config()->set('mail.from.address', 'hello@example.com');
});

it('sends a welcome email even when the patient already belongs to an existing user', function () {
    $existingUser = User::factory()->create([
        'email' => 'existing.patient@example.com',
    ]);

    $sentEmails = [];

    $this->app->bind(BrevoMailer::class, function () use (&$sentEmails) {
        return new class($sentEmails) extends BrevoMailer {
            public function __construct(private array &$sentEmails) {}

            public function sendMessage(string $to, string $subject, string $htmlContent, ?string $fromName = null, ?string $fromEmail = null): array
            {
                $this->sentEmails[] = [
                    'to' => $to,
                    'subject' => $subject,
                    'html' => $htmlContent,
                    'fromName' => $fromName,
                    'fromEmail' => $fromEmail,
                ];

                return ['status_code' => 201, 'response' => 'ok'];
            }
        };
    });

    $patient = Patient::create([
        'ci' => '12345678',
        'first_name' => 'Ana',
        'last_name' => 'Pérez',
        'birth_date' => '1990-01-01',
        'gender' => 'F',
        'phone' => '77777777',
        'email' => $existingUser->email,
        'address' => 'Av. Principal 123',
        'medical_history_notes' => 'Sin antecedentes',
    ]);

    $job = new CreatePatientUserJob($patient);
    $job->handle();

    expect($patient->fresh()->user_id)->toBe($existingUser->id);
    expect($existingUser->fresh()->hasRole('Paciente'))->toBeTrue();
    expect($sentEmails)->toHaveCount(1);
    expect($sentEmails[0]['to'])->toBe($existingUser->email);
    expect($sentEmails[0]['subject'])->toContain('Bienvenido');
    expect($sentEmails[0]['html'])->toContain('Clínica Norte');
});
