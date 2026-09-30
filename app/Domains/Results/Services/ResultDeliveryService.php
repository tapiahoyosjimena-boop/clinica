<?php

namespace App\Domains\Results\Services;

use App\Domains\Notifications\Mail\ResultadosListosMail;
use App\Domains\Notifications\Notifications\ResultadosDisponiblesPacienteNotification;
use App\Domains\Patients\Models\Patient;
use App\Domains\Payments\Models\Delivery;
use App\Domains\Results\Models\Result;
use App\Support\BrevoMailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ResultDeliveryService
{
    /**
     * Tras confirmar un resultado: publica en el portal del paciente (marca de tiempo)
     * e intenta enviar el PDF por correo al paciente.
     *
     * @return array{portal_published: bool, email_sent: bool, email_skipped_reason: string|null}
     */
    public function publishAndNotify(Result $result): array
    {
        $result->loadMissing(['order.patient.user', 'exam']);

        $patient = $result->order?->patient;

        $out = [
            'portal_published' => false,
            'email_sent' => false,
            'email_skipped_reason' => null,
        ];

        if (! $patient) {
            $out['email_skipped_reason'] = 'Sin paciente asociado a la orden.';

            return $out;
        }

        $newlyPublishedToPortal = false;

        DB::transaction(function () use ($result, &$out, &$newlyPublishedToPortal): void {
            if ($result->published_to_portal_at === null) {
                $result->update(['published_to_portal_at' => now()]);
                $out['portal_published'] = true;
                $newlyPublishedToPortal = true;
            } else {
                $out['portal_published'] = true;
            }
        });

        $result->refresh();

        if (
            $newlyPublishedToPortal
            && $patient->user
            && $result->pdf_path
            && Storage::disk('public')->exists($result->pdf_path)
        ) {
            $patient->user->notify(new ResultadosDisponiblesPacienteNotification($result->fresh(['exam'])));
        }

        if (! $result->pdf_path || ! Storage::disk('public')->exists($result->pdf_path)) {
            $out['email_skipped_reason'] = 'No hay PDF generado para adjuntar.';

            return $out;
        }

        $email = $this->resolvePatientEmail($patient);
        if ($email === null || $email === '') {
            $out['email_skipped_reason'] = 'El paciente no tiene correo registrado.';

            return $out;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $out['email_skipped_reason'] = 'El correo del paciente no es válido.';

            return $out;
        }

        $alreadySent = Delivery::query()
            ->where('result_id', $result->id)
            ->where('channel', 'email')
            ->where('status', 'enviado')
            ->exists();

        if ($alreadySent) {
            $out['email_sent'] = true;
            $out['email_skipped_reason'] = null;

            return $out;
        }

        try {
            $mailable = new ResultadosListosMail($result);
            $html = (string) $mailable->render();
            BrevoMailer::send(
                $email,
                $mailable->envelope()->subject,
                $html,
                config('mail.from.name', 'Clínica Norte'),
                config('mail.from.address', 'hello@example.com')
            );

            Delivery::create([
                'result_id' => $result->id,
                'patient_id' => $patient->id,
                'invoice_id' => null,
                'channel' => 'email',
                'sent_at' => now(),
                'status' => 'enviado',
                'pdf_path' => $result->pdf_path,
            ]);

            $out['email_sent'] = true;
            $out['email_skipped_reason'] = null;
        } catch (Throwable $e) {
            Log::error('ResultDeliveryService: falló envío de correo', [
                'result_id' => $result->id,
                'patient_id' => $patient->id,
                'message' => $e->getMessage(),
            ]);

            try {
                Delivery::create([
                    'result_id' => $result->id,
                    'patient_id' => $patient->id,
                    'invoice_id' => null,
                    'channel' => 'email',
                    'sent_at' => now(),
                    'status' => 'fallido',
                    'pdf_path' => $result->pdf_path,
                ]);
            } catch (Throwable $inner) {
                Log::error('ResultDeliveryService: no se pudo registrar delivery fallido', [
                    'message' => $inner->getMessage(),
                ]);
            }

            $out['email_skipped_reason'] = 'No se pudo enviar el correo (se registró el fallo).';
        }

        return $out;
    }

    private function resolvePatientEmail(Patient $patient): ?string
    {
        $direct = trim((string) $patient->email);

        if ($direct !== '') {
            return $direct;
        }

        $fromUser = trim((string) ($patient->user?->email ?? ''));

        return $fromUser !== '' ? $fromUser : null;
    }
}
