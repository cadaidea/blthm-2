import { useMemo, useState } from "react";
import {
  BODEGAS, COMPRAS_SEED, SUPPLIERS, fmt, fmt2, loadCompras, loadEmpleados,
  randomCode, saveCompras, saveEmpleados,
  type CompraOC, type Empleado,
} from "../../data";
import { CopyBtn, I, Modal, toast } from "../ui";
import { Bar, Card, Chip, SectionTitle, Stat, Td, Th, btnDark, btnGhost, inp } from "./pui";
import { StatusChip } from "./Panel";

/* ================= RRHH · Nómina (fuente de autores del blog) ================= */
export function RRHH() {
  const [emps, setEmps] = useState<Empleado[]>(() => loadEmpleados());
  const [openNew, setOpenNew] = useState(false);
  const [nf, setNf] = useState({ nombre: "", cargo: "", area: "Taller", sueldo: "", email: "" });

  const activos = emps.filter((e) => e.estado === "Activo");
  const masa = activos.reduce((a, e) => a + e.sueldo, 0);
  const autores = emps.filter((e) => e.esAutor).length;

  const persist = (next: Empleado[]) => { setEmps(next); saveEmpleados(next); };

  const toggleAutor = (id: string) => {
    const target = emps.find((e) => e.id === id);
    persist(emps.map((e) => (e.id === id ? { ...e, esAutor: !e.esAutor } : e)));
    if (target) toast(target.esAutor ? `${target.nombre} ya no firma artículos` : `${target.nombre} ahora puede firmar artículos`, "ok");
  };

  const add = () => {
    const sueldo = parseFloat(nf.sueldo);
    if (nf.nombre.trim().length < 3 || !sueldo || sueldo <= 0) return;
    const e: Empleado = {
      id: `e${Date.now()}`, nombre: nf.nombre.trim(), cargo: nf.cargo.trim() || "Colaborador",
      area: nf.area, sueldo, estado: "Activo", ingreso: "2026",
      email: nf.email.trim() || `${nf.nombre.trim().split(" ")[0].toLowerCase()}@bletia.ec`,
      esAutor: false,
    };
    persist([e, ...emps]);
    toast(`${e.nombre} ingresó a la nómina`, "ok");
    setNf({ nombre: "", cargo: "", area: "Taller", sueldo: "", email: "" });
    setOpenNew(false);
  };

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="RRHH · Nómina"
        sub={`${emps.length} colaboradores · de aquí salen los autores que firman el diario de taller.`}
        right={<button onClick={() => setOpenNew(true)} className={btnDark}><I n="plus" s={14} /> Nuevo colaborador</button>}
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Colaboradores" value={emps.length} sub={`${activos.length} activos`} />
        <Stat label="Masa salarial" value={fmt(masa)} sub="mensual · solo activos" />
        <Stat label="Autores del diario" value={autores} sub="marcados con ✎ en la tabla" />
        <Stat label="En vacaciones" value={emps.filter((e) => e.estado === "Vacaciones").length} sub="cobertura de taller asignada" />
      </div>

      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[860px]">
            <thead><tr><Th>Colaborador</Th><Th>Cargo</Th><Th>Área</Th><Th>Sueldo</Th><Th>Estado</Th><Th>Autor del diario</Th></tr></thead>
            <tbody>
              {emps.map((e) => (
                <tr key={e.id} className="hover:bg-paper2/50 transition-colors fade-in">
                  <Td>
                    <div className="flex items-center gap-3">
                      <span className="w-9 h-9 bg-ink text-paper text-[11px] font-bold flex items-center justify-center shrink-0">
                        {e.nombre.split(" ").slice(0, 2).map((w) => w[0]).join("")}
                      </span>
                      <span>
                        <span className="font-medium block">{e.nombre}</span>
                        <span className="text-[11px] text-stone">{e.email}</span>
                      </span>
                    </div>
                  </Td>
                  <Td className="text-ink2">{e.cargo}</Td>
                  <Td><Chip tone="neutral">{e.area}</Chip></Td>
                  <Td className="tnum font-semibold">{fmt(e.sueldo)}</Td>
                  <Td>
                    <Chip tone={e.estado === "Activo" ? "ok" : e.estado === "Vacaciones" ? "warn" : "bad"} dot>{e.estado}</Chip>
                  </Td>
                  <Td>
                    <button onClick={() => toggleAutor(e.id)}
                      className={`text-[11.5px] font-semibold px-3 py-1.5 border transition-colors flex items-center gap-1.5 ${e.esAutor ? "border-maroon/50 text-maroon bg-maroon/10" : "border-linedark text-ink2 hover:border-ink"}`}>
                      <I n={e.esAutor ? "check" : "plus"} s={12} /> {e.esAutor ? "Firma artículos" : "Hacer autor"}
                    </button>
                  </Td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="px-4 py-3 border-t border-line text-[11.5px] text-stone flex items-center gap-2">
          <I n="users" s={13} />
          Al marcar «Firma artículos», el colaborador aparece de inmediato como autor elegible en el CMS y en la tienda.
        </div>
      </Card>

      <Modal open={openNew} onClose={() => setOpenNew(false)}>
        <div className="p-6 sm:p-8">
          <div className="flex items-center justify-between">
            <h3 className="font-bold text-[17px]">Nuevo colaborador</h3>
            <button onClick={() => setOpenNew(false)} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={16} /></button>
          </div>
          <div className="grid sm:grid-cols-2 gap-4 mt-5">
            <label className="block sm:col-span-2">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Nombre completo</span>
              <input value={nf.nombre} onChange={(e) => setNf({ ...nf, nombre: e.target.value })} className={inp} placeholder="Ej. Ana Lucía Torres" />
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Cargo</span>
              <input value={nf.cargo} onChange={(e) => setNf({ ...nf, cargo: e.target.value })} className={inp} placeholder="Ej. Carpintera" />
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Área</span>
              <select value={nf.area} onChange={(e) => setNf({ ...nf, area: e.target.value })} className={inp}>
                {["Taller", "Diseño", "Gerencia", "Finanzas", "Ventas", "Logística"].map((a) => <option key={a}>{a}</option>)}
              </select>
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Sueldo mensual (USD)</span>
              <input value={nf.sueldo} onChange={(e) => setNf({ ...nf, sueldo: e.target.value })} className={inp} placeholder="0.00" inputMode="decimal" />
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Correo</span>
              <input value={nf.email} onChange={(e) => setNf({ ...nf, email: e.target.value })} className={inp} placeholder="nombre@bletia.ec" />
            </label>
          </div>
          <button onClick={add} className={`${btnDark} w-full mt-6 !py-3.5`}><I n="plus" s={14} /> Ingresar a la nómina</button>
        </div>
      </Modal>
    </div>
  );
}

/* ================= Compras · OC + acceso de un solo uso al proveedor =================
   El "link de un solo uso" vive aquí: al enviar una Orden de Compra al proveedor
   externo, se genera un acceso único y se le envía por correo. El proveedor lo
   abre UNA vez para confirmar el pedido (stock o pedido de cliente con specs). */
export function Compras() {
  const [ocs, setOcs] = useState<CompraOC[]>(() => loadCompras());
  const [openNew, setOpenNew] = useState(false);
  const [nf, setNf] = useState({ tipo: "stock" as "stock" | "pedido_cliente", proveedor: SUPPLIERS[0].name, specs: "", destino: BODEGAS[0], sku: "", pieza: "", qty: "1", costo: "" });
  const [sel, setSel] = useState<CompraOC | null>(null);

  const persist = (next: CompraOC[]) => { setOcs(next); saveCompras(next); };

  const enviadas = ocs.filter((o) => o.estado === "Enviada");
  const porRecibir = ocs.filter((o) => o.estado === "Confirmada");
  const valorCurso = enviadas.concat(porRecibir).reduce((a, o) => a + o.total, 0);
  const linksPend = ocs.filter((o) => !o.linkUsado && o.estado !== "Anulada").length;

  const send = () => {
    const qty = parseInt(nf.qty) || 1;
    const costo = parseFloat(nf.costo) || 0;
    if (!nf.pieza.trim() || costo <= 0) return;
    const prov = SUPPLIERS.find((s) => s.name === nf.proveedor);
    const oc: CompraOC = {
      id: `oc${Date.now()}`,
      folio: `OC-${String(33 + ocs.length).padStart(4, "0")}`,
      tipo: nf.tipo,
      proveedor: nf.proveedor,
      email: prov?.contact ?? "compras@proveedor.ec",
      items: [{ sku: nf.sku.trim() || "MP-000", pieza: nf.pieza.trim(), qty, costo }],
      total: qty * costo * 1.15,
      estado: "Enviada",
      link: `PRV-${randomCode()}`,
      linkUsado: false,
      linkEnviado: "ahora",
      fecha: "hoy",
      destino: nf.destino,
      specs: nf.tipo === "pedido_cliente" ? nf.specs.trim() || undefined : undefined,
    };
    persist([oc, ...ocs]);
    toast(`OC ${oc.folio} enviada a ${oc.proveedor} · link de un uso mandado a ${oc.email}`, "ok");
    setNf({ ...nf, specs: "", sku: "", pieza: "", qty: "1", costo: "" });
    setOpenNew(false);
  };

  const markReceived = (id: string) => {
    persist(ocs.map((o) => (o.id === id ? { ...o, estado: "Recibida" } : o)));
    toast("OC recibida · ingresada a bodega y contabilizada", "ok");
  };

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Compras · Órdenes al proveedor"
        sub="Cada OC que envías genera un acceso de un solo uso que llega por correo al proveedor. Puede ser para stock o para el pedido de tu cliente con sus características."
        right={<button onClick={() => setOpenNew(true)} className={btnDark}><I n="plus" s={14} /> Nueva orden de compra</button>}
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="OC en curso" value={enviadas.length + porRecibir.length} sub={`${enviadas.length} enviadas · ${porRecibir.length} confirmadas`} />
        <Stat label="Valor en curso" value={fmt2(valorCurso)} sub="IVA incluido" />
        <Stat label="Links sin usar" value={linksPend} sub="accesos de un solo uso pendientes" />
        <Stat label="Recibidas" value={ocs.filter((o) => o.estado === "Recibida").length} sub="este mes · con asiento contable" />
      </div>

      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[980px]">
            <thead><tr><Th>OC</Th><Th>Proveedor</Th><Th>Tipo</Th><Th>Items</Th><Th>Total</Th><Th>Acceso de un uso</Th><Th>Estado</Th><Th> </Th></tr></thead>
            <tbody>
              {ocs.map((o) => (
                <tr key={o.id} className="hover:bg-paper2/50 transition-colors fade-in">
                  <Td className="font-mono text-[12px] font-semibold">{o.folio}</Td>
                  <Td>
                    <span className="font-medium block">{o.proveedor}</span>
                    <span className="text-[11px] text-stone">{o.email}</span>
                  </Td>
                  <Td>
                    <Chip tone={o.tipo === "stock" ? "neutral" : "maroon"} dot>
                      {o.tipo === "stock" ? "Para stock" : "Pedido cliente"}
                    </Chip>
                  </Td>
                  <Td className="text-[12px] text-ink2">{o.items.reduce((a, i) => a + i.qty, 0)} uds · {o.items.length} {o.items.length === 1 ? "referencia" : "referencias"}</Td>
                  <Td className="tnum font-semibold">{fmt2(o.total)}</Td>
                  <Td>
                    <span className="flex items-center gap-2">
                      <code className="text-[11px] font-mono text-ink2">{o.link}</code>
                      <CopyBtn text={`https://bletia.ec/p/${o.link}`} label="" />
                      {o.linkUsado
                        ? <Chip tone="ok">Usado</Chip>
                        : <Chip tone="warn" dot>Pendiente</Chip>}
                    </span>
                  </Td>
                  <Td>
                    <Chip tone={o.estado === "Recibida" ? "ok" : o.estado === "Confirmada" ? "maroon" : o.estado === "Enviada" ? "warn" : "bad"} dot>{o.estado}</Chip>
                  </Td>
                  <Td>
                    <button onClick={() => setSel(o)} className="text-[11.5px] font-semibold px-3 py-2 border border-linedark hover:bg-ink hover:text-paper hover:border-ink transition-colors whitespace-nowrap flex items-center gap-1.5">
                      <I n="eye" s={12} /> Ver
                    </button>
                  </Td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="px-4 py-3 border-t border-line text-[11.5px] text-stone flex items-center gap-2">
          <I n="link" s={13} />
          El link se consume al primer uso (el proveedor confirma). Si no lo ha usado, puedes reenviarlo desde el detalle.
        </div>
      </Card>

      {/* detalle de OC */}
      <Modal open={!!sel} onClose={() => setSel(null)} w="max-w-xl">
        {sel && (
          <div className="p-6 sm:p-8">
            <div className="flex items-start justify-between gap-4">
              <div>
                <p className="text-[10.5px] font-bold tracking-[0.16em] uppercase text-stone">Orden de compra · {sel.tipo === "stock" ? "para stock" : "pedido de cliente"}</p>
                <h3 className="font-bold text-[19px] mt-1 font-mono">{sel.folio}</h3>
              </div>
              <button onClick={() => setSel(null)} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={16} /></button>
            </div>

            <dl className="mt-5 space-y-2.5 text-[13px]">
              <div className="flex justify-between gap-6 border-b border-line pb-2.5"><dt className="text-stone">Proveedor</dt><dd className="font-medium text-right">{sel.proveedor}</dd></div>
              <div className="flex justify-between gap-6 border-b border-line pb-2.5"><dt className="text-stone">Enviado a</dt><dd className="font-medium text-right">{sel.email}</dd></div>
              <div className="flex justify-between gap-6 border-b border-line pb-2.5"><dt className="text-stone">Destino</dt><dd className="font-medium text-right">{sel.destino}</dd></div>
              <div className="flex justify-between gap-6 border-b border-line pb-2.5"><dt className="text-stone">Fecha</dt><dd className="font-medium text-right">{sel.fecha}</dd></div>
            </dl>

            {sel.specs && (
              <div className="mt-4 border border-maroon/30 bg-maroon/5 p-3.5">
                <p className="text-[10.5px] font-bold tracking-[0.14em] uppercase text-maroon">Características del cliente</p>
                <p className="text-[13px] mt-1.5">{sel.specs}</p>
              </div>
            )}

            <div className="mt-5 border border-linedark overflow-hidden">
              <table className="w-full">
                <thead><tr><Th>SKU</Th><Th>Pieza</Th><Th>Cant.</Th><Th>Costo</Th></tr></thead>
                <tbody>
                  {sel.items.map((i, ix) => (
                    <tr key={ix}>
                      <Td className="font-mono text-[12px]">{i.sku}</Td>
                      <Td>{i.pieza}</Td>
                      <Td className="tnum">{i.qty}</Td>
                      <Td className="tnum">{fmt2(i.costo * i.qty)}</Td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="mt-4 border border-line bg-paper2/50 p-4">
              <p className="text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone flex items-center gap-2">
                <span className={`w-1.5 h-1.5 ${sel.linkUsado ? "bg-ok" : "bg-warn"} pulse-ok`} />
                Acceso de un solo uso · enviado por correo {sel.linkEnviado}
              </p>
              <div className="flex items-center justify-between gap-3 mt-2.5">
                <code className="text-[13.5px] font-mono font-semibold break-all">bletia.ec/p/{sel.link}</code>
                <CopyBtn text={`https://bletia.ec/p/${sel.link}`} label="Copiar" />
              </div>
              <p className="text-[11.5px] text-stone mt-2">{sel.linkUsado ? "El proveedor ya confirmó con este link." : "Aún sin usar · se consume al primer clic del proveedor."}</p>
            </div>

            {!sel.linkUsado && sel.estado !== "Recibida" && (
              <button onClick={() => { markReceived(sel.id); setSel(null); }} className={`${btnDark} w-full mt-4 !py-3.5`}>
                <I n="check" s={14} /> Marcar recibida (ingresa a {sel.destino})
              </button>
            )}
          </div>
        )}
      </Modal>

      {/* nueva OC */}
      <Modal open={openNew} onClose={() => setOpenNew(false)} w="max-w-xl">
        <div className="p-6 sm:p-8">
          <div className="flex items-center justify-between">
            <h3 className="font-bold text-[17px]">Nueva orden de compra</h3>
            <button onClick={() => setOpenNew(false)} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={16} /></button>
          </div>

          <div className="grid gap-4 mt-5">
            <div>
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Tipo de compra</span>
              <div className="grid grid-cols-2 gap-2">
                <button onClick={() => setNf({ ...nf, tipo: "stock" })}
                  className={`border p-3 text-left transition-all ${nf.tipo === "stock" ? "border-maroon bg-card shadow-[inset_2px_0_0_#800000]" : "border-linedark hover:border-ink"}`}>
                  <span className="block font-semibold text-[13px]">Para stock</span>
                  <span className="block text-[11.5px] text-stone mt-0.5">Reponer inventario o materia prima</span>
                </button>
                <button onClick={() => setNf({ ...nf, tipo: "pedido_cliente" })}
                  className={`border p-3 text-left transition-all ${nf.tipo === "pedido_cliente" ? "border-maroon bg-card shadow-[inset_2px_0_0_#800000]" : "border-linedark hover:border-ink"}`}>
                  <span className="block font-semibold text-[13px]">Pedido de cliente</span>
                  <span className="block text-[11.5px] text-stone mt-0.5">Con las características que pidió</span>
                </button>
              </div>
            </div>

            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Proveedor externo</span>
              <select value={nf.proveedor} onChange={(e) => setNf({ ...nf, proveedor: e.target.value })} className={inp}>
                {SUPPLIERS.filter((s) => s.type === "Muebles").map((s) => <option key={s.id}>{s.name}</option>)}
              </select>
            </label>

            {nf.tipo === "pedido_cliente" && (
              <label className="block">
                <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Características del cliente</span>
                <textarea value={nf.specs} onChange={(e) => setNf({ ...nf, specs: e.target.value })} rows={2} className={`${inp} resize-y`} placeholder="Ej. tapiz teja, lado izquierdo, grabado de logo…" />
              </label>
            )}

            <div className="grid grid-cols-[1fr_2fr] gap-3">
              <label className="block">
                <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">SKU</span>
                <input value={nf.sku} onChange={(e) => setNf({ ...nf, sku: e.target.value })} className={`${inp} font-mono`} placeholder="MP-000" />
              </label>
              <label className="block">
                <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Pieza / descripción</span>
                <input value={nf.pieza} onChange={(e) => setNf({ ...nf, pieza: e.target.value })} className={inp} placeholder="Ej. Tablero nogal 18mm" />
              </label>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <label className="block">
                <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Cantidad</span>
                <input value={nf.qty} onChange={(e) => setNf({ ...nf, qty: e.target.value })} className={inp} inputMode="numeric" />
              </label>
              <label className="block">
                <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Costo unitario (USD)</span>
                <input value={nf.costo} onChange={(e) => setNf({ ...nf, costo: e.target.value })} className={inp} inputMode="decimal" placeholder="0.00" />
              </label>
            </div>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Destino (bodega)</span>
              <select value={nf.destino} onChange={(e) => setNf({ ...nf, destino: e.target.value })} className={inp}>
                {BODEGAS.map((b) => <option key={b}>{b}</option>)}
              </select>
            </label>
          </div>

          <button onClick={send} className={`${btnDark} w-full mt-6 !py-3.5`}>
            <I n="link" s={14} /> Enviar OC + link de un uso por correo
          </button>
          <p className="text-[11px] text-stone text-center mt-3">Se genera un acceso único y se envía al correo del proveedor. Solo puede usarse una vez.</p>
        </div>
      </Modal>
    </div>
  );
}
