<?php
namespace App\Observers;

use App\Models\Bitacora;

/**
 * Observer genérico: registra creación/edición/borrado de cualquier modelo al que se asocie.
 * El nombre del módulo sale del basename de la clase.
 */
class BitacoraObserver
{
    protected function modulo($model): string
    {
        $map = [
            'PedidoEspecial' => 'Pedido', 'Recibo' => 'Recibo', 'Cliente' => 'Cliente',
            'Producto' => 'Producto', 'Proveedor' => 'Proveedor', 'Despacho' => 'Despacho',
            'Transportista' => 'Transportista', 'Local' => 'Local', 'User' => 'Usuario',
            'MateriaPrima' => 'Materia prima', 'MovimientoMaterial' => 'Movimiento material',
            'Variante' => 'Variante', 'Categoria' => 'Categoría',
        ];
        $b = class_basename($model);
        return $map[$b] ?? $b;
    }

    public function created($model): void
    {
        Bitacora::registrar('creó', $this->modulo($model), $model->id ?? null, $this->resumen($model));
    }

    public function updated($model): void
    {
        $cambios = method_exists($model, 'getChanges') ? array_keys($model->getChanges()) : [];
        $cambios = array_diff($cambios, ['updated_at']);
        $desc = $cambios ? 'Campos: ' . implode(', ', $cambios) : null;
        Bitacora::registrar('actualizó', $this->modulo($model), $model->id ?? null, $desc);
    }

    public function deleted($model): void
    {
        Bitacora::registrar('eliminó', $this->modulo($model), $model->id ?? null, $this->resumen($model));
    }

    protected function resumen($model): ?string
    {
        foreach (['folio', 'nombre', 'name', 'codigo', 'titulo'] as $campo) {
            if (! empty($model->$campo)) return (string) $model->$campo;
        }
        return null;
    }
}
