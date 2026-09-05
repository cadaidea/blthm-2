<?php

namespace App\Mail;

use App\Models\SriComprobante;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComprobanteSriMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SriComprobante $comprobante) {}

    public function envelope(): Envelope
    {
        $tipo = ['01' => 'Factura', '04' => 'Nota de Crédito', '05' => 'Nota de Débito', '06' => 'Guía de Remisión', '07' => 'Comprobante de Retención'][$this->comprobante->cod_doc] ?? 'Comprobante';
        $num = $this->comprobante->estab . '-' . $this->comprobante->pto_emi . '-' . $this->comprobante->secuencial;
        return new Envelope(subject: "$tipo electrónica $num");
    }

    public function content(): Content
    {
        return new Content(view: 'sri.correo', with: ['c' => $this->comprobante]);
    }

    public function attachments(): array
    {
        $a = [];
        $c = $this->comprobante;
        if ($c->pdf_path && is_file($c->pdf_path)) {
            $a[] = \Illuminate\Mail\Mailables\Attachment::fromPath($c->pdf_path)->as('Factura_' . $c->clave_acceso . '.pdf')->withMime('application/pdf');
        }
        // XML autorizado como adjunto
        $xml = $c->xml_autorizado ?: $c->xml_firmado;
        if ($xml) {
            $a[] = \Illuminate\Mail\Mailables\Attachment::fromData(fn () => $xml, $c->clave_acceso . '.xml')->withMime('application/xml');
        }
        return $a;
    }
}
