<?php

return [
    // Credenciales de tu app WEB en Payphone Developer.
    'token'    => env('PAYPHONE_TOKEN', ''),
    'store_id' => env('PAYPHONE_STORE_ID', ''),

    // Endpoints oficiales de la Cajita de Pagos v2.0.
    'confirm_url' => env('PAYPHONE_CONFIRM_URL', 'https://pay.payphonetodoesposible.com/api/button/V2/Confirm'),

    'lang'     => 'es',
    'currency' => 'USD',
];
