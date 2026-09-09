import { useEffect, useState } from 'react';
import { useCustomersStore } from '../../store';
import { I, toast } from '../ui';
import { Card, SectionTitle, Td, Th, btnDark, btnGhost, inp } from './pui';
import type { Customer } from '../../api/client';

export function CRMAPI() {
  const { customers, isLoading, fetchCustomers, addCustomer, updateCustomer, deleteCustomer } = useCustomersStore();
  const [openNew, setOpenNew] = useState(false);
  const [editing, setEditing] = useState<Customer | null>(null);
  const [form, setForm] = useState({
    name: '',
    email: '',
    phone: '',
    taxId: '',
    address: '',
    city: '',
  });

  useEffect(() => {
    fetchCustomers();
  }, [fetchCustomers]);

  const resetForm = () => {
    setForm({ name: '', email: '', phone: '', taxId: '', address: '', city: '' });
    setEditing(null);
    setOpenNew(false);
  };

  const handleSave = async () => {
    if (!form.name.trim()) {
      toast('El nombre es obligatorio', 'bad');
      return;
    }

    try {
      if (editing) {
        await updateCustomer(editing.id, form);
        toast(`Cliente ${form.name} actualizado`, 'ok');
      } else {
        await addCustomer(form);
        toast(`Cliente ${form.name} creado`, 'ok');
      }
      resetForm();
    } catch (err) {
      toast(err instanceof Error ? err.message : 'Error al guardar', 'bad');
    }
  };

  const handleEdit = (customer: Customer) => {
    setEditing(customer);
    setForm({
      name: customer.name,
      email: customer.email || '',
      phone: customer.phone || '',
      taxId: customer.taxId || '',
      address: customer.address || '',
      city: customer.city || '',
    });
    setOpenNew(true);
  };

  const handleDelete = async (id: string, name: string) => {
    if (!confirm(`¿Eliminar cliente ${name}?`)) return;
    
    try {
      await deleteCustomer(id);
      toast(`Cliente ${name} eliminado`, 'ok');
    } catch (err) {
      toast(err instanceof Error ? err.message : 'Error al eliminar', 'bad');
    }
  };

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Clientes · CRM (con API)"
        sub="Gestión de clientes conectada a PostgreSQL vía API REST"
        right={
          <button onClick={() => setOpenNew(true)} className={btnDark}>
            <I n="plus" s={14} /> Nuevo cliente
          </button>
        }
      />

      {/* Indicador de conexión */}
      <Card className="p-4 bg-okbg border-ok/40">
        <div className="flex items-center gap-3">
          <span className="w-2.5 h-2.5 rounded-full bg-ok pulse-ok" />
          <div>
            <p className="font-semibold text-ok text-sm">Conectado a API</p>
            <p className="text-xs text-ink2">Los datos se sincronizan con PostgreSQL en tiempo real</p>
          </div>
        </div>
      </Card>

      {/* Tabla de clientes */}
      <Card className="overflow-hidden">
        {isLoading ? (
          <div className="p-12 text-center">
            <div className="animate-spin text-3xl mb-3">⏳</div>
            <p className="text-stone">Cargando clientes desde la API...</p>
          </div>
        ) : customers.length === 0 ? (
          <div className="p-12 text-center">
            <I n="users" s={48} className="mx-auto text-stone mb-4" />
            <p className="font-semibold text-lg mb-2">No hay clientes</p>
            <p className="text-stone text-sm mb-4">Crea tu primer cliente para empezar</p>
            <button onClick={() => setOpenNew(true)} className={btnDark}>
              <I n="plus" s={14} /> Crear primer cliente
            </button>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full min-w-[700px]">
              <thead>
                <tr>
                  <Th>Código</Th>
                  <Th>Nombre</Th>
                  <Th>Email</Th>
                  <Th>Teléfono</Th>
                  <Th>Ciudad</Th>
                  <Th>Acciones</Th>
                </tr>
              </thead>
              <tbody>
                {customers.map((c: Customer) => (
                  <tr key={c.id} className="hover:bg-paper2/50 transition-colors">
                    <Td className="font-mono text-xs font-semibold">{c.code}</Td>
                    <Td className="font-medium">{c.name}</Td>
                    <Td className="text-ink2">{c.email || '—'}</Td>
                    <Td className="text-ink2">{c.phone || '—'}</Td>
                    <Td className="text-ink2">{c.city || '—'}</Td>
                    <Td>
                      <div className="flex gap-2">
                        <button
                          onClick={() => handleEdit(c)}
                          className="p-2 hover:bg-paper2 transition-colors"
                          title="Editar"
                        >
                          <I n="doc" s={15} className="text-stone hover:text-ink" />
                        </button>
                        <button
                          onClick={() => handleDelete(c.id, c.name)}
                          className="p-2 hover:bg-badbg transition-colors"
                          title="Eliminar"
                        >
                          <I n="close" s={15} className="text-stone hover:text-bad" />
                        </button>
                      </div>
                    </Td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Footer con estadísticas */}
        {!isLoading && customers.length > 0 && (
          <div className="px-4 py-3 border-t border-line bg-paper2/30">
            <p className="text-xs text-stone">
              Mostrando <strong>{customers.length}</strong> cliente{customers.length !== 1 ? 's' : ''}
            </p>
          </div>
        )}
      </Card>

      {/* Modal de crear/editar */}
      {openNew && (
        <div className="fixed inset-0 z-[90] flex items-center justify-center p-4 bg-ink/50 backdrop-blur-sm">
          <div className="bg-card border border-line rounded-lg shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div className="sticky top-0 bg-card border-b border-line px-6 py-4 flex items-center justify-between">
              <h3 className="font-bold text-lg">{editing ? 'Editar cliente' : 'Nuevo cliente'}</h3>
              <button onClick={resetForm} className="p-2 hover:bg-paper2 transition-colors">
                <I n="close" s={20} />
              </button>
            </div>

            <div className="p-6 space-y-4">
              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-semibold mb-2">
                    Nombre / Razón social *
                  </label>
                  <input
                    value={form.name}
                    onChange={(e) => setForm({ ...form, name: e.target.value })}
                    className={inp}
                    placeholder="Ej. María Fernanda Jaramillo"
                  />
                </div>

                <div>
                  <label className="block text-sm font-semibold mb-2">
                    Cédula / RUC
                  </label>
                  <input
                    value={form.taxId}
                    onChange={(e) => setForm({ ...form, taxId: e.target.value })}
                    className={`${inp} font-mono`}
                    placeholder="10 o 13 dígitos"
                  />
                </div>

                <div>
                  <label className="block text-sm font-semibold mb-2">
                    Email
                  </label>
                  <input
                    type="email"
                    value={form.email}
                    onChange={(e) => setForm({ ...form, email: e.target.value })}
                    className={inp}
                    placeholder="correo@ejemplo.com"
                  />
                </div>

                <div>
                  <label className="block text-sm font-semibold mb-2">
                    Teléfono
                  </label>
                  <input
                    value={form.phone}
                    onChange={(e) => setForm({ ...form, phone: e.target.value })}
                    className={inp}
                    placeholder="+593 99 123 4567"
                  />
                </div>

                <div>
                  <label className="block text-sm font-semibold mb-2">
                    Ciudad
                  </label>
                  <input
                    value={form.city}
                    onChange={(e) => setForm({ ...form, city: e.target.value })}
                    className={inp}
                    placeholder="Cuenca"
                  />
                </div>

                <div className="md:col-span-2">
                  <label className="block text-sm font-semibold mb-2">
                    Dirección
                  </label>
                  <input
                    value={form.address}
                    onChange={(e) => setForm({ ...form, address: e.target.value })}
                    className={inp}
                    placeholder="Av. Principal 123"
                  />
                </div>
              </div>
            </div>

            <div className="sticky bottom-0 bg-card border-t border-line px-6 py-4 flex gap-3 justify-end">
              <button onClick={resetForm} className={btnGhost}>
                Cancelar
              </button>
              <button onClick={handleSave} className={btnDark}>
                <I n="check" s={14} />
                {editing ? 'Actualizar' : 'Crear cliente'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
