import { useMemo, useRef, useState } from "react";
import { ORDERS, fmt2, productosActivos } from "../../data";
import { I, Modal, toast } from "../ui";
import { Card, Chip, SectionTitle, Stat, Td, Th, btnDark, btnGhost, inp } from "./pui";
import { StatusChip } from "./Panel";

/* ---------- Máquina de 15 estados BLETIA ---------- */
const ST15 = [
  "Cotización", "Cot. enviada", "Aprobada", "Anticipo pend.", "Anticipo recibido",
  "Ord. fabricación", "En corte", "En ensamble", "En tapizado", "Control calidad",
  "Lista despacho", "Guía emitida", "En transporte", "Entregado", "Facturado",
] as const;

/* mapea los 5 estados legacy del seed a su posición en la máquina de 15 */
const MAP: Record<string, number> = {
  "Pago pendiente": 3, "Pago aprobado": 4, "En taller": 6, "En transporte": 12, "Entregado": 13,
};

type Row = { id: string; code: string; customer: string; item: string; total: number; state: number; tipo: "Venta stock" | "Venta pedido"; spec?: string };

/* ---------- Cargador de foto por campo (el fix solicitado) ----------
   Cada cargador lleva el nombre del campo visible, así siempre se sabe
   a qué campo corresponde la foto (ej. "Tapiz principal"). */
function CampoFoto({ field, value, onChange }: {
  field: string; value: string | null; onChange: (v: string | null) => void;
}) {
  const ref = useRef<HTMLInputElement>(null);
  return (
    <div className="border border-linedark bg-card p-3">
      <div className="flex items-center justify-between gap-2 mb-2">
        <span className="text-[12px] font-semibold flex items-center gap-1.5">
          <I n="image" s={13} className="text-maroon" /> {field}
        </span>
        {value && (
          <button onClick={() => onChange(null)} className="text-[10.5px] font-semibold text-bad hover:underline flex items-center gap-1">
            <I n="close" s={10} /> quitar
          </button>
        )}
      </div>
      <input ref={ref} type="file" accept="image/*" className="hidden"
        onChange={(e) => {
          const f = e.target.files?.[0];
          if (f) onChange(URL.createObjectURL(f));
          e.target.value = "";
        }} />
      {value ? (
        <div className="relative group">
          <img src={value} alt={`Foto de ${field}`} className="w-full h-24 object-cover" />
          <span className="absolute inset-x-0 bottom-0 bg-ink/70 text-cream text-[10px] px-2 py-1 opacity-0 group-hover:opacity-100 transition-opacity">
            {field} · adjunta ✓
          </span>
        </div>
      ) : (
        <button onClick={() => ref.current?.click()}
          className="w-full h-24 border border-dashed border-linedark hover:border-maroon hover:bg-paper2/50 transition-colors flex flex-col items-center justify-center gap-1.5 text-stone">
          <I n="plus" s={16} />
          <span className="text-[11px] font-semibold">Subir foto · {field}</span>
        </button>
      )}
    </div>
  );
}

const CAMPOS_FOTO = ["Tapiz principal", "Tapiz secundario", "Estructura / madera", "Patas / acabado", "Plano / medidas"];

