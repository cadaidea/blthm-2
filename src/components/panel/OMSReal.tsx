import { useEffect, useState } from 'react';
import { useOrdersStore, useCustomersStore, useProductsStore } from '../../store';
import { I, toast } from '../ui';
import { Card, SectionTitle, Td, Th, btnDark, btnGhost, inp } from './pui';
import type { SalesOrder } from '../../api/client';

const STATUS_COLORS: Record<string, string> = {
  PENDIENTE: 'bg-warnbg text-warn',
  CONFIRMADO: 'bg-okbg text-ok',
  EN_PRODUCCION: 'bg-paper2 text-ink2',
  LISTO: 'bg-okbg text-ok',
  ENVIADO: 'bg-paper2 text-ink2',
  ENTREGADO: 'bg-okbg text-ok',
  CANCELADO: 'bg-badbg text-bad',
};

const STATUS_LABELS: Record<string, string> = {
  PENDIENTE: 'Pendiente',
  CONFIRMADO: 'Confirmado',
  EN_PRODUCCION: 'En Producción',
  LISTO: 'Listo',
  ENVIADO: 'Enviado',
  ENTREGADO: 'Entregado',
  CANCELADO: 'Cancelado',
};

export function OMSReal() {
  const { orders, fetchOrders, addOrder, updateOrderStatus, deleteOrder, isLoading } = useOrdersStore();
  const { customers, fetchCustomers } = useCustomersStore();
  const { products, fetchProducts } = useProductsStore();

  const [filter, setFilter] = useState<string>('TODOS');
  const [openNew, setOpenNew] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState<SalesOrder | null>(null);

  // Formulario de nueva orden
  const [customerId, setCustomerId] = useState('');
  const [items, setItems] = useState<Array<{ productId: string; quantity: number }>>([]);
  const [notes, setNotes] = useState('');

  useEffect(() => {
    fetchOrders();
    fetchCustomers();
    fetchProducts();
  }, []);

  const filteredOrders = filter === 'TODOS' 
    ? orders 
    : orders.filter(o => o.status === filter);

  const stats = {
    total: orders.length,
    pendientes: orders.filter(o => o.status === 'PENDIENTE').length,
    enProduccion: orders.filter(o => o.status === 'EN_PRODUCCION').length,
    entregados: orders.filter(o => o.status === 'ENTREGADO').length,
  };

  const handleAddItem = () => {
    setItems([...items, { productId: '', quantity: 1 }]);
  };

  const handleRemoveItem = (index: number) => {
    setItems(items.filter((_, i) => i !== index));
  };

  const handleItemChange = (index: number, field: 'productId' | 'quantity', value: string | number) => {
    const newItems = [...items];
    newItems[index] = { ...newItems[index], [field]: value };
    setItems(newItems);
  };

  const handleCreateOrder = async () => {
    if (!customerId) {
      toast('Selecciona un cliente', 'bad');
      return;
    }
    if (items.length === 0 || items.some(i => !i.productId || i.quantity <= 0)) {
      toast('Agrega al menos un producto con cantidad válida', 'bad');
      return;
    }

    const orderItems = items.map(item => {
      const product = products.find(p => p.id === item.productId);
      return {
        productId: item.productId,
        quantity: item.quantity,
        unitPrice: product?.price || 0,
      };
    });

    try {
      await addOrder({ customerId, items: orderItems, notes });
      toast('Orden creada exitosamente', 'ok');
      setOpenNew(false);
      setCustomerId('');
      setItems([]);
      setNotes('');
    } catch (error) {
      toast('Error al crear la orden', 'bad');
    }
  };

  const handleUpdateStatus = async (id: string, status: SalesOrder['status']) => {
    try {
      await updateOrderStatus(id, status);
      toast(`Estado actualizado a ${STATUS_LABELS[status]}`, 'ok');
    } catch (error) {
      toast('Error al actualizar el estado', 'bad');
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm('¿Estás seguro de eliminar esta orden?')) return;
    try {
      await deleteOrder(id);
      toast('Orden eliminada', 'ok');
    } catch (error) {
      toast('Error al eliminar la orden', 'bad');
    }
  };

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Gestión de pedidos"
        sub="Órdenes de venta conectadas a PostgreSQL"
        right={
          <button onClick={() => setOpenNew(true)} className={btnDark}>
            <I n="plus" s={14} /> Nueva orden
          </button>
        }
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <div className="bg-card border border-line p-4">
          <p className="text-[11px] font-bold tracking-[0.14em] uppercase text-stone">Total</p>
          <p className="font-display font-medium text-[24px] mt-1 tnum">{stats.total}</p>
          <p className="text-[11px] text-stone mt-0.5">órdenes registradas</p>
        </div>
        <div className="bg-card border border-line p-4">
          <p className="text-[11px] font-bold tracking-[0.14em] uppercase text-stone">Pendientes</p>
          <p className="font-display font-medium text-[24px] mt-1 tnum text-warn">{stats.pendientes}</p>
          <p className="text-[11px] text-stone mt-0.5">por confirmar</p>
        </div>
        <div className="bg-card border border-line p-4">
          <p className="text-[11px] font-bold tracking-[0.14em] uppercase text-stone">En Producción</p>
          <p className="font-display font-medium text-[24px] mt-1 tnum">{stats.enProduccion}</p>
          <p className="text-[11px] text-stone mt-0.5">en proceso</p>
        </div>
        <div className="bg-card border border-line p-4">
          <p className="text-[11px] font-bold tracking-[0.14em] uppercase text-stone">Entregados</p>
          <p className="font-display font-medium text-[24px] mt-1 tnum text-ok">{stats.entregados}</p>
          <p className="text-[11px] text-stone mt-0.5">completados</p>
        </div>
      </div>

      <Card className="p-4 flex flex-col sm:flex-row gap-3">
        <div className="flex gap-1.5 flex-wrap">
          {['TODOS', 'PENDIENTE', 'CONFIRMADO', 'EN_PRODUCCION', 'LISTO', 'ENVIADO', 'ENTREGADO', 'CANCELADO'].map((s) => (
            <button key={s} onClick={() => setFilter(s)}
              className={`px-3 py-2 text-[11.5px] font-semibold border transition-colors ${filter === s ? 'bg-ink text-paper border-ink' : 'border-linedark text-ink2 hover:border-ink'}`}>
              {STATUS_LABELS[s] || 'Todos'}
            </button>
          ))}
        </div>
      </Card>

      {isLoading ? (
        <Card className="p-10 text-center">
          <p className="text-stone">Cargando órdenes...</p>
        </Card>
      ) : filteredOrders.length === 0 ? (
        <Card className="border-dashed !border-2 !border-linedark">
          <div className="py-20 text-center px-6">
            <div className="inline-flex w-14 h-14 rounded-full bg-paper2 items-center justify-center mb-5">
              <I n="box" s={24} className="text-stone" />
            </div>
            <h3 className="font-display font-medium text-[22px]">No hay órdenes</h3>
            <p className="text-[13.5px] text-stone mt-2 max-w-[46ch] mx-auto leading-relaxed">
              {filter === 'TODOS' 
                ? 'Crea tu primera orden con el botón de arriba.'
                : `No hay órdenes con estado "${STATUS_LABELS[filter]}".`
              }
            </p>
          </div>
        </Card>
      ) : (
        <Card className="overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[880px]">
              <thead>
                <tr>
                  <Th>Código</Th>
                  <Th>Cliente</Th>
                  <Th>Productos</Th>
                  <Th>Total</Th>
                  <Th>Estado</Th>
                  <Th>Fecha</Th>
                  <Th> </Th>
                </tr>
              </thead>
              <tbody>
                {filteredOrders.map((o) => (
                  <tr key={o.id} className="hover:bg-paper2/50 transition-colors fade-in">
                    <Td className="font-mono text-[12px] font-semibold">{o.code}</Td>
                    <Td>
                      <span className="font-medium">{o.customer.name}</span>
                      <span className="block text-[11px] text-stone">{o.customer.email}</span>
                    </Td>
                    <Td className="text-ink2">{o.items.length} items</Td>
                    <Td className="tnum font-semibold">${o.total.toFixed(2)}</Td>
                    <Td>
                      <span className={`inline-block px-2.5 py-1 text-[11px] font-semibold ${STATUS_COLORS[o.status]}`}>
                        {STATUS_LABELS[o.status]}
                      </span>
                    </Td>
                    <Td className="text-[12px] text-stone">
                      {new Date(o.createdAt).toLocaleDateString('es-EC')}
                    </Td>
                    <Td>
                      <div className="flex gap-1">
                        <button onClick={() => setSelectedOrder(o)} title="Ver detalle"
                          className="p-2 text-stone hover:text-ink hover:bg-paper2 transition-colors">
                          <I n="eye" s={15} />
                        </button>
                        <button onClick={() => handleDelete(o.id)} title="Eliminar"
                          className="p-2 text-stone hover:text-bad hover:bg-badbg transition-colors">
                          <I n="close" s={15} />
                        </button>
                      </div>
                    </Td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      )}

      {/* Modal de nueva orden */}
      {openNew && (
        <div className="fixed inset-0 z-[90] bg-ink/50 flex items-center justify-center p-4">
          <div className="bg-paper border border-line max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b border-line flex items-center justify-between">
              <h3 className="font-bold text-[17px]">Nueva orden</h3>
              <button onClick={() => setOpenNew(false)} className="p-2 hover:bg-paper2">
                <I n="close" s={16} />
              </button>
            </div>
            <div className="p-6 space-y-4">
              <div>
                <label className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Cliente *</label>
                <select value={customerId} onChange={(e) => setCustomerId(e.target.value)} className={inp}>
                  <option value="">Selecciona un cliente</option>
                  {customers.map(c => (
                    <option key={c.id} value={c.id}>{c.name}</option>
                  ))}
                </select>
              </div>

              <div>
                <div className="flex items-center justify-between mb-2">
                  <label className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone">Productos *</label>
                  <button onClick={handleAddItem} className="text-[11px] font-semibold text-maroon hover:text-maroon2">
                    + Agregar producto
                  </button>
                </div>
                <div className="space-y-2">
                  {items.map((item, index) => (
                    <div key={index} className="flex gap-2 items-center">
                      <select 
                        value={item.productId} 
                        onChange={(e) => handleItemChange(index, 'productId', e.target.value)}
                        className={`${inp} flex-1`}
                      >
                        <option value="">Selecciona un producto</option>
                        {products.map(p => (
                          <option key={p.id} value={p.id}>{p.name} - ${p.price.toFixed(2)}</option>
                        ))}
                      </select>
                      <input 
                        type="number" 
                        min="1"
                        value={item.quantity}
                        onChange={(e) => handleItemChange(index, 'quantity', parseInt(e.target.value) || 1)}
                        className={`${inp} w-20`}
                        placeholder="Cant."
                      />
                      <button onClick={() => handleRemoveItem(index)} className="p-2 text-stone hover:text-bad">
                        <I n="close" s={14} />
                      </button>
                    </div>
                  ))}
                </div>
              </div>

              <div>
                <label className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Notas</label>
                <textarea 
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                  className={`${inp} resize-y`}
                  rows={3}
                  placeholder="Notas adicionales..."
                />
              </div>
            </div>
            <div className="p-6 border-t border-line flex justify-end gap-2">
              <button onClick={() => setOpenNew(false)} className={btnGhost}>Cancelar</button>
              <button onClick={handleCreateOrder} className={btnDark}>Crear orden</button>
            </div>
          </div>
        </div>
      )}

      {/* Modal de detalle */}
      {selectedOrder && (
        <div className="fixed inset-0 z-[90] bg-ink/50 flex items-center justify-center p-4">
          <div className="bg-paper border border-line max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b border-line flex items-center justify-between">
              <h3 className="font-bold text-[17px]">Orden {selectedOrder.code}</h3>
              <button onClick={() => setSelectedOrder(null)} className="p-2 hover:bg-paper2">
                <I n="close" s={16} />
              </button>
            </div>
            <div className="p-6 space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-[11px] font-bold tracking-[0.14em] uppercase text-stone">Cliente</p>
                  <p className="font-medium">{selectedOrder.customer.name}</p>
                  <p className="text-[12px] text-stone">{selectedOrder.customer.email}</p>
                </div>
                <div>
                  <p className="text-[11px] font-bold tracking-[0.14em] uppercase text-stone">Estado</p>
                  <span className={`inline-block px-2.5 py-1 text-[11px] font-semibold ${STATUS_COLORS[selectedOrder.status]}`}>
                    {STATUS_LABELS[selectedOrder.status]}
                  </span>
                </div>
              </div>

              <div>
                <p className="text-[11px] font-bold tracking-[0.14em] uppercase text-stone mb-2">Productos</p>
                <div className="border border-line">
                  <table className="w-full">
                    <thead>
                      <tr className="border-b border-line bg-paper2">
                        <th className="text-left px-3 py-2 text-[11px] font-bold">Producto</th>
                        <th className="text-right px-3 py-2 text-[11px] font-bold">Cant.</th>
                        <th className="text-right px-3 py-2 text-[11px] font-bold">Precio</th>
                        <th className="text-right px-3 py-2 text-[11px] font-bold">Subtotal</th>
                      </tr>
                    </thead>
                    <tbody>
                      {selectedOrder.items.map(item => (
                        <tr key={item.id} className="border-b border-line last:border-0">
                          <td className="px-3 py-2 text-[13px]">{item.product.name}</td>
                          <td className="px-3 py-2 text-[13px] text-right">{item.quantity}</td>
                          <td className="px-3 py-2 text-[13px] text-right">${item.unitPrice.toFixed(2)}</td>
                          <td className="px-3 py-2 text-[13px] text-right font-semibold">${item.subtotal.toFixed(2)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>

              <div className="border-t border-line pt-4 space-y-2">
                <div className="flex justify-between text-[13px]">
                  <span className="text-stone">Subtotal:</span>
                  <span className="font-semibold">${selectedOrder.subtotal.toFixed(2)}</span>
                </div>
                <div className="flex justify-between text-[13px]">
                  <span className="text-stone">IVA (15%):</span>
                  <span className="font-semibold">${selectedOrder.tax.toFixed(2)}</span>
                </div>
                <div className="flex justify-between text-[15px] font-bold border-t border-line pt-2">
                  <span>Total:</span>
                  <span className="text-maroon">${selectedOrder.total.toFixed(2)}</span>
                </div>
              </div>

              {selectedOrder.notes && (
                <div>
                  <p className="text-[11px] font-bold tracking-[0.14em] uppercase text-stone mb-1">Notas</p>
                  <p className="text-[13px] text-ink2">{selectedOrder.notes}</p>
                </div>
              )}

              <div>
                <p className="text-[11px] font-bold tracking-[0.14em] uppercase text-stone mb-2">Cambiar estado</p>
                <div className="flex flex-wrap gap-2">
                  {(['PENDIENTE', 'CONFIRMADO', 'EN_PRODUCCION', 'LISTO', 'ENVIADO', 'ENTREGADO', 'CANCELADO'] as const).map(status => (
                    <button
                      key={status}
                      onClick={() => handleUpdateStatus(selectedOrder.id, status)}
                      disabled={selectedOrder.status === status}
                      className={`px-3 py-1.5 text-[11px] font-semibold border transition-colors ${
                        selectedOrder.status === status 
                          ? 'bg-ink text-paper border-ink cursor-default' 
                          : 'border-linedark text-ink2 hover:border-ink'
                      }`}
                    >
                      {STATUS_LABELS[status]}
                    </button>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
