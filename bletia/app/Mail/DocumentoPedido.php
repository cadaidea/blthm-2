<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentoPedido extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<int,string> $adjuntos rutas absolutas de PDFs */
    public function __construct(
        public string $asunto,
        public string $cuerpoHtml,
        public array $adjuntos = []
    ) {}

    public function build()
    {
        $m = $this->subject($this->asunto)->html($this->cuerpoHtml);
        foreach ($this->adjuntos as $ruta) {
            if (is_string($ruta) && file_exists($ruta)) {
                $m->attach($ruta);
            }
        }
        return $m;
    }
}