export function OMS15() {
  const [rows, setRows] = useState<Row[]>(() =>
    ORDERS.map((o) => ({ id: o.id, code: o.code, customer: o.customer, item: o.item, total: o.total, state: MAP[o.status] ?? 0, tipo: o.tipo, spec: o.spec })),
  );
  const [sel, setSel] = useState<string>(ORDERS[0].id);
  const [openSpec, setOpenSpec] = useState(false);
  const [openStock, setOpenStock] = useState(false);
  const [fotos, setFotos] = useState<Record<string, string | null>>({});
  /* venta por pedido (specs) */
  const [specCliente, setSpecCliente] = useState("");
  const [specPieza, setSpecPieza] = useState("");
  /* venta de stock */
  const stockables = useMemo(() => productosActivos().filter((p) => p.stock > 0 && p.state === "Publicado"), []);
  const [stockId, setStockId] = useState(stockables[0]?.id ?? "");
  const [stockQty, setStockQty] = useState("1");

  /* crea una venta de stock (sale de bodega, entra en estado 5 = pago aprobado) */
  const crearVentaStock = () => {
    const p = stockables.find((x) => x.id === stockId);
    const qty = Math.max(1, parseInt(stockQty) || 1);
    if (!p) return;
    const code = `BL-2026-0${150 + rows.length}`;
    setRows((rs) => [{ id: `vs${Date.now()}`, code, customer: "Mostrador / web", item: `${qty} × ${p.name}`, total: p.price * qty, state: 4, tipo: "Venta stock" }, ...rs]);
    toast(`Venta de stock ${code} · ${qty} × ${p.name} (descuenta inventario)`, "ok");
    setOpenStock(false);
  };

  /* crea una venta por pedido (fabricación con specs, entra en estado 1 = cotización) */
  const crearVentaPedido = () => {
    if (!specCliente.trim() || !specPieza.trim()) return;
    const code = `BL-2026-0${150 + rows.length}`;
    setRows((rs) => [{ id: `vp${Date.now()}`, code, customer: specCliente.trim(), item: specPieza.trim(), total: 0, state: 0, tipo: "Venta pedido", spec: `${fotosAdjuntas} fotos de specs adjuntas` }, ...rs]);
    toast(`Venta por pedido ${code} enviada a cotización (estado 1/15)`, "ok");
    setSpecCliente(""); setSpecPieza(""); setFotos({});
    setOpenSpec(false);
  };

  const selected = rows.find((r) => r.id === sel) || rows[0];
  const inTaller = rows.filter((r) => r.state >= 5 && r.state <= 10).length;
  const enRuta = rows.filter((r) => r.state === 12).length;
  const cerrados = rows.filter((r) => r.state >= 13).length;

  const advance = (id: string) =>
    setRows((rs) => rs.map((r) => {
      if (r.id !== id) return r;
      const next = Math.min(r.state + 1, ST15.length - 1);
      toast(`${r.code} → ${ST15[next]}`, next >= 13 ? "ok" : "info");
      return { ...r, state: next };
    }));

  const fotosAdjuntas = useMemo(() => Object.values(fotos).filter(Boolean).length, [fotos]);

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Pedidos · máquina de 15 estados"
        sub="De la cotización a la factura. Cada avance emite un evento al cliente y actualiza inventario y contabilidad."
        right={
          <div className="flex gap-2">
            <button onClick={() => setOpenStock(true)} className={btnGhost}><I n="box" s={14} /> Venta de stock</button>
            <button onClick={() => setOpenSpec(true)} className={btnDark}><I n="hammer" s={14} /> Venta por pedido</button>
          </div>
        }
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="En fabricación" value={inTaller} sub="Estados 6–10 de la máquina" />
        <Stat label="En transporte" value={enRuta} sub="Con guía SRI emitida" />
        <Stat label="Entregados / facturados" value={cerrados} sub="Ciclo completo" />
        <Stat label="Estados de la máquina" value={ST15.length} sub="Trazabilidad punta a punta" />
      </div>

      {/* línea de estados del pedido seleccionado */}
      <Card className="p-5 sm:p-6">
        <div className="flex flex-wrap items-center justify-between gap-3 mb-5">
          <div>
            <h3 className="font-bold text-[15px] tracking-tight">Trazabilidad · {selected.code}</h3>
            <p className="text-[12px] text-stone mt-0.5">{selected.customer} · {selected.item}</p>
          </div>
          <span className="text-[12px] font-semibold text-maroon">
            Estado {selected.state + 1}/15 · {ST15[selected.state]}
          </span>
        </div>
        <div className="overflow-x-auto pb-2">
          <div className="flex items-start gap-1.5 min-w-[820px]">
            {ST15.map((s, i) => {
              const done = i < selected.state;
              const current = i === selected.state;
              return (
                <div key={s} className="flex-1 min-w-[52px]">
                  <div className={`h-1.5 ${done ? "bg-ink" : current ? "bg-maroon" : "bg-paper2"}`} />
                  <p className={`text-[9.5px] font-bold uppercase tracking-wide mt-1.5 leading-tight ${current ? "text-maroon" : done ? "text-ink" : "text-stone/60"}`}>
                    {i + 1}. {s}
                  </p>
                </div>
              );
            })}
          </div>
        </div>
      </Card>

      {/* tabla */}
      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[820px]">
            <thead><tr><Th>Pedido</Th><Th>Cliente</Th><Th>Piezas</Th><Th>Tipo</Th><Th>Total</Th><Th>Estado (15)</Th><Th> </Th></tr></thead>
            <tbody>
              {rows.map((r) => (
                <tr key={r.id + r.state} onClick={() => setSel(r.id)}
                  className={`hover:bg-paper2/50 transition-colors cursor-pointer ${sel === r.id ? "bg-paper2/60" : ""}`}>
                  <Td className="font-mono text-[12px] font-semibold">{r.code}</Td>
                  <Td className="font-medium">{r.customer}</Td>
                  <Td className="text-ink2">{r.item}</Td>
                  <Td>
                    <Chip tone={r.tipo === "Venta stock" ? "neutral" : "maroon"} dot>
                      {r.tipo === "Venta stock" ? "Stock" : "Pedido"}
                    </Chip>
                  </Td>
                  <Td className="tnum font-semibold">{r.total ? fmt2(r.total) : "—"}</Td>
                  <Td>
                    <div className="flex items-center gap-2.5">
                      <StatusChip s={r.state >= 13 ? "Entregado" : r.state === 12 ? "En transporte" : r.state >= 5 ? "En taller" : r.state >= 4 ? "Pago aprobado" : "Pago pendiente"} />
                      <span className="text-[10.5px] text-stone tnum shrink-0">{r.state + 1}/15</span>
                    </div>
                  </Td>
                  <Td>
                    {r.state < ST15.length - 1 ? (
                      <button onClick={(e) => { e.stopPropagation(); advance(r.id); }}
                        className={`${btnGhost} !py-1.5 text-[11.5px] whitespace-nowrap`}>
                        Avanzar <I n="chev-r" s={11} />
                      </button>
                    ) : (
                      <span className="text-[11px] text-ok flex items-center gap-1"><I n="check" s={12} /> Cerrado</span>
                    )}
                  </Td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="px-4 py-3 border-t border-line text-[11.5px] text-stone flex items-center gap-2">
          <I n="pulse" s={13} />
          Haz clic en una fila para ver su línea de 15 estados arriba. El estado 11 emite la guía de remisión automáticamente.
        </div>
      </Card>

      {/* pedido bajo specs */}
      <Modal open={openSpec} onClose={() => setOpenSpec(false)} w="max-w-3xl">
        <div className="p-6 sm:p-8">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="font-bold text-[17px]">Pedido bajo specs · personalización con fotos</h3>
            </div>
            <button onClick={() => setOpenSpec(false)} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={16} /></button>
          </div>
          <p className="text-[12.5px] text-stone mt-2">
            Cada campo de personalización tiene su propio cargador de foto, etiquetado con el nombre del campo:
            siempre sabrás a cuál corresponde cada imagen.
          </p>

          <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-6">
            {CAMPOS_FOTO.map((f) => (
              <CampoFoto key={f} field={f} value={fotos[f] ?? null}
                onChange={(v) => setFotos((p) => ({ ...p, [f]: v }))} />
            ))}
            <div className="border border-linedark bg-card p-3 flex flex-col items-center justify-center text-center">
              <span className={`font-display font-medium text-[26px] tnum ${fotosAdjuntas ? "text-ok" : "text-stone"}`}>{fotosAdjuntas}</span>
              <span className="text-[11px] font-semibold text-stone mt-1">fotos adjuntas<br />de {CAMPOS_FOTO.length} campos</span>
            </div>
          </div>

          <div className="grid sm:grid-cols-2 gap-3 mt-5">
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Cliente</span>
              <input value={specCliente} onChange={(e) => setSpecCliente(e.target.value)} className={inp} placeholder="Ej. Hotel Casa del Patio" />
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Pieza / referencia</span>
              <input value={specPieza} onChange={(e) => setSpecPieza(e.target.value)} className={inp} placeholder="Ej. Silla Vela · 18 unidades" />
            </label>
          </div>

          <div className="flex items-center justify-between gap-3 mt-6">
            <p className="text-[11.5px] text-stone flex items-center gap-1.5">
              <I n="shield" s={12} /> Las fotos viajan al DAM y quedan vinculadas a la ficha del pedido.
            </p>
            <button onClick={crearVentaPedido} disabled={!specCliente.trim() || !specPieza.trim()} className={`${btnDark} !py-3 disabled:opacity-50`}>
              <I n="check" s={14} /> Crear venta por pedido (1/15)
            </button>
          </div>
        </div>
      </Modal>

      {/* venta de stock */}
      <Modal open={openStock} onClose={() => setOpenStock(false)} w="max-w-md">
        <div className="p-6 sm:p-7">
          <div className="flex items-center justify-between">
            <h3 className="font-bold text-[17px]">Venta de stock</h3>
            <button onClick={() => setOpenStock(false)} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={16} /></button>
          </div>
          <p className="text-[12.5px] text-stone mt-2">Sale directo de bodega y descuenta inventario. Entra en estado 5 (pago aprobado).</p>
          <label className="block mt-5">
            <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Pieza con stock</span>
            <select value={stockId} onChange={(e) => setStockId(e.target.value)} className={inp}>
              {stockables.map((p) => <option key={p.id} value={p.id}>{p.name} · {p.stock} en stock · {fmt2(p.price)}</option>)}
            </select>
          </label>
          <label className="block mt-3">
            <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Cantidad</span>
            <input value={stockQty} onChange={(e) => setStockQty(e.target.value)} className={inp} inputMode="numeric" />
          </label>
          <button onClick={crearVentaStock} className={`${btnDark} w-full mt-5 !py-3.5`}>
            <I n="check" s={14} /> Registrar venta de stock
          </button>
        </div>
      </Modal>
    </div>
  );
}
