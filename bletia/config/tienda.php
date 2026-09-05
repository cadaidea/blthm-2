<?php

return [
    // IVA general Ecuador (configurable: cambió a 15% en 2024). Ajusta si cambia.
    'iva'     => env('TIENDA_IVA', 15),
    'moneda'  => 'USD',
    'marca'   => env('TIENDA_MARCA', 'Bletia Seridea'),
    'eslogan' => env('TIENDA_ESLOGAN', 'Muebles y decoración'),
    'autor'   => 'Cada Idea',
    // Datos para schema.org Organization
    'telefono' => env('TIENDA_TELEFONO', ''),
    'ciudad'   => env('TIENDA_CIUDAD', 'Cuenca'),
    'pais'     => 'EC',
    'logo'     => env('TIENDA_LOGO', ''),
];
