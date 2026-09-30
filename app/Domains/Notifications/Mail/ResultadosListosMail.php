<?php

namespace App\Domains\Notifications\Mail;

use App\Domains\Results\Models\Result;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ResultadosListosMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Result $result) {}

    public function envelope(): Envelope
    {
        $examName = $this->result->exam?->name ?? 'examen';

        return new Envelope(
            subject: "Sus resultados de «{$examName}» están disponibles — Clínica Norte",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'notifications.resultados-listos',
            with: [
                'patientName' => $this->result->order?->patient?->full_name ?? 'Paciente',
                'examName' => $this->result->exam?->name ?? '—',
                'isCritical' => $this->result->is_critical,
                'portalUrl' => url('/paciente/resultados'),
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        if (! $this->result->pdf_path) {
            return [];
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($this->result->pdf_path)) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('public', $this->result->pdf_path)
                ->as('resultado-'.$this->result->id.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
