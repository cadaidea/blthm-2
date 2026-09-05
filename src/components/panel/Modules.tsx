import { useMemo, useRef, useState } from "react";
import { TTL_CACHE_MS, detectarDocumento } from "../../utils/sri";
import {
  CITIES, CUSTOMERS, ORDER_FLOW, ORDERS, PRODUCTS, SUPPLIERS,
  fmt, fmt2, type Customer, type Order, type Product,
} from "../../data";
import { I, Modal } from "../ui";
import { Bar, Card, Chip, SectionTitle, Stat, Td, Th, btnDark, btnGhost, inp } from "./pui";
import { StatusChip } from "./Panel";

/* ================= OMS · Pedidos ================= */
export function OMS() {
  const [orders, setOrders] = useState<Order[]>(ORDERS);
  const [filter, setFilter] = useState<string>("Todos");

  const shown = filter === "Todos" ? orders : orders.filter((o) => o.status === filter);
  const pending = orders.filter((o) => o.status === "Pago pendiente");
  const inTaller = orders.filter((o) => o.status === "En taller");
  const inTransit = orders.filter((o) => o.status === "En transporte");

  const advance = (id: string) => {
    setOrders((os) =>
      os.map((o) => {
        if (o.id !== id) return o;
        const next = ORDER_FLOW[Math.min(ORDER_FLOW.indexOf(o.status) + 1, ORDER_FLOW.length - 1)];
        const carrier =
          next === "En transporte" && o.carrier === "—"
            ? SUPPLIERS.filter((s) => s.type === "Transporte")[Math.floor(Math.random() * 3)].name
            : o.carrier;
        return { ...o, status: next, carrier };
      }),
    );
  };

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Gestión de pedidos"
        sub="Del pago PayPhone a la entrega guante blanco. Cada avance emite un evento al cliente."
        right={
          <div className="flex flex-wrap gap-1.5">
            {["Todos", ...ORDER_FLOW].map((f) => (
              <button key={f} onClick={() => setFilter(f)}
                className={`px-3 py-1.5 text-[11.5px] font-semibold border transition-colors ${filter === f ? "bg-ink text-paper border-ink" : "border-linedark text-ink2 hover:border-ink"}`}>
                {f}
              </button>
            ))}
          </div>
        }
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Pago pendiente" value={pending.length} sub={fmt2(pending.reduce((a, o) => a + o.total, 0)) + " por cobrar"} />
        <Stat label="En taller" value={inTaller.length} sub="Fabricación propia" />
        <Stat label="En transporte" value={inTransit.length} sub="Con GPS y guía visible" />
        <Stat label="Entregados · semana" value={9} sub={<span className="text-ok font-semibold">NPS 72 · sin incidencias</span>} />
      </div>

      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[900px]">
            <thead>
              <tr><Th>Pedido</Th><Th>Cliente / Ciudad</Th><Th>Piezas</Th><Th>Pago</Th><Th>Total</Th><Th>Transporte</Th><Th>Estado</Th><Th> </Th></tr>
            </thead>
            <tbody>
              {shown.map((o) => {
                const idx = ORDER_FLOW.indexOf(o.status);
                return (
                  <tr key={o.id + o.status} className="hover:bg-paper2/50 transition-colors fade-in">
                    <Td>
                      <span className="font-mono text-[12px] font-semibold">{o.code}</span>
                      <span className="block text-[11px] text-stone">{o.date}</span>
                    </Td>
                    <Td>
                      <span className="font-medium">{o.customer}</span>
                      <span className="block text-[11px] text-stone">{o.city}</span>
                    </Td>
                    <Td className="text-ink2">{o.item}</Td>
                    <Td>
                      <span className="inline-flex items-center gap-1.5 text-[12px]">
                        <I n="card" s={13} className="text-stone" /> {o.pay}
                      </span>
                    </Td>
                    <Td className="tnum font-semibold">{fmt2(o.total)}</Td>
                    <Td className="text-[12px] text-ink2">{o.carrier}</Td>
                    <Td>
                      <StatusChip s={o.status} />
                      <div className="w-24 mt-2"><Bar value={((idx + 1) / ORDER_FLOW.length) * 100} tone={o.status === "Pago pendiente" ? "warn" : "ok"} /></div>
                    </Td>
                    <Td>
                      {o.status !== "Entregado" ? (
                        <button onClick={() => advance(o.id)}
                          className="text-[11.5px] font-semibold px-3 py-2 border border-linedark hover:bg-ink hover:text-paper hover:border-ink transition-colors whitespace-nowrap flex items-center gap-1.5">
                          Avanzar <I n="chev-r" s={11} />
                        </button>
                      ) : (
                        <span className="text-[11px] text-stone flex items-center gap-1"><I n="check" s={12} className="text-ok" /> Cerrado</span>
                      )}
                    </Td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
        <div className="px-4 py-3 border-t border-line text-[11.5px] text-stone flex items-center gap-2">
          <I n="pulse" s={13} />
          Cada cambio de estado dispara: evento al cliente (link de un uso), actualización de inventario y asiento contable automático.
        </div>
      </Card>
    </div>
  );
}

/* ---- Consulta por documento (SRI / Registro) · caché 5 min · modo offline ---- */
type DocHit = {
  limpio: string; tipo: string; valido: boolean; detalle: string;
  match: Customer | null; cached: boolean; offline: boolean;
};

function DocLookup() {
  const [q, setQ] = useState("");
  const [busy, setBusy] = useState(false);
  const [hit, setHit] = useState<DocHit | null>(null);
  const [offline, setOffline] = useState(false);
  const [cacheCount, setCacheCount] = useState(0);
  const cache = useRef(new Map<string, { r: Omit<DocHit, "cached" | "offline">; ts: number }>());

  const consultar = () => {
    const limpio = q.replace(/[\s.\-]/g, "");
    if (!limpio) return;
    setBusy(true);
    setTimeout(() => {
      const now = Date.now();
      const prev = cache.current.get(limpio);
      if (prev && now - prev.ts < TTL_CACHE_MS) {
        setHit({ ...prev.r, cached: true, offline });
      } else {
        const v = detectarDocumento(limpio);
        const match = CUSTOMERS.find((c) => c.doc.replace(/\D/g, "") === limpio) || null;
        const r = { limpio, tipo: v.tipo, valido: v.valido, detalle: v.detalle, match };
        cache.current.set(limpio, { r, ts: now });
        setCacheCount(cache.current.size);
        setHit({ ...r, cached: false, offline });
      }
      setBusy(false);
    }, offline ? 150 : 700);
  };

  const limpiarCache = () => {
    cache.current.clear();
    setCacheCount(0);
    setHit(null);
  };

  const tone = !hit ? "neutral" : hit.valido ? (hit.tipo === "Cédula" ? "neutral" : "maroon") : "bad";

  return (
    <Card className="p-5">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h3 className="font-bold text-[15px] tracking-tight flex items-center gap-2">
            <I n="search" s={16} className="text-stone" /> Consulta por documento
          </h3>
          <p className="text-[12px] text-stone mt-0.5">
            SRI / Registro Civil · Módulo 10 (cédula) y Módulo 11 (sociedades y públicas) · caché de 5 min
          </p>
        </div>
        <button
          onClick={() => setOffline(!offline)}
          className={`text-[11px] font-bold uppercase tracking-wider px-3 py-1.5 border transition-colors ${offline ? "border-warn/50 text-warn bg-warnbg" : "border-ok/40 text-ok bg-okbg"}`}
        >
          {offline ? "SRI offline · modo local" : "SRI en línea"}
        </button>
      </div>

      <div className="flex flex-col sm:flex-row gap-2.5 mt-4">
        <input
          value={q}
          onChange={(e) => setQ(e.target.value)}
          onKeyDown={(e) => e.key === "Enter" && consultar()}
          placeholder="Cédula (10) o RUC (13) · ej. 1710034065 · 1791228847001"
          className={`${inp} sm:flex-1 font-mono`}
        />
        <button onClick={consultar} disabled={busy} className={`${btnDark} !py-2.5 disabled:opacity-60 whitespace-nowrap`}>
          {busy ? "Consultando…" : "Validar documento"}
        </button>
        <button onClick={limpiarCache} className={`${btnGhost} !py-2.5 whitespace-nowrap`}>
          Limpiar caché <span className="tnum">({cacheCount})</span>
        </button>
      </div>

      {hit && (
        <div className="mt-4 border border-line bg-paper2/40 p-4 fade-in">
          <div className="flex flex-wrap items-center gap-2.5">
            <Chip tone={tone as "ok" | "warn" | "bad" | "neutral" | "maroon"}>{hit.tipo}</Chip>
            <span className={`inline-flex items-center gap-1.5 text-[12px] font-semibold ${hit.valido ? "text-ok" : "text-bad"}`}>
              <I n={hit.valido ? "check" : "alert"} s={13} />
              {hit.valido ? "Válido" : "Inválido"}
            </span>
            {hit.cached && (
              <span className="text-[10.5px] font-bold uppercase tracking-wider text-stone bg-paper2 px-2 py-1">
                Respuesta desde caché · TTL 5 min
              </span>
            )}
          </div>
          <p className="text-[13px] mt-2.5 font-mono">{hit.limpio}</p>
          <p className={`text-[12.5px] mt-1 ${hit.valido ? "text-ink2" : "text-bad"}`}>{hit.detalle}</p>
          {hit.offline && (
            <p className="text-[12px] text-warn font-medium mt-2 flex items-center gap-1.5">
              <I n="alert" s={13} /> SRI no disponible — se aplicó validación local estricta (sin consulta externa).
            </p>
          )}
          {hit.match ? (
            <div className="mt-3 pt-3 border-t border-line flex flex-wrap items-center justify-between gap-2">
              <p className="text-[13px]">
                <strong>{hit.match.name}</strong>
                <span className="text-stone"> · {hit.match.city} · {hit.match.segment} · {hit.match.orders} pedidos · LTV {fmt(hit.match.ltv)}</span>
              </p>
              <span className="text-[11px] font-bold uppercase tracking-wider text-ok bg-okbg px-2 py-1">Cliente en base</span>
            </div>
          ) : (
            <p className="text-[12px] text-stone mt-3 pt-3 border-t border-line">
              Sin coincidencias en la base local de BLETIA{hit.valido ? " — documento apto para crear ficha nueva." : "."}
            </p>
          )}
        </div>
      )}
    </Card>
  );
}

/* ================= CRM · Clientes ================= */
export function CRM() {
  const [list, setList] = useState<Customer[]>(CUSTOMERS);
  const [q, setQ] = useState("");
  const [seg, setSeg] = useState("Todos");
  const [sel, setSel] = useState<Customer | null>(null);
  const [openNew, setOpenNew] = useState(false);
  const [nf, setNf] = useState({ name: "", doc: "", city: CITIES[0], segment: "Residencial" as Customer["segment"] });
  const [genLink, setGenLink] = useState<string | null>(null);
  const [nfErr, setNfErr] = useState("");
  const nfDocV = nf.doc.trim() ? detectarDocumento(nf.doc) : null;

  const shown = useMemo(
    () =>
      list.filter(
        (c) =>
          (seg === "Todos" || c.segment === seg) &&
          (c.name.toLowerCase().includes(q.toLowerCase()) || c.city.toLowerCase().includes(q.toLowerCase())),
      ),
    [list, q, seg],
  );

  const addCustomer = () => {
    if (nf.name.trim().length < 3) return setNfErr("Ingresa el nombre o razón social.");
    if (nf.doc.trim()) {
      const v = detectarDocumento(nf.doc);
      if (!v.valido) return setNfErr(`Documento inválido: ${v.detalle}`);
    }
    setNfErr("");
    const c: Customer = {
      id: `c${Date.now()}`, name: nf.name.trim(), contact: "—", city: nf.city,
      segment: nf.segment, orders: 0, ltv: 0, last: "ahora", doc: nf.doc || "pendiente",
      timeline: [{ date: "hoy", text: "Ficha creada manualmente desde el CRM", kind: "nota" }],
    };
    setList((l) => [c, ...l]);
    setNf({ name: "", doc: "", city: CITIES[0], segment: "Residencial" });
    setOpenNew(false);
    setSel(c);
  };

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Relación con clientes"
        sub={`${list.length} fichas · segmentos residencial, arquitectura, hotelería y corporativo.`}
        right={
          <button onClick={() => setOpenNew(true)} className={btnDark}>
            <I n="plus" s={14} /> Nuevo cliente
          </button>
        }
      />

      <DocLookup />

      <Card className="p-4 flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <I n="search" s={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-stone" />
          <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Buscar por nombre o ciudad…"
            className={`${inp} pl-9`} />
        </div>
        <div className="flex gap-1.5 flex-wrap">
          {["Todos", "Residencial", "Arquitecto", "Hotelero", "Corporativo"].map((s) => (
            <button key={s} onClick={() => setSeg(s)}
              className={`px-3 py-2 text-[11.5px] font-semibold border transition-colors ${seg === s ? "bg-ink text-paper border-ink" : "border-linedark text-ink2 hover:border-ink"}`}>
              {s}
            </button>
          ))}
        </div>
      </Card>

      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[760px]">
            <thead><tr><Th>Cliente</Th><Th>Segmento</Th><Th>Ciudad</Th><Th>Pedidos</Th><Th>LTV</Th><Th>Última actividad</Th><Th> </Th></tr></thead>
            <tbody>
              {shown.map((c) => (
                <tr key={c.id} onClick={() => { setSel(c); setGenLink(null); }} className="hover:bg-paper2/50 transition-colors cursor-pointer fade-in">
                  <Td>
                    <span className="font-medium">{c.name}</span>
                    <span className="block text-[11px] text-stone">{c.contact}</span>
                  </Td>
                  <Td><Chip tone={c.segment === "Hotelero" || c.segment === "Corporativo" ? "maroon" : "neutral"}>{c.segment}</Chip></Td>
                  <Td>{c.city}</Td>
                  <Td className="tnum">{c.orders}</Td>
                  <Td className="tnum font-semibold">{fmt(c.ltv)}</Td>
                  <Td className="text-[12px] text-stone">{c.last}</Td>
                  <Td><I n="chev-r" s={14} className="text-stone" /></Td>
                </tr>
              ))}
            </tbody>
          </table>
          {shown.length === 0 && (
            <p className="p-10 text-center text-[13px] text-stone">Sin resultados para «{q}». Prueba con otro nombre o limpia el segmento.</p>
          )}
        </div>
      </Card>

      {/* Drawer detalle */}
      {sel && (
        <div className="fixed inset-0 z-[85]">
          <div className="absolute inset-0 bg-ink/45 fade-in" onClick={() => setSel(null)} />
          <aside className="absolute right-0 top-0 h-full w-full max-w-[400px] bg-paper border-l border-line slide-in-right flex flex-col">
            <div className="flex items-center justify-between px-6 h-16 border-b border-line shrink-0">
              <p className="text-[11px] font-bold tracking-[0.16em] uppercase text-stone">Ficha de cliente</p>
              <button onClick={() => setSel(null)} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={17} /></button>
            </div>
            <div className="flex-1 overflow-y-auto px-6 py-5">
              <div className="flex items-start justify-between gap-4">
                <div>
                  <h3 className="font-bold text-[17px] leading-tight">{sel.name}</h3>
                  <p className="text-[12px] text-stone mt-1">{sel.contact}</p>
                  <p className="text-[11.5px] text-stone font-mono mt-0.5">{sel.doc}</p>
                </div>
                <span className="w-10 h-10 bg-ink text-paper text-[12px] font-bold flex items-center justify-center shrink-0">
                  {sel.name.split(" ").slice(0, 2).map((w) => w[0]).join("")}
                </span>
              </div>
              <div className="grid grid-cols-3 gap-px bg-line border border-line mt-5">
                {[["Pedidos", String(sel.orders)], ["LTV", fmt(sel.ltv)], ["Ciudad", sel.city]].map(([k, v]) => (
                  <div key={k} className="bg-card p-3 text-center">
                    <p className="text-[9.5px] font-bold tracking-[0.14em] uppercase text-stone">{k}</p>
                    <p className="font-semibold text-[13.5px] mt-1 tnum">{v}</p>
                  </div>
                ))}
              </div>

              <p className="text-[11px] font-bold tracking-[0.16em] uppercase text-stone mt-6 mb-3">Línea de tiempo</p>
              <div className="space-y-0">
                {sel.timeline.map((t, i) => (
                  <div key={i} className="flex gap-3.5">
                    <div className="flex flex-col items-center">
                      <span className={`w-2.5 h-2.5 rounded-full mt-1 ${t.kind === "pago" ? "bg-maroon" : t.kind === "entrega" ? "bg-ok" : "bg-linedark"}`} />
                      {i < sel.timeline.length - 1 && <span className="w-px flex-1 bg-line" />}
                    </div>
                    <div className="pb-5">
                      <p className="text-[12.5px] font-medium leading-snug">{t.text}</p>
                      <p className="text-[11px] text-stone mt-0.5">{t.date}</p>
                    </div>
                  </div>
                ))}
              </div>

              {genLink && (
                <div className="border border-maroon/30 bg-maroon/5 p-3.5 fade-in">
                  <p className="text-[10.5px] font-bold tracking-[0.14em] uppercase text-maroon">Link PayPhone de un uso</p>
                  <p className="font-mono text-[12.5px] font-semibold mt-1.5 break-all">pay.bletia.ec/l/{genLink}</p>
                  <p className="text-[11px] text-stone mt-1.5">Enviado por WhatsApp · expira en 24 h · también quedó en el módulo Enlaces.</p>
                </div>
              )}
            </div>
            <div className="border-t border-line p-4 grid grid-cols-2 gap-2.5 shrink-0">
              <button onClick={() => setGenLink(`${Math.random().toString(36).slice(2, 6).toUpperCase()}-${Math.random().toString(36).slice(2, 6).toUpperCase()}`)}
                className={`${btnGhost} !py-3 text-[12px]`}>
                <I n="link" s={14} /> Link de pago
              </button>
              <button className={`${btnDark} !py-3 text-[12px]`}>
                <I n="doc" s={14} /> Nueva cotización
              </button>
            </div>
          </aside>
        </div>
      )}

      {/* Nuevo cliente */}
      <Modal open={openNew} onClose={() => setOpenNew(false)}>
        <div className="p-6 sm:p-8">
          <div className="flex items-center justify-between">
            <h3 className="font-bold text-[17px]">Nuevo cliente</h3>
            <button onClick={() => setOpenNew(false)} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={16} /></button>
          </div>
          <div className="grid sm:grid-cols-2 gap-4 mt-5">
            <label className="block sm:col-span-2">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Nombre / Razón social</span>
              <input value={nf.name} onChange={(e) => setNf({ ...nf, name: e.target.value })} className={inp} placeholder="Ej. Hotel Río Verde" />
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Cédula / RUC</span>
              <input value={nf.doc} onChange={(e) => setNf({ ...nf, doc: e.target.value })} className={`${inp} font-mono`} placeholder="10 o 13 dígitos" />
              {nfDocV && (
                <span className={`mt-1.5 inline-flex items-center gap-1.5 text-[11px] font-semibold ${nfDocV.valido ? "text-ok" : "text-bad"}`}>
                  <I n={nfDocV.valido ? "check" : "alert"} s={12} />
                  {nfDocV.tipo} · {nfDocV.valido ? "válido" : nfDocV.detalle}
                </span>
              )}
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Ciudad</span>
              <select value={nf.city} onChange={(e) => setNf({ ...nf, city: e.target.value })} className={inp}>
                {CITIES.map((c) => <option key={c}>{c}</option>)}
              </select>
            </label>
            <label className="block sm:col-span-2">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Segmento</span>
              <div className="flex gap-1.5 flex-wrap">
                {(["Residencial", "Arquitecto", "Hotelero", "Corporativo"] as const).map((s) => (
                  <button key={s} onClick={() => setNf({ ...nf, segment: s })}
                    className={`px-3 py-2 text-[11.5px] font-semibold border transition-colors ${nf.segment === s ? "bg-ink text-paper border-ink" : "border-linedark text-ink2 hover:border-ink"}`}>
                    {s}
                  </button>
                ))}
              </div>
            </label>
          </div>
          {nfErr && <p className="text-bad text-[12px] font-medium mt-4 flex items-center gap-2"><I n="alert" s={13} />{nfErr}</p>}
          <button onClick={addCustomer} className={`${btnDark} w-full mt-6 !py-3.5`}>
            <I n="plus" s={14} /> Crear ficha
          </button>
        </div>
      </Modal>
    </div>
  );
}

/* ================= PIM · Productos ================= */
export function PIM() {
  const [list, setList] = useState<Product[]>(PRODUCTS);
  const [openNew, setOpenNew] = useState(false);
  const [np, setNp] = useState({ name: "", sku: "", category: "Sofás" as Product["category"], price: "", material: "" });

  const toggle = (id: string) =>
    setList((l) =>
      l.map((p) =>
        p.id === id
          ? { ...p, state: p.state === "Publicado" ? "Borrador" : "Publicado" }
          : p,
      ),
    );

  const add = () => {
    const price = parseFloat(np.price);
    if (np.name.trim().length < 2 || !price || price <= 0) return;
    const p: Product = {
      id: `p${Date.now()}`, sku: np.sku.trim() || `BLT-${900 + list.length}`, name: np.name.trim(),
      category: np.category, price, material: np.material.trim() || "Por definir en ficha",
      dims: "—", img: "", stock: 0, state: "Borrador", origin: "Taller BLETIA",
      lead: "Por estimar", desc: "Ficha creada desde el PIM; completa materiales y dimensiones antes de publicar.",
      channels: ["Showroom"],
    };
    setList((l) => [p, ...l]);
    setNp({ name: "", sku: "", category: "Sofás", price: "", material: "" });
    setOpenNew(false);
  };

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Información de producto"
        sub="Una sola fuente de verdad: web, showroom y catálogo mayorista consumen estas fichas."
        right={<button onClick={() => setOpenNew(true)} className={btnDark}><I n="plus" s={14} /> Nueva ficha</button>}
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Referencias" value={list.length} sub="7 colecciones activas" />
        <Stat label="Publicadas en web" value={list.filter((p) => p.state === "Publicado").length} sub="Sincronizadas en <1 s" />
        <Stat label="En fabricación" value={list.filter((p) => p.state === "En taller").length} sub="Series numeradas" />
        <Stat label="Valor de inventario" value="$86.4k" sub="Costo · IVA excluido" />
      </div>

      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[880px]">
            <thead><tr><Th>Producto</Th><Th>Categoría</Th><Th>Precio (IVA incl.)</Th><Th>Stock</Th><Th>Origen</Th><Th>Canales</Th><Th>Estado</Th></tr></thead>
            <tbody>
              {list.map((p) => (
                <tr key={p.id} className="hover:bg-paper2/50 transition-colors fade-in">
                  <Td>
                    <div className="flex items-center gap-3">
                      <span className="w-10 h-12 bg-paper2 overflow-hidden shrink-0 border border-line flex items-center justify-center">
                        {p.img ? <img src={p.img} alt="" className="w-full h-full object-cover" /> : <I n="image" s={15} className="text-stone" />}
                      </span>
                      <span>
                        <span className="font-medium block">{p.name}</span>
                        <span className="text-[11px] text-stone font-mono">{p.sku}</span>
                      </span>
                    </div>
                  </Td>
                  <Td className="text-ink2">{p.category}</Td>
                  <Td>
                    <span className="tnum font-semibold">{fmt(p.price)}</span>
                    <span className="block text-[11px] text-stone tnum">base {fmt2(p.price / 1.15)}</span>
                  </Td>
                  <Td>
                    <span className={`tnum font-semibold ${p.stock <= 3 ? "text-warn" : ""}`}>{p.stock}</span>
                    {p.stock <= 3 && <span className="block text-[10.5px] text-warn">reponer</span>}
                  </Td>
                  <Td>
                    <span className="flex items-center gap-1.5 text-[12px]">
                      {p.origin === "Taller BLETIA" && <span className="w-1.5 h-1.5 bg-maroon" />}
                      {p.origin === "Taller BLETIA" ? "Taller" : "Proveedor"}
                    </span>
                  </Td>
                  <Td>
                    <div className="flex gap-1 flex-wrap">
                      {p.channels.map((c) => (
                        <span key={c} className="text-[10px] font-bold uppercase tracking-wider bg-paper2 text-ink2 px-1.5 py-0.5">{c}</span>
                      ))}
                    </div>
                  </Td>
                  <Td>
                    <button onClick={() => toggle(p.id)} className="cursor-pointer" title="Cambiar estado">
                      <StatusChip s={p.state} />
                    </button>
                  </Td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="px-4 py-3 border-t border-line text-[11.5px] text-stone flex items-center gap-2">
          <I n="tag" s={13} />
          Los precios incluyen IVA 15%. Un cambio aquí se propaga a la web, al catálogo PDF y a contabilidad como un solo evento PIM.
        </div>
      </Card>

      <Modal open={openNew} onClose={() => setOpenNew(false)}>
        <div className="p-6 sm:p-8">
          <div className="flex items-center justify-between">
            <h3 className="font-bold text-[17px]">Nueva ficha de producto</h3>
            <button onClick={() => setOpenNew(false)} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={16} /></button>
          </div>
          <div className="grid sm:grid-cols-2 gap-4 mt-5">
            <label className="block sm:col-span-2">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Nombre</span>
              <input value={np.name} onChange={(e) => setNp({ ...np, name: e.target.value })} className={inp} placeholder="Ej. Banco Río" />
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">SKU</span>
              <input value={np.sku} onChange={(e) => setNp({ ...np, sku: e.target.value })} className={inp} placeholder="BLT-XXX (opcional)" />
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Categoría</span>
              <select value={np.category} onChange={(e) => setNp({ ...np, category: e.target.value as Product["category"] })} className={inp}>
                {["Asientos", "Mesas", "Almacenaje", "Descanso"].map((c) => <option key={c}>{c}</option>)}
              </select>
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Precio final USD (IVA incl.)</span>
              <input value={np.price} onChange={(e) => setNp({ ...np, price: e.target.value })} className={inp} placeholder="0.00" inputMode="decimal" />
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Material</span>
              <input value={np.material} onChange={(e) => setNp({ ...np, material: e.target.value })} className={inp} placeholder="Ej. Nogal · cuero" />
            </label>
          </div>
          <button onClick={add} className={`${btnDark} w-full mt-6 !py-3.5`}>
            <I n="plus" s={14} /> Crear como borrador
          </button>
        </div>
      </Modal>
    </div>
  );
}
