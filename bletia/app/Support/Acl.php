<?php
namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Control de visibilidad por rol en /dash.
 * Roles: admin, operaciones, vendedor, bodega, despacho, contabilidad, produccion.
 */
class Acl
{
    public const ROLES = [
        'admin'        => 'Administrador',
        'operaciones'  => 'Operaciones',
        'vendedor'     => 'Vendedor',
        'bodega'       => 'Bodega',
        'contabilidad' => 'Contabilidad',
        'produccion'   => 'Producción (taller)',
        'comunicacion' => 'Comunicación',
    ];

    protected const MATRIZ = [
        'operaciones' => [
            'ReclamoResource', 'CompraResource', 'ColaProduccion',
            'PedidoEspecialResource', 'ReciboResource', 'ClienteResource', 'ProductoResource',
            'ProveedorResource', 'DespachoResource', 'TransportistaResource', 'LocalResource',
            'MovimientoStockResource', 'CategoriaResource', 'AtributoResource', 'Inventario', 'PanelErp',
            'MateriaPrimaResource', 'ProduccionResource', 'SolicitudMaterialResource',
        ],
        'vendedor' => [
            'ReclamoResource',
            'PedidoEspecialResource', 'ReciboResource', 'ClienteResource', 'ProductoResource', 'VentaResource',
        ],
        'bodega' => [
            'PedidoEspecialResource', 'ProveedorResource', 'DespachoResource', 'TransportistaResource',
            'LocalResource', 'MovimientoStockResource', 'ProductoResource', 'CategoriaResource',
            'AtributoResource', 'Inventario', 'MateriaPrimaResource', 'SolicitudMaterialResource',
        ],
        'contabilidad' => [
            'ReciboResource', 'ClienteResource', 'PedidoEspecialResource',
            'CuentaResource', 'AsientoResource', 'GastoResource', 'CuentaMapeoResource',
            'ImpuestoResource', 'ReportesContables', 'EstadosFinancierosPage',
            'LibroTributarioPage', 'ChequesPorCobrar', 'VentaResource', 'CompraResource', 'ProveedorResource',
        ],
        'produccion' => [
            'ProduccionResource', 'MateriaPrimaResource', 'SolicitudMaterialResource', 'CompraResource', 'ColaProduccion',
        ],
        'comunicacion' => [
            'ArticuloResource', 'PaginaResource', 'BlogCategoriaResource', 'EtiquetaResource',
            'CampaniaResource', 'AutomatizacionResource', 'ListaResource', 'SuscriptorResource', 'BounceResource',
            'FormularioResource', 'RecursoResource', 'CuponResource',
        ],
    ];

    public static function rol(): string
    {
        $u = Auth::user();
        return $u && ! empty($u->rol) ? $u->rol : 'sin_acceso';
    }

    public static function esAdmin(): bool        { return self::rol() === 'admin'; }
    public static function esOperaciones(): bool   { return self::rol() === 'operaciones'; }
    public static function esVendedor(): bool      { return self::rol() === 'vendedor'; }
    public static function esContabilidad(): bool  { return self::rol() === 'contabilidad'; }
    public static function esProduccion(): bool    { return self::rol() === 'produccion'; }

    public static function puedeAprobar(): bool   { return in_array(self::rol(), ['admin', 'operaciones'], true); }
    // Compra a proveedor: administrativo (admin, operaciones, contabilidad). Producción NO gestiona compras.
    public static function puedeGestionarCompraProveedor(): bool { return in_array(self::rol(), ['admin', 'operaciones', 'contabilidad'], true); }
    // Orden de producción (taller): solo avanza SU fabricación (admin/operaciones para supervisar, produccion para fabricar).
    public static function puedeGestionarProduccionInterna(): bool { return in_array(self::rol(), ['admin', 'operaciones', 'produccion'], true); }
    public static function puedeRegistrarPago(): bool { return in_array(self::rol(), ['admin', 'operaciones', 'vendedor'], true); }
    // Generar guía de remisión: logística (no vendedor)
    public static function puedeGenerarGuia(): bool { return in_array(self::rol(), ['admin', 'operaciones', 'bodega', 'despacho'], true); }
    // Registrar entrega/retiro con firma: incluye vendedor (stock o retiro en local)
    public static function puedeEntregar(): bool { return in_array(self::rol(), ['admin', 'operaciones', 'bodega', 'despacho', 'vendedor'], true); }
    public static function puedeValidarPago(): bool { return in_array(self::rol(), ['admin', 'contabilidad', 'operaciones'], true); }
    public static function puedeResolverPago(): bool { return in_array(self::rol(), ['admin', 'operaciones', 'contabilidad'], true); }

    public static function ve(string $clase): bool
    {
        if (self::esAdmin()) return true;
        $base = class_basename($clase);
        return in_array($base, self::MATRIZ[self::rol()] ?? [], true);
    }

    /** Módulo contable: administrador y contador. */
    public static function puedeContabilidad(): bool { return in_array(self::rol(), ['admin', 'contabilidad'], true); }

    public static function puedeEliminar(): bool  { return self::esAdmin(); }

    public static function soloLectura(string $clase): bool
    {
        if (self::esAdmin()) return false;
        $base = class_basename($clase);
        if (self::esVendedor() && in_array($base, ['ProductoResource'], true)) return true;
        if (self::esContabilidad() && in_array($base, ['PedidoEspecialResource', 'ClienteResource'], true)) return true;
        return false;
    }
}
