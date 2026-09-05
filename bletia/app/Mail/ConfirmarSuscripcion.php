<?php

namespace App\Mail;

use App\Models\Suscriptor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmarSuscripcion extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Suscriptor $suscriptor) {}

    public function build()
    {
        $url = url('/digest/confirm?sid=' . $this->suscriptor->id . '&token=' . $this->suscriptor->token);
        return $this->subject('Confirma tu suscripción · ' . config('tienda.marca', config('app.name')))
            ->view('emails.confirmar', ['url' => $url, 'suscriptor' => $this->suscriptor]);
    }
}
