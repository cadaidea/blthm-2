import { useEffect, useMemo, useState } from 'react';
import { useCustomersStore } from '../../store';
import { I, Modal, toast } from '../ui';
import { Card, SectionTitle, Td, Th, btnDark, btnGhost, inp } from './pui';
import type { Customer } from '../../api/client';

export function CRMReal() {
  const { customers, isLoading, fetchCustomers, addCustomer, updateCustomer, deleteCustomer } = useCustomersStore();
  const [q, setQ] = useState('');
  const [seg, setSeg] = useState('Todos');
  const [sel, setSel] = useState<Customer | null>(null);
  const [openNew, setOpenNew] = useState(false);
  const [nf, setNf] = useState({ name: '', email: '', phone: '', taxId: '', address: '', city: '' });
  const [nfErr, setNfErr] = useState('');

  useEffect(() => {
    fetchCustomers();
  }, [fetchCustomers]);

  const shown = useMemo(
    () =>
      customers.filter(
        (c) =>
          (seg === 'Todos' || c.city === seg) &&
          (c.name.toLowerCase().includes(q.toLowerCase()) || c.city?.toLowerCase().includes(q.toLowerCase())),
      ),
    [customers, q, seg],
  );

  const addCustomerHandler = async () => {
    if (nf.name.trim().length < 3) return setNfErr('Ingresa el nombre o razón social.');
    setNfErr('');
    
    try {
      const customer = await addCustomer({
        name: nf.name.trim(),
        email: nf.email.trim() || undefined,
        phone: nf.phone.trim() || undefined,
        taxId: nf.taxId.trim() || undefined,
        address: nf.address.trim() || undefined,
        city: nf.city.trim() || undefined,
      });
      
      toast(`Cliente "${customer.name}" creado`, 'ok');
      setNf({ name: '', email: '', phone: '', taxId: '', address: '', city: '' });
      setOpenNew(false);
      setSel(customer);
    } catch (error) {
      toast(`Error al crear cliente: ${error instanceof Error ? error.message : 'Error desconocido'}`, 'bad');
    }
  };

  const deleteCustomerHandler = async (id: string) => {
    if (!confirm('¿Estás seguro de eliminar este cliente?')) return;
    
    try {
      await deleteCustomer(id);
      toast('Cliente eliminado', 'ok');
      setSel(null);
    } catch (error) {
      toast(`Error al eliminar: ${error instanceof Error ? error.message : 'Error desconocido'}`, 'bad');
    }
  };

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Relación con clientes (API)"
        sub={`${customers.length} fichas · conectado a PostgreSQL`}
        right={
          <button onClick={() => setOpenNew(true)} className={btnDark}>
            <I n="plus" s={14} /> Nuevo cliente
          </button>
        }
      />

      <Card className="p-4 flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <I n="search" s={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-stone" />
          <input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Buscar por nombre o ciudad…"
            className={`${inp} pl-9`}
          />
        </div>
        <div className="flex gap-1.5 flex-wrap">
          {['Todos', 'Cuenca', 'Quito', 'Guayaquil'].map((s) => (
            <button
              key={s}
              onClick={() => setSeg(s)}
              className={`px-3 py-2 text-[11.5px] font-semibold border transition-colors ${
                seg === s ? 'bg-ink text-paper border-ink' : 'border-linedark text-ink2 hover:border-ink'
              }`}
            >
              {s}
            </button>
          ))}
        </div>
      </Card>

      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[760px]">
            <thead>
              <tr>
                <Th>Código</Th>
                <Th>Cliente</Th>
                <Th>Email</Th>
                <Th>Teléfono</Th>
                <Th>Ciudad</Th>
                <Th> </Th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={6} className="px-5 py-10 text-center text-[13px] text-stone">
                    Cargando clientes…
                  </td>
                </tr>
              ) : shown.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-5 py-10 text-center text-[13px] text-stone">
                    {q ? `Sin resultados para «${q}»` : 'Aún no hay clientes. Crea el primero.'}
                  </td>
                </tr>
              ) : (
                shown.map((c) => (
                  <tr
                    key={c.id}
                    onClick={() => setSel(c)}
                    className="hover:bg-paper2/50 transition-colors cursor-pointer fade-in"
                  >
                    <Td className="font-mono text-xs font-semibold">{c.code}</Td>
                    <Td>
                      <span className="font-medium">{c.name}</span>
                      <span className="block text-[11px] text-stone">{c.taxId || '—'}</span>
                    </Td>
                    <Td className="text-ink2">{c.email || '—'}</Td>
                    <Td className="text-ink2">{c.phone || '—'}</Td>
                    <Td>{c.city || '—'}</Td>
                    <Td>
                      <I n="chev-r" s={14} className="text-stone" />
                    </Td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </Card>

      {/* Drawer detalle */}
      {sel && (
        <div className="fixed inset-0 z-[85]">
          <div className="absolute inset-0 bg-ink/45 fade-in" onClick={() => setSel(null)} />
          <aside className="absolute right-0 top-0 h-full w-full max-w-[400px] bg-paper border-l border-line slide-in-right flex flex-col">
            <div className="flex items-center justify-between px-6 h-16 border-b border-line shrink-0">
              <p className="text-[11px] font-bold tracking-[0.16em] uppercase text-stone">Ficha de cliente</p>
              <button onClick={() => setSel(null)} className="p-2 hover:bg-paper2" aria-label="Cerrar">
                <I n="close" s={17} />
              </button>
            </div>
            <div className="flex-1 overflow-y-auto px-6 py-5">
              <div className="flex items-start justify-between gap-4">
                <div>
                  <p className="text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone">{sel.code}</p>
                  <h3 className="font-bold text-[17px] leading-tight mt-1">{sel.name}</h3>
                  <p className="text-[12px] text-stone mt-1">{sel.email || '—'}</p>
                  <p className="text-[11.5px] text-stone font-mono mt-0.5">{sel.taxId || '—'}</p>
                </div>
                <span className="w-10 h-10 bg-ink text-paper text-[12px] font-bold flex items-center justify-center shrink-0">
                  {sel.name.split(' ').slice(0, 2).map((w) => w[0]).join('')}
                </span>
              </div>

              <div className="grid grid-cols-2 gap-px bg-line border border-line mt-5">
                {[
                  ['Teléfono', sel.phone || '—'],
                  ['Ciudad', sel.city || '—'],
                ].map(([k, v]) => (
                  <div key={k} className="bg-card p-3">
                    <p className="text-[9.5px] font-bold tracking-[0.14em] uppercase text-stone">{k}</p>
                    <p className="font-semibold text-[13.5px] mt-1">{v}</p>
                  </div>
                ))}
              </div>

              {sel.address && (
                <div className="mt-5">
                  <p className="text-[11px] font-bold tracking-[0.16em] uppercase text-stone mb-2">Dirección</p>
                  <p className="text-[12.5px] text-ink2">{sel.address}</p>
                </div>
              )}

              <div className="mt-5 pt-5 border-t border-line">
                <p className="text-[11px] font-bold tracking-[0.16em] uppercase text-stone mb-2">Información</p>
                <div className="space-y-1.5 text-[12px] text-ink2">
                  <p>Creado: {new Date(sel.createdAt).toLocaleDateString('es-EC')}</p>
                  <p>Actualizado: {new Date(sel.updatedAt).toLocaleDateString('es-EC')}</p>
                </div>
              </div>
            </div>
            <div className="border-t border-line p-4 shrink-0">
              <button
                onClick={() => deleteCustomerHandler(sel.id)}
                className="w-full text-[12px] font-semibold text-bad border border-bad/30 py-2.5 hover:bg-bad hover:text-paper transition-colors"
              >
                Eliminar cliente
              </button>
            </div>
          </aside>
        </div>
      )}

      {/* Modal nuevo cliente */}
      <Modal open={openNew} onClose={() => setOpenNew(false)}>
        <div className="p-6">
          <h3 className="font-bold text-[17px] mb-5">Nuevo cliente</h3>
          <div className="space-y-4">
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">
                Nombre / Razón social *
              </span>
              <input
                value={nf.name}
                onChange={(e) => setNf({ ...nf, name: e.target.value })}
                className={inp}
                placeholder="Ej. María Fernanda Jaramillo"
              />
            </label>
            <div className="grid grid-cols-2 gap-3">
              <label className="block">
                <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">
                  Email
                </span>
                <input
                  type="email"
                  value={nf.email}
                  onChange={(e) => setNf({ ...nf, email: e.target.value })}
                  className={inp}
                  placeholder="correo@ejemplo.com"
                />
              </label>
              <label className="block">
                <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">
                  Teléfono
                </span>
                <input
                  value={nf.phone}
                  onChange={(e) => setNf({ ...nf, phone: e.target.value })}
                  className={inp}
                  placeholder="+593 99 123 4567"
                />
              </label>
            </div>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">
                Cédula / RUC
              </span>
              <input
                value={nf.taxId}
                onChange={(e) => setNf({ ...nf, taxId: e.target.value })}
                className={inp}
                placeholder="0101010101 o 0101010101001"
              />
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">
                Dirección
              </span>
              <input
                value={nf.address}
                onChange={(e) => setNf({ ...nf, address: e.target.value })}
                className={inp}
                placeholder="Av. Principal 123"
              />
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">
                Ciudad
              </span>
              <input
                value={nf.city}
                onChange={(e) => setNf({ ...nf, city: e.target.value })}
                className={inp}
                placeholder="Cuenca"
              />
            </label>
          </div>
          {nfErr && <p className="text-bad text-[12px] font-medium mt-3">{nfErr}</p>}
          <div className="flex gap-3 mt-6">
            <button onClick={() => setOpenNew(false)} className={btnGhost + ' flex-1'}>
              Cancelar
            </button>
            <button onClick={addCustomerHandler} className={btnDark + ' flex-1'}>
              <I n="plus" s={14} /> Crear cliente
            </button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
