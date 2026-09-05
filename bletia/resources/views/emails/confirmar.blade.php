{!! \App\Support\CorreoBrand::wrap(
    'Confirma tu suscripción',
    '<p style="margin:0 0 14px">Gracias por sumarte a nuestro círculo. Queremos que cada novedad que recibas valga tu tiempo: ideas para tu espacio, piezas nuevas y detalles pensados con calma.</p>'
    . '<p style="margin:0">Solo falta un paso — confirma tu correo:</p>',
    [
        'preheader' => 'Confirma tu correo para recibir nuestras novedades',
        'cta' => ['text' => 'Confirmar suscripción', 'url' => $url],
        'footer_extra' => ' · Si no fuiste tú, ignora este mensaje.',
    ]
) !!}
