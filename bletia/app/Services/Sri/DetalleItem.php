<?php

namespace App\Services\Sri;

/** Arma la descripción enriquecida de un ítem de pedido (nombre + detalles de fabricación). */
class DetalleItem
{
    /**
     * Devuelve la descripción completa del ítem para el comprobante.
     * Formato: "Nombre — Tapiz: X, Lado: Y, Lacado: Z. Notas: ..."
     */
    public static function descripcion($item): string
    {
        $it = is_array($item) ? (object) $item : $item;
        $nombre = $it->nombre ?? 'Producto';
        $detalles = self::detalles($it);
        return $detalles ? ($nombre . ' — ' . $detalles) : $nombre;
    }

    /**
     * Solo la parte de detalles (sin el nombre), para mostrar como línea aparte.
     * Usa los campos individuales definitivos (tras aprobación), NO el campo 'variantes'
     * (que es el texto del momento de la venta y duplicaría la información).
     */
    public static function detalles($item): string
    {
        $it = is_array($item) ? (object) $item : $item;
        $partes = [];

        // atributos de fabricación definitivos (una sola fuente, sin duplicar)
        $map = [
            'tapiz_principal'    => 'Tapiz',
            'tapiz_secundario'   => 'Tapiz sec.',
            'lado'               => 'Lado',
            'lacado'             => 'Lacado',
            'cojines'            => 'Cojines',
            'cojines_secundario' => 'Cojines sec.',
        ];
        foreach ($map as $campo => $label) {
            if (! empty($it->$campo)) $partes[] = $label . ': ' . $it->$campo;
        }

        // si NO hay ningún atributo individual, recién ahí usar 'variantes' como respaldo
        if (empty($partes) && ! empty($it->variantes)) {
            $partes[] = $it->variantes;
        }

        $texto = implode(', ', $partes);

        // notas y motivo de ajuste como frase final
        $extras = [];
        if (! empty($it->notas_adicionales)) $extras[] = 'Notas: ' . $it->notas_adicionales;
        if (! empty($it->motivo_adicional)) $extras[] = 'Ajuste: ' . $it->motivo_adicional;
        if ($extras) $texto = trim($texto . '. ' . implode('. ', $extras), '. ');

        return $texto;
    }
}
