import { useEffect, useState } from 'react';
import { usePurchaseOrdersStore, useSuppliersStore, useProductsStore } from '../../store';
import { I, toast } from '../ui';
import { confirm } from '../ConfirmModal';
import { Card, SectionTitle, Td, Th, btnDark, btnGhost, inp } from './pui';
import type { PurchaseOrder } from '../../api/client';

export function ComprasReal() {
  const { orders, fetchOrders, addOrder, updateOrderStatus, deleteOrder } = usePurchaseOrdersStore();
  const { suppliers, fetchSuppliers } = useSuppliersStore();
  const { products, fetchProducts } = useProductsStore();
  
  const [showNewOrder, setShowNewOrder] = useState(false);
  const [newOrder, setNewOrder] = useState({
    supplierId: '',
    items: [] as Array<{ productId: string; quantity: number; unitCost: number }>,
    expectedDate: '',
    notes: '',
  });
  const [filter, setFilter] = useState<string>('all');

  useEffect(() => {
    fetchOrders();
    fetchSuppliers();
    fetchProducts();
  }, []);

  const handleCreateOrder = async () => {
    if (!newOrder.supplierId || newOrder.items.length === 0) {
      toast('Selecciona proveedor y al menos un producto', 'bad');
      return;
    }
    try {
      await addOrder(newOrder);
      toast('Orden de compra creada exitosamente', 'ok');
      setNewOrder({ supplierId: '', items: [], expectedDate: '', notes: '' });
      setShowNewOrder(false);
    } catch (error) {
      toast('Error al crear orden de compra', 'bad');
    }
  };

  const handleStatusChange = async (id: string, status: PurchaseOrder['status']) => {
    try {
      await updateOrderStatus(id, status);
      toast(`Estado actualizado a ${status}`, 'ok');
    } catch (error) {
      toast('Error al actualizar estado', 'bad');
    }
  };

  const handleDelete = async (id: string) => {
    const order = orders.find(o => o.id === id);
    if (!order) return;
    
    confirm.generic(
      'Eliminar orden de compra',
      `¿Estás seguro de que deseas eliminar la orden de compra "${order.code}"? Esta acción no se puede deshacer.`,
      async () => {
        try {
          await deleteOrder(id);
          toast('Orden eliminada', 'ok');
        } catch (error) {
          toast('Error al eliminar orden', 'bad');
        }
      },
      'danger'
    );
  };

  const addItem = () => {
    setNewOrder({
      ...newOrder,
      items: [...newOrder.items, { productId: '', quantity: 1, unitCost: 0 }],
    });
  };

  const updateItem = (index: number, field: string, value: string | number) => {
    const items = [...newOrder.items];
    items[index] = { ...items[index], [field]: value };
    setNewOrder({ ...newOrder, items });
  };

  const removeItem = (index: number) => {
    setNewOrder({ ...newOrder, items: newOrder.items.filter((_, i) => i !== index) });
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'BORRADOR': return 'bg-gray-100 text-gray-700';
      case 'ENVIADA': return 'bg-blue-100 text-blue-700';
      case 'PARCIAL': return 'bg-yellow-100 text-yellow-700';
      case 'RECIBIDA': return 'bg-green-100 text-green-700';
      case 'CANCELADA': return 'bg-red-100 text-red-700';
      default: return 'bg-gray-100 text-gray-700';
    }
  };

  const filteredOrders = filter === 'all' 
    ? orders 
    : orders.filter(o => o.status === filter);

  const totalOrders = orders.length;
  const pendingOrders = orders.filter(o => o.status === 'ENVIADA').length;
  const receivedOrders = orders.filter(o => o.status === 'RECIBIDA').length;
  const totalAmount = orders.reduce((sum, o) => sum + o.total, 0);

  return (
    <div className="space-y-6">
      <SectionTitle
        title="Compras"
        sub="Gestión de órdenes de compra a proveedores"
      />

      {/* Estadísticas */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <div className="text-sm text-gray-600">Total Órdenes</div>
          <div className="text-2xl font-bold">{totalOrders}</div>
        </Card>
        <Card className="p-4">
          <div className="text-sm text-gray-600">Pendientes</div>
          <div className="text-2xl font-bold text-blue-600">{pendingOrders}</div>
        </Card>
        <Card className="p-4">
          <div className="text-sm text-gray-600">Recibidas</div>
          <div className="text-2xl font-bold text-green-600">{receivedOrders}</div>
        </Card>
        <Card className="p-4">
          <div className="text-sm text-gray-600">Monto Total</div>
          <div className="text-2xl font-bold">${totalAmount.toFixed(2)}</div>
        </Card>
      </div>

      {/* Filtros y acciones */}
      <div className="flex justify-between items-center">
        <div className="flex gap-2">
          <button
            onClick={() => setFilter('all')}
            className={`px-4 py-2 rounded ${filter === 'all' ? 'bg-ink text-paper' : 'bg-paper2 text-ink2'}`}
          >
            Todas
          </button>
          <button
            onClick={() => setFilter('ENVIADA')}
            className={`px-4 py-2 rounded ${filter === 'ENVIADA' ? 'bg-ink text-paper' : 'bg-paper2 text-ink2'}`}
          >
            Pendientes
          </button>
          <button
            onClick={() => setFilter('RECIBIDA')}
            className={`px-4 py-2 rounded ${filter === 'RECIBIDA' ? 'bg-ink text-paper' : 'bg-paper2 text-ink2'}`}
          >
            Recibidas
          </button>
        </div>
        <button onClick={() => setShowNewOrder(true)} className={btnDark}>
          <I n="plus" s={16} className="inline mr-2" />
          Nueva Orden
        </button>
      </div>

      {/* Tabla de órdenes */}
      <Card>
        <table className="w-full">
          <thead>
            <tr className="border-b border-gray-200">
              <Th>Código</Th>
              <Th>Proveedor</Th>
              <Th>Fecha</Th>
              <Th>Estado</Th>
              <Th>Total</Th>
              <Th>Acciones</Th>
            </tr>
          </thead>
          <tbody>
            {filteredOrders.map((order) => (
              <tr key={order.id} className="border-b border-gray-100 hover:bg-gray-50">
                <Td className="font-mono text-sm">{order.code}</Td>
                <Td>{order.supplier?.name || '-'}</Td>
                <Td className="text-sm text-gray-600">
                  {new Date(order.createdAt).toLocaleDateString('es-EC')}
                </Td>
                <Td>
                  <span className={`px-2 py-1 rounded text-xs font-medium ${getStatusColor(order.status)}`}>
                    {order.status}
                  </span>
                </Td>
                <Td className="font-semibold">${order.total.toFixed(2)}</Td>
                <Td>
                  <div className="flex gap-2">
                    {order.status === 'BORRADOR' && (
                      <button
                        onClick={() => handleStatusChange(order.id, 'ENVIADA')}
                        className="text-blue-600 hover:text-blue-800 text-sm"
                      >
                        Enviar
                      </button>
                    )}
                    {order.status === 'ENVIADA' && (
                      <>
                        <button
                          onClick={() => handleStatusChange(order.id, 'PARCIAL')}
                          className="text-yellow-600 hover:text-yellow-800 text-sm"
                        >
                          Parcial
                        </button>
                        <button
                          onClick={() => handleStatusChange(order.id, 'RECIBIDA')}
                          className="text-green-600 hover:text-green-800 text-sm"
                        >
                          Recibir
                        </button>
                      </>
                    )}
                    {order.status === 'PARCIAL' && (
                      <button
                        onClick={() => handleStatusChange(order.id, 'RECIBIDA')}
                        className="text-green-600 hover:text-green-800 text-sm"
                      >
                        Completar
                      </button>
                    )}
                    <button
                      onClick={() => handleDelete(order.id)}
                      className="text-red-600 hover:text-red-800 text-sm"
                    >
                      <I n="close" s={14} />
                    </button>
                  </div>
                </Td>
              </tr>
            ))}
            {filteredOrders.length === 0 && (
              <tr>
                <td colSpan={6} className="text-center py-8 text-gray-500">
                  No hay órdenes de compra
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </Card>

      {/* Modal nueva orden */}
      {showNewOrder && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <Card className="w-full max-w-3xl max-h-[90vh] overflow-y-auto">
            <h3 className="text-lg font-semibold mb-4">Nueva Orden de Compra</h3>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-1">Proveedor *</label>
                <select
                  value={newOrder.supplierId}
                  onChange={(e) => setNewOrder({ ...newOrder, supplierId: e.target.value })}
                  className={inp}
                >
                  <option value="">Seleccionar proveedor</option>
                  {suppliers.map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.name}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">Fecha esperada</label>
                <input
                  type="date"
                  value={newOrder.expectedDate}
                  onChange={(e) => setNewOrder({ ...newOrder, expectedDate: e.target.value })}
                  className={inp}
                />
              </div>

              <div>
                <div className="flex justify-between items-center mb-2">
                  <label className="block text-sm font-medium">Productos</label>
                  <button onClick={addItem} className={btnGhost}>
                    <I n="plus" s={14} className="inline mr-1" />
                    Agregar producto
                  </button>
                </div>
                <div className="space-y-2">
                  {newOrder.items.map((item, index) => (
                    <div key={index} className="flex gap-2 items-start">
                      <select
                        value={item.productId}
                        onChange={(e) => updateItem(index, 'productId', e.target.value)}
                        className={inp}
                      >
                        <option value="">Seleccionar producto</option>
                        {products.map((p) => (
                          <option key={p.id} value={p.id}>
                            {p.name}
                          </option>
                        ))}
                      </select>
                      <input
                        type="number"
                        value={item.quantity}
                        onChange={(e) => updateItem(index, 'quantity', parseInt(e.target.value) || 0)}
                        className={inp}
                        placeholder="Cantidad"
                        min="1"
                      />
                      <input
                        type="number"
                        value={item.unitCost}
                        onChange={(e) => updateItem(index, 'unitCost', parseFloat(e.target.value) || 0)}
                        className={inp}
                        placeholder="Costo unitario"
                        step="0.01"
                      />
                      <button
                        onClick={() => removeItem(index)}
                        className="text-red-600 hover:text-red-800 p-2"
                      >
                        <I n="close" s={16} />
                      </button>
                    </div>
                  ))}
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">Notas</label>
                <textarea
                  value={newOrder.notes}
                  onChange={(e) => setNewOrder({ ...newOrder, notes: e.target.value })}
                  className={inp}
                  rows={3}
                />
              </div>

              <div className="flex gap-2 justify-end pt-4">
                <button onClick={() => setShowNewOrder(false)} className={btnGhost}>
                  Cancelar
                </button>
                <button onClick={handleCreateOrder} className={btnDark}>
                  Crear Orden
                </button>
              </div>
            </div>
          </Card>
        </div>
      )}
    </div>
  );
}
