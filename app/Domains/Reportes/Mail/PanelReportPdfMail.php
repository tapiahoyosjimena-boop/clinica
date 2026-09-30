<?php

namespace App\Domains\Reportes\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PanelReportPdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $pdfBinary,
        public readonly string $attachmentFilename,
        public readonly string $subjectLine,
        public readonly string $reportTitle,
        public readonly string $reportDescription,
        public readonly string $periodLabel,
        public readonly ?string $generatedByName = null,
        public readonly ?int $rowCount = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'notifications.panel-report-pdf',
            with: [
                'reportTitle' => $this->reportTitle,
                'reportDescription' => $this->reportDescription,
                'periodLabel' => $this->periodLabel,
                'generatedByName' => $this->generatedByName,
                'rowCount' => $this->rowCount,
                'attachmentFilename' => $this->attachmentFilename,
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBinary, $this->attachmentFilename)
                ->withMime('application/pdf'),
        ];
    }
}
