<?php

namespace App\Mail;

use App\Models\Venta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotaVentaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Venta $venta, public string $pdfPath) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nota de venta ' . $this->venta->numero_comprobante);
    }

    public function content(): Content
    {
        return new Content(view: 'sri.correo-nota-venta', with: ['venta' => $this->venta]);
    }

    public function attachments(): array
    {
        if ($this->pdfPath && is_file($this->pdfPath)) {
            return [Attachment::fromPath($this->pdfPath)->as('NotaVenta_' . $this->venta->numero_comprobante . '.pdf')->withMime('application/pdf')];
        }
        return [];
    }
}
