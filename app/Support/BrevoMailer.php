<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BrevoMailer
{
    public static function send(string $to, string $subject, string $htmlContent, ?string $fromName = null, ?string $fromEmail = null): array
    {
        return app(static::class)->sendMessage($to, $subject, $htmlContent, $fromName, $fromEmail);
    }

    public function sendMessage(string $to, string $subject, string $htmlContent, ?string $fromName = null, ?string $fromEmail = null): array
    {
        $useLaravelMailer = filter_var(env('USE_LARAVEL_MAILER', false), FILTER_VALIDATE_BOOLEAN);

        $apiKey = env('BREVO_API_KEY');

        if (empty($apiKey)) {
            return $this->sendViaLaravelMailer($to, $subject, $htmlContent, $fromName, $fromEmail);
        }

        try {
            $payload = [
                'sender' => [
                    'name' => $fromName ?? config('mail.from.name', 'Clínica Norte'),
                    'email' => $fromEmail ?? config('mail.from.address', 'hello@example.com'),
                ],
                'to' => [
                    ['email' => $to],
                ],
                'subject' => $subject,
                'htmlContent' => $htmlContent,
            ];

            $ch = curl_init('https://api.brevo.com/v3/smtp/email');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'accept: application/json',
                'api-key: '.$apiKey,
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($statusCode >= 400) {
                throw new \RuntimeException('Brevo API error: '.$statusCode.' '.$response);
            }

            return [
                'status_code' => $statusCode,
                'response' => $response,
            ];
        } catch (\Throwable $e) {
            Log::warning('Brevo API failed, falling back to Laravel mailer.', [
                'error' => $e->getMessage(),
                'to' => $to,
            ]);

            return $this->sendViaLaravelMailer($to, $subject, $htmlContent, $fromName, $fromEmail);
        }
    }

    protected function sendViaLaravelMailer(string $to, string $subject, string $htmlContent, ?string $fromName = null, ?string $fromEmail = null): array
    {
        Mail::html($htmlContent, function ($message) use ($to, $subject, $fromName, $fromEmail): void {
            $message->to($to)
                ->subject($subject);

            $message->from(
                $fromEmail ?? config('mail.from.address', 'hello@example.com'),
                $fromName ?? config('mail.from.name', 'Clínica Norte')
            );
        });

        return [
            'status_code' => 200,
            'response' => 'sent_via_laravel_mailer',
        ];
    }
}
