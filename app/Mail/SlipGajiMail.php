<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SlipGajiMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $detail,
        public string $pdfPath,
        public string $fileName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Slip Gaji '.$this->employeeName().' - '.$this->formattedPeriod()
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.slip-gaji',
            with: [
                'data' => $this->detail,
                'periodeText' => $this->formattedPeriod(),
            ]
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as($this->fileName)
                ->withMime('application/pdf'),
        ];
    }

    private function employeeName(): string
    {
        return trim((string) ($this->detail['nama'] ?? 'Pegawai')) ?: 'Pegawai';
    }

    private function formattedPeriod(): string
    {
        $periode = $this->detail['periode'] ?? null;

        if (! $periode) {
            return '-';
        }

        try {
            return Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y');
        } catch (\Throwable $e) {
            return (string) $periode;
        }
    }
}
