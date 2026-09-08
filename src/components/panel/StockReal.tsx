import { useEffect, useState } from 'react';
import { useWarehousesStore, useInventoryStore, useProductsStore } from '../../store';
import { I, toast } from '../ui';
import { Card, SectionTitle, Td, Th, btnDark, btnGhost, inp } from './pui';
import type { Warehouse, InventoryMove } from '../../api/client';

export function StockReal() {
  const { warehouses, fetchWarehouses, addWarehouse } = useWarehousesStore();
  const { moves, fetchMoves, addMove } = useInventoryStore();
  const { products, fetchProducts } = useProductsStore();
  
  const [activeTab, setActiveTab] = useState<'warehouses' | 'moves'>('warehouses');
  const [showNewWarehouse, setShowNewWarehouse] = useState(false);
  const [showNewMove, setShowNewMove] = useState(false);
  
  // Form states
  const [newWarehouse, setNewWarehouse] = useState({ code: '', name: '', address: '' });
  const [newMove, setNewMove] = useState({
    productId: '',
    warehouseId: '',
    type: 'ENTRADA' as 'ENTRADA' | 'SALIDA' | 'AJUSTE' | 'TRANSFERENCIA',
    quantity: 0,
    reference: '',
    notes: '',
  });

  useEffect(() => {
    fetchWarehouses();
    fetchMoves();
    fetchProducts();
  }, []);

  const handleCreateWarehouse = async () => {
    if (!newWarehouse.code || !newWarehouse.name) {
      toast('Código y nombre son obligatorios', 'bad');
      return;
    }
    try {
      await addWarehouse(newWarehouse);
      toast('Bodega creada exitosamente', 'ok');
      setNewWarehouse({ code: '', name: '', address: '' });
      setShowNewWarehouse(false);
    } catch (error) {
      toast('Error al crear bodega', 'bad');
    }
  };

  const handleCreateMove = async () => {
    if (!newMove.productId || !newMove.warehouseId || newMove.quantity <= 0) {
      toast('Producto, bodega y cantidad son obligatorios', 'bad');
      return;
    }
    try {
      await addMove(newMove);
      toast('Movimiento registrado exitosamente', 'ok');
      setNewMove({
        productId: '',
        warehouseId: '',
        type: 'ENTRADA',
        quantity: 0,
        reference: '',
        notes: '',
      });
      setShowNewMove(false);
      fetchMoves(); // Refresh moves list
      fetchProducts(); // Refresh products to update stock
    } catch (error) {
      toast('Error al registrar movimiento', 'bad');
    }
  };

  const getTypeColor = (type: string) => {
    switch (type) {
      case 'ENTRADA': return 'text-green-600 bg-green-50';
      case 'SALIDA': return 'text-red-600 bg-red-50';
      case 'AJUSTE': return 'text-blue-600 bg-blue-50';
      case 'TRANSFERENCIA': return 'text-purple-600 bg-purple-50';
      default: return 'text-gray-600 bg-gray-50';
    }
  };

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'ENTRADA': return 'arrow-down';
      case 'SALIDA': return 'arrow-up';
      case 'AJUSTE': return 'edit';
      case 'TRANSFERENCIA': return 'swap';
      default: return 'box';
    }
  };

  return (
    <div className="space-y-6">
      <SectionTitle
        title="Inventarios"
        sub="Gestión de bodegas y movimientos de inventario"
      />

      {/* Tabs */}
      <div className="flex gap-2 border-b border-gray-200">
        <button
          onClick={() => setActiveTab('warehouses')}
          className={`px-4 py-2 font-medium transition-colors ${
            activeTab === 'warehouses'
              ? 'text-maroon border-b-2 border-maroon'
              : 'text-gray-600 hover:text-gray-900'
          }`}
        >
          Bodegas ({warehouses.length})
        </button>
        <button
          onClick={() => setActiveTab('moves')}
          className={`px-4 py-2 font-medium transition-colors ${
            activeTab === 'moves'
              ? 'text-maroon border-b-2 border-maroon'
              : 'text-gray-600 hover:text-gray-900'
          }`}
        >
          Movimientos ({moves.length})
        </button>
      </div>

      {/* Warehouses Tab */}
      {activeTab === 'warehouses' && (
        <div className="space-y-4">
          <div className="flex justify-end">
            <button
              onClick={() => setShowNewWarehouse(true)}
              className={btnDark}
            >
              <I n="plus" s={16} className="inline mr-2" />
              Nueva Bodega
            </button>
          </div>

          <Card>
            <table className="w-full">
              <thead>
                <tr className="border-b border-gray-200">
                  <Th>Código</Th>
                  <Th>Nombre</Th>
                  <Th>Dirección</Th>
                  <Th>Movimientos</Th>
                  <Th>Estado</Th>
                </tr>
              </thead>
              <tbody>
                {warehouses.map((w: Warehouse) => (
                  <tr key={w.id} className="border-b border-gray-100 hover:bg-gray-50">
                    <Td className="font-mono text-sm">{w.code}</Td>
                    <Td className="font-medium">{w.name}</Td>
                    <Td className="text-gray-600">{w.address || '-'}</Td>
                    <Td className="text-center">
                      <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                        {w._count?.inventoryMoves || 0}
                      </span>
                    </Td>
                    <Td>
                      <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                        w.active ? 'bg-green-50 text-green-700' : 'bg-gray-50 text-gray-700'
                      }`}>
                        {w.active ? 'Activa' : 'Inactiva'}
                      </span>
                    </Td>
                  </tr>
                ))}
                {warehouses.length === 0 && (
                  <tr>
                    <td colSpan={5} className="text-center py-8 text-gray-500">
                      No hay bodegas registradas
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </Card>
        </div>
      )}

      {/* Moves Tab */}
      {activeTab === 'moves' && (
        <div className="space-y-4">
          <div className="flex justify-end">
            <button
              onClick={() => setShowNewMove(true)}
              className={btnDark}
            >
              <I n="plus" s={16} className="inline mr-2" />
              Nuevo Movimiento
            </button>
          </div>

          <Card>
            <table className="w-full">
              <thead>
                <tr className="border-b border-gray-200">
                  <Th>Fecha</Th>
                  <Th>Producto</Th>
                  <Th>Bodega</Th>
                  <Th>Tipo</Th>
                  <Th>Cantidad</Th>
                  <Th>Referencia</Th>
                </tr>
              </thead>
              <tbody>
                {moves.map((m: InventoryMove) => (
                  <tr key={m.id} className="border-b border-gray-100 hover:bg-gray-50">
                    <Td className="text-sm text-gray-600">
                      {new Date(m.createdAt).toLocaleDateString('es-EC')}
                    </Td>
                    <Td className="font-medium">{m.product?.name || '-'}</Td>
                    <Td>{m.warehouse?.name || '-'}</Td>
                    <Td>
                      <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium ${getTypeColor(m.type)}`}>
                        <I n={getTypeIcon(m.type) as any} s={12} />
                        {m.type}
                      </span>
                    </Td>
                    <Td className={`font-mono font-medium ${
                      m.type === 'ENTRADA' ? 'text-green-600' :
                      m.type === 'SALIDA' ? 'text-red-600' :
                      'text-blue-600'
                    }`}>
                      {m.type === 'SALIDA' ? '-' : '+'}{m.quantity}
                    </Td>
                    <Td className="text-sm text-gray-600">{m.reference || '-'}</Td>
                  </tr>
                ))}
                {moves.length === 0 && (
                  <tr>
                    <td colSpan={6} className="text-center py-8 text-gray-500">
                      No hay movimientos registrados
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </Card>
        </div>
      )}

      {/* New Warehouse Modal */}
      {showNewWarehouse && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <Card className="w-full max-w-md">
            <h3 className="text-lg font-semibold mb-4">Nueva Bodega</h3>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-1">Código *</label>
                <input
                  type="text"
                  value={newWarehouse.code}
                  onChange={(e) => setNewWarehouse({ ...newWarehouse, code: e.target.value })}
                  className={inp}
                  placeholder="BOD-001"
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Nombre *</label>
                <input
                  type="text"
                  value={newWarehouse.name}
                  onChange={(e) => setNewWarehouse({ ...newWarehouse, name: e.target.value })}
                  className={inp}
                  placeholder="Bodega Principal"
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Dirección</label>
                <input
                  type="text"
                  value={newWarehouse.address}
                  onChange={(e) => setNewWarehouse({ ...newWarehouse, address: e.target.value })}
                  className={inp}
                  placeholder="Av. Principal 123, Cuenca"
                />
              </div>
              <div className="flex gap-2 justify-end pt-4">
                <button
                  onClick={() => setShowNewWarehouse(false)}
                  className={btnGhost}
                >
                  Cancelar
                </button>
                <button
                  onClick={handleCreateWarehouse}
                  className={btnDark}
                >
                  Crear Bodega
                </button>
              </div>
            </div>
          </Card>
        </div>
      )}

      {/* New Move Modal */}
      {showNewMove && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <Card className="w-full max-w-md">
            <h3 className="text-lg font-semibold mb-4">Nuevo Movimiento</h3>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-1">Producto *</label>
                <select
                  value={newMove.productId}
                  onChange={(e) => setNewMove({ ...newMove, productId: e.target.value })}
                  className={inp}
                >
                  <option value="">Seleccionar producto</option>
                  {products.map((p) => (
                    <option key={p.id} value={p.id}>
                      {p.name} (Stock: {p.stock})
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Bodega *</label>
                <select
                  value={newMove.warehouseId}
                  onChange={(e) => setNewMove({ ...newMove, warehouseId: e.target.value })}
                  className={inp}
                >
                  <option value="">Seleccionar bodega</option>
                  {warehouses.map((w) => (
                    <option key={w.id} value={w.id}>
                      {w.name}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Tipo *</label>
                <select
                  value={newMove.type}
                  onChange={(e) => setNewMove({ ...newMove, type: e.target.value as any })}
                  className={inp}
                >
                  <option value="ENTRADA">Entrada</option>
                  <option value="SALIDA">Salida</option>
                  <option value="AJUSTE">Ajuste</option>
                  <option value="TRANSFERENCIA">Transferencia</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Cantidad *</label>
                <input
                  type="number"
                  value={newMove.quantity}
                  onChange={(e) => setNewMove({ ...newMove, quantity: parseInt(e.target.value) || 0 })}
                  className={inp}
                  min="1"
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Referencia</label>
                <input
                  type="text"
                  value={newMove.reference}
                  onChange={(e) => setNewMove({ ...newMove, reference: e.target.value })}
                  className={inp}
                  placeholder="OC-001, OV-002, etc."
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Notas</label>
                <textarea
                  value={newMove.notes}
                  onChange={(e) => setNewMove({ ...newMove, notes: e.target.value })}
                  className={inp}
                  rows={3}
                  placeholder="Observaciones adicionales..."
                />
              </div>
              <div className="flex gap-2 justify-end pt-4">
                <button
                  onClick={() => setShowNewMove(false)}
                  className={btnGhost}
                >
                  Cancelar
                </button>
                <button
                  onClick={handleCreateMove}
                  className={btnDark}
                >
                  Registrar Movimiento
                </button>
              </div>
            </div>
          </Card>
        </div>
      )}
    </div>
  );
}
