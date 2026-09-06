import { useMemo, useState } from "react";
import { CASHFLOW, INVOICES, LINKS_SEED, fmt2, loadSite, randomCode, saveSite, type PayLink } from "../../data";
import { CodeBlock, CopyBtn, I, toast } from "../ui";
import { Card, Chip, SectionTitle, Stat, Td, Th, btnDark, btnGhost, inp } from "./pui";
import { StatusChip } from "./Panel";

/* ================= Contabilidad ================= */
export function Contabilidad() {
  const max = Math.max(...CASHFLOW.map((c) => c.in));
  const [tab, setTab] = useState<"facturas" | "partida" | "f104">("facturas");

  /* ---- Datos del emisor (campo de facturación) ---- */
  const [legal, setLegal] = useState(() => loadSite().legal);
  const [legalSaved, setLegalSaved] = useState(false);
  const guardarLegal = () => {
    saveSite({ ...loadSite(), legal });
    setLegalSaved(true);
    toast("Datos del emisor guardados para la facturación", "ok");
    setTimeout(() => setLegalSaved(false), 1600);
  };

  /* ---- Libro diario (partida doble) derivado de la facturación ---- */
  const asientos = useMemo(() => {
    const rows: { fecha: string; cuenta: string; concepto: string; debe: number; haber: number }[] = [];
    INVOICES.forEach((f) => {
      rows.push({ fecha: f.date, cuenta: "102.01 Bancos", concepto: `Cobro ${f.number} · ${f.customer}`, debe: f.total, haber: 0 });
      rows.push({ fecha: f.date, cuenta: "401.01 Ventas locales", concepto: `Venta ${f.number} · ${f.customer}`, debe: 0, haber: f.base });
      rows.push({ fecha: f.date, cuenta: "201.05 IVA en ventas", concepto: `IVA 15% ${f.number}`, debe: 0, haber: f.iva });
    });
    return rows;
  }, []);
  const totDebe = asientos.reduce((a, r) => a + r.debe, 0);
  const totHaber = asientos.reduce((a, r) => a + r.haber, 0);

  /* ---- Formulario 104 (borrador) ---- */
  const f104 = useMemo(() => {
    const aut = INVOICES.filter((f) => f.status === "Autorizada");
    const ventas15 = aut.reduce((a, f) => a + f.base, 0);
    const ivaVentas = aut.reduce((a, f) => a + f.iva, 0);
    const retenciones = 1214; // retenciones emitidas a proveedores (demo)
    return { ventas15, ivaVentas, retenciones, impuesto: Math.max(ivaVentas - retenciones, 0), n: aut.length };
  }, []);

  const exportCsv = () => {
    const rows = [
      ["Numero", "Fecha", "Cliente", "RUC", "Base", "IVA_15", "Total", "Autorizacion_SRI", "Estado"],
      ...INVOICES.map((f) => [f.number, f.date, f.customer, f.ruc, f.base.toFixed(2), f.iva.toFixed(2), f.total.toFixed(2), f.auth, f.status]),
    ];
    const csv = rows.map((r) => r.join(",")).join("\n");
    const blob = new Blob(["\uFEFF" + csv], { type: "text/csv;charset=utf-8" });
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "bletia_facturacion_2026-02.csv";
    a.click();
    URL.revokeObjectURL(a.href);
  };

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Contabilidad · Ecuador"
        sub="Facturación electrónica SRI, IVA 15% y retenciones. Cada pago PayPhone genera su asiento."
        right={
          <button onClick={exportCsv} className={btnGhost}>
            <I n="doc" s={14} /> Exportar CSV para el contador
          </button>
        }
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Ingresos febrero" value="$48.600" sub={<span className="text-ok font-semibold">▲ 8,2% vs enero</span>} />
        <Stat label="IVA por declarar" value="$6.348" sub="Declaración mensual SRI" />
        <Stat label="Retenciones emitidas" value="$1.214" sub="A proveedores de transporte" />
        <Stat label="Margen bruto" value="41,3%" sub="Mezcla taller + curaduría" />
      </div>

      {/* Campo de facturación: datos del emisor (se usan al autorizar cada factura SRI) */}
      <Card className="p-5 sm:p-6">
        <div className="flex flex-wrap items-center justify-between gap-3 mb-1">
          <div>
            <h3 className="font-bold text-[15px] tracking-tight flex items-center gap-2">
              <I n="calc" s={16} className="text-maroon" /> Datos del emisor · facturación electrónica
            </h3>
            <p className="text-[12px] text-stone mt-0.5">
              Este emisor firma cada factura, nota de crédito y guía de remisión que autoriza el SRI a nombre de BLETIA.
            </p>
          </div>
          <button onClick={guardarLegal} className={`${btnDark} ${legalSaved ? "!bg-ok" : ""}`}>
            <I n={legalSaved ? "check" : "doc"} s={14} /> {legalSaved ? "Guardado" : "Guardar emisor"}
          </button>
        </div>
        <div className="grid sm:grid-cols-2 xl:grid-cols-3 gap-4 mt-4">
          {([
            ["razonSocial", "Razón social"],
            ["ruc", "RUC"],
            ["direccion", "Domicilio fiscal"],
            ["sri", "Régimen / SRI"],
            ["moneda", "Moneda e IVA"],
            ["pagos", "Pasarela de cobro"],
          ] as const).map(([key, label]) => (
            <label key={key} className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">{label}</span>
              <input value={legal[key]} onChange={(e) => setLegal({ ...legal, [key]: e.target.value })} className={inp} />
            </label>
          ))}
        </div>
        <div className="mt-4 border border-line bg-paper2/50 px-4 py-3 flex flex-wrap items-center gap-x-5 gap-y-1 text-[12px] text-ink2">
          <span className="text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone">Vista previa de emisor</span>
          <span><strong>{legal.razonSocial}</strong> · RUC {legal.ruc}</span>
          <span>{legal.direccion}</span>
          <span className="text-stone">{legal.moneda}</span>
        </div>
      </Card>

      {/* Flujo de caja */}
      <Card className="p-5 sm:p-6">
        <div className="flex flex-wrap items-center justify-between gap-3 mb-6">
          <div>
            <h3 className="font-bold text-[15px] tracking-tight">Flujo de caja · 6 meses</h3>
            <p className="text-[12px] text-stone mt-0.5">Valores en USD · Ecuador no tiene moneda propia que mover, solo disciplina.</p>
          </div>
          <div className="flex items-center gap-4 text-[11.5px] font-semibold">
            <span className="flex items-center gap-2"><span className="w-3 h-3 bg-ink" /> Ingresos</span>
            <span className="flex items-center gap-2"><span className="w-3 h-3 bg-linedark" /> Egresos</span>
          </div>
        </div>
        <div className="grid grid-cols-6 gap-3 sm:gap-5 items-end h-44">
          {CASHFLOW.map((c) => (
            <div key={c.m} className="flex flex-col items-center gap-2 h-full justify-end group">
              <div className="w-full flex items-end justify-center gap-1.5 flex-1">
                <div className="w-1/3 max-w-[26px] bg-ink group-hover:bg-maroon transition-colors duration-300 relative" style={{ height: `${(c.in / max) * 100}%` }}>
                  <span className="absolute -top-6 left-1/2 -translate-x-1/2 text-[10px] font-bold tnum opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">{Math.round(c.in / 1000)}k</span>
                </div>
                <div className="w-1/3 max-w-[26px] bg-linedark" style={{ height: `${(c.out / max) * 100}%` }} />
              </div>
              <span className="text-[11px] font-bold text-stone uppercase tracking-wider">{c.m}</span>
            </div>
          ))}
        </div>
      </Card>

      {/* Pestañas: facturas / partida doble / formulario 104 */}
      <div className="flex flex-wrap gap-1.5">
        {([["facturas", "Facturas SRI"], ["partida", "Partida doble"], ["f104", "Formulario 104"]] as const).map(([id, label]) => (
          <button key={id} onClick={() => setTab(id)}
            className={`px-4 py-2 text-[12.5px] font-semibold border transition-colors ${tab === id ? "bg-ink text-paper border-ink" : "border-linedark text-ink2 hover:border-ink"}`}>
            {label}
          </button>
        ))}
      </div>

      {/* Facturas SRI */}
      {tab === "facturas" && (
      <Card className="overflow-hidden">
        <div className="px-5 py-4 border-b border-line flex items-center justify-between">
          <h3 className="font-bold text-[15px] tracking-tight">Facturación electrónica · SRI</h3>
          <Chip tone="ok" dot>Ambiente: producción</Chip>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full min-w-[900px]">
            <thead><tr><Th>N° documento</Th><Th>Fecha</Th><Th>Cliente · RUC/Cédula</Th><Th>Base</Th><Th>IVA 15%</Th><Th>Total</Th><Th>Autorización</Th><Th>Estado</Th></tr></thead>
            <tbody>
              {INVOICES.map((f) => (
                <tr key={f.id} className="hover:bg-paper2/50 transition-colors">
                  <Td className="font-mono text-[12px] font-semibold whitespace-nowrap">{f.number}</Td>
                  <Td className="whitespace-nowrap">{f.date}</Td>
                  <Td>
                    <span className="font-medium block">{f.customer}</span>
                    <span className="text-[11px] text-stone font-mono">{f.ruc}</span>
                  </Td>
                  <Td className="tnum">{fmt2(f.base)}</Td>
                  <Td className="tnum">{fmt2(f.iva)}</Td>
                  <Td className="tnum font-semibold">{fmt2(f.total)}</Td>
                  <Td>
                    <span className="flex items-center gap-2">
                      <code className="text-[11px] font-mono text-ink2">{f.auth.slice(0, 10)}…</code>
                      <CopyBtn text={f.auth} label="" />
                    </span>
                  </Td>
                  <Td><StatusChip s={f.status} /></Td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="px-4 py-3 border-t border-line text-[11.5px] text-stone flex items-center gap-2">
          <I n="shield" s={13} />
          Clave de acceso de 49 dígitos validada contra el SRI. Las facturas en contingencia se re-autorizan solas cuando vuelve el servicio.
        </div>
      </Card>
      )}

      {/* Partida doble */}
      {tab === "partida" && (
      <Card className="overflow-hidden fade-in">
        <div className="px-5 py-4 border-b border-line flex flex-wrap items-center justify-between gap-3">
          <div>
            <h3 className="font-bold text-[15px] tracking-tight">Libro diario · partida doble</h3>
            <p className="text-[12px] text-stone mt-0.5">Cada factura genera tres líneas: Bancos (debe), Ventas e IVA (haber). Debe = Haber siempre.</p>
          </div>
          <Chip tone={Math.abs(totDebe - totHaber) < 0.01 ? "ok" : "bad"} dot>
            {Math.abs(totDebe - totHaber) < 0.01 ? "Cuadrado" : "Descuadrado"}
          </Chip>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full min-w-[760px]">
            <thead><tr><Th>Fecha</Th><Th>Cuenta</Th><Th>Concepto</Th><Th>Debe</Th><Th>Haber</Th></tr></thead>
            <tbody>
              {asientos.map((a, i) => (
                <tr key={i} className="hover:bg-paper2/50 transition-colors">
                  <Td className="whitespace-nowrap text-[12px]">{a.fecha}</Td>
                  <Td className="font-mono text-[12px] font-semibold">{a.cuenta}</Td>
                  <Td className="text-[12.5px]">{a.concepto}</Td>
                  <Td className="tnum">{a.debe ? fmt2(a.debe) : ""}</Td>
                  <Td className="tnum">{a.haber ? fmt2(a.haber) : ""}</Td>
                </tr>
              ))}
            </tbody>
            <tfoot>
              <tr className="border-t-2 border-ink bg-paper2/60 font-bold">
                <td className="px-4 py-3 text-[12px]" colSpan={3}>Totales del período</td>
                <Td className="tnum">{fmt2(totDebe)}</Td>
                <Td className="tnum">{fmt2(totHaber)}</Td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div className="px-4 py-3 border-t border-line text-[11.5px] text-stone flex items-center gap-2">
          <I n="calc" s={13} />
          El asiento se genera automáticamente al autorizar la factura en el SRI; no hay registro manual que descuadre.
        </div>
      </Card>
      )}

      {/* Formulario 104 (borrador) */}
      {tab === "f104" && (
      <Card className="overflow-hidden fade-in">
        <div className="px-5 py-4 border-b border-line flex flex-wrap items-center justify-between gap-3">
          <div>
            <h3 className="font-bold text-[15px] tracking-tight">Formulario 104 · Declaración de IVA</h3>
            <p className="text-[12px] text-stone mt-0.5">Borrador calculado del período · RUC 1793442001001 · BLETIA S.A.S.</p>
          </div>
          <Chip tone="warn" dot>Borrador — pendiente de envío</Chip>
        </div>
        <div className="p-5 grid sm:grid-cols-2 gap-x-10 gap-y-3">
          {[
            ["411", "Ventas locales gravadas tarifa 15%", f104.ventas15],
            ["415", "IVA transferido en ventas (15%)", f104.ivaVentas],
            ["441", "Retenciones en ventas", f104.retenciones],
            ["499", "Impuesto a pagar", f104.impuesto],
          ].map(([campo, label, val]) => (
            <div key={campo as string} className="flex items-baseline justify-between gap-4 border-b border-line pb-2.5">
              <span className="text-[12.5px] text-ink2">
                <code className="font-mono font-bold text-maroon mr-2">{campo}</code>{label}
              </span>
              <span className="tnum font-semibold text-[14px]">{fmt2(val as number)}</span>
            </div>
          ))}
        </div>
        <div className="px-5 pb-5">
          <div className="bg-paper2/60 border border-line p-4 flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
            <p className="text-[12px] text-ink2">
              <strong className="text-ink">{f104.n} facturas autorizadas</strong> consideradas · el borrador se arma solo;
              tu contador revisa y envía al SRI antes del día 10 del mes siguiente.
            </p>
            <button className={`${btnDark} !py-2.5 whitespace-nowrap`}>
              <I n="doc" s={14} /> Descargar borrador XML
            </button>
          </div>
        </div>
      </Card>
      )}
    </div>
  );
}

/* ================= Enlaces de un solo uso ================= */
const LINK_TYPES: PayLink["type"][] = ["Pago PayPhone", "Acceso al panel", "Catálogo mayorista", "Seguimiento de pedido"];

export function Enlaces() {
  const [links, setLinks] = useState<PayLink[]>(LINKS_SEED);
  const [form, setForm] = useState({ type: "Pago PayPhone" as PayLink["type"], who: "", amount: "", expires: "24 h" });
  const [lastNew, setLastNew] = useState<string | null>(null);

  const create = () => {
    if (form.who.trim().length < 2) return;
    const code = randomCode();
    const nl: PayLink = {
      id: `l${Date.now()}`, code, type: form.type, who: form.who.trim(),
      amount: form.type === "Pago PayPhone" ? parseFloat(form.amount) || 0 : null,
      expires: form.expires, status: "Activo",
    };
    setLinks((l) => [nl, ...l]);
    setLastNew(code);
    toast(`Enlace creado para ${nl.who}`, "ok");
    setForm({ ...form, who: "", amount: "" });
    setTimeout(() => setLastNew(null), 2500);
  };

  const revoke = (id: string) => {
    setLinks((l) => l.map((x) => (x.id === id ? { ...x, status: "Revocado" } : x)));
    toast("Enlace revocado: ya no se puede usar", "warn");
  };

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Enlaces de un solo uso"
        sub="Pagos, accesos al panel, catálogos y seguimientos. Se usan una vez — o caducan — y nadie más puede abrirlos."
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Activos" value={links.filter((l) => l.status === "Activo").length} sub="Monitoreados en tiempo real" />
        <Stat label="Usados" value={links.filter((l) => l.status === "Usado").length} sub="Consumo único verificado" />
        <Stat label="Revocados" value={links.filter((l) => l.status === "Revocado").length} sub="Muerte instantánea del token" />
        <Stat label="Monto en links de pago" value={fmt2(links.filter((l) => l.status === "Activo" && l.amount).reduce((a, l) => a + (l.amount || 0), 0))} sub="Por cobrar vía PayPhone" />
      </div>

      {/* Generador */}
      <Card className="p-5 sm:p-6">
        <h3 className="font-bold text-[15px] tracking-tight mb-4">Generar enlace</h3>
        <div className="grid sm:grid-cols-2 xl:grid-cols-4 gap-3">
          <label className="block">
            <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Tipo</span>
            <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value as PayLink["type"] })} className={inp}>
              {LINK_TYPES.map((t) => <option key={t}>{t}</option>)}
            </select>
          </label>
          <label className="block">
            <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Destinatario</span>
            <input value={form.who} onChange={(e) => setForm({ ...form, who: e.target.value })} className={inp} placeholder="Cliente o colaborador" />
          </label>
          {form.type === "Pago PayPhone" && (
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Monto USD</span>
              <input value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value })} className={inp} placeholder="0.00" inputMode="decimal" />
            </label>
          )}
          <label className="block">
            <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Expiración</span>
            <select value={form.expires} onChange={(e) => setForm({ ...form, expires: e.target.value })} className={inp}>
              {["24 h", "7 días", "1 uso"].map((t) => <option key={t}>{t}</option>)}
            </select>
          </label>
        </div>
        <div className="flex flex-wrap items-center gap-4 mt-4">
          <button onClick={create} className={btnDark}><I n="link" s={14} /> Crear enlace</button>
          {lastNew && (
            <span className="flex items-center gap-2.5 text-[12.5px] font-semibold text-ok bg-okbg px-3 py-2 fade-in">
              <I n="check" s={13} /> Creado: <code className="font-mono">bletia.ec/l/{lastNew}</code>
              <CopyBtn text={`https://bletia.ec/l/${lastNew}`} label="Copiar" />
            </span>
          )}
        </div>
      </Card>

      {/* Lista */}
      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[820px]">
            <thead><tr><Th>Enlace</Th><Th>Tipo</Th><Th>Destinatario</Th><Th>Monto</Th><Th>Expira</Th><Th>Estado</Th><Th>Acciones</Th></tr></thead>
            <tbody>
              {links.map((l) => (
                <tr key={l.id} className="hover:bg-paper2/50 transition-colors fade-in">
                  <Td>
                    <span className="flex items-center gap-2.5">
                      <code className="font-mono text-[12px] font-semibold">bletia.ec/l/{l.code}</code>
                      <CopyBtn text={`https://bletia.ec/l/${l.code}`} label="" />
                    </span>
                  </Td>
                  <Td>
                    <Chip tone={l.type === "Pago PayPhone" ? "maroon" : "neutral"}>
                      {l.type === "Pago PayPhone" ? "Pago" : l.type === "Acceso al panel" ? "Acceso" : l.type === "Catálogo mayorista" ? "Catálogo" : "Seguimiento"}
                    </Chip>
                  </Td>
                  <Td className="text-[12.5px]">{l.who}</Td>
                  <Td className="tnum font-semibold">{l.amount ? fmt2(l.amount) : "—"}</Td>
                  <Td className="text-[12px] text-stone">{l.expires}</Td>
                  <Td><StatusChip s={l.status} /></Td>
                  <Td>
                    {l.status === "Activo" ? (
                      <button onClick={() => revoke(l.id)}
                        className="text-[11.5px] font-semibold px-3 py-1.5 border border-linedark text-bad hover:bg-bad hover:text-paper hover:border-bad transition-colors whitespace-nowrap">
                        Revocar
                      </button>
                    ) : (
                      <span className="text-[11px] text-stone">—</span>
                    )}
                  </Td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="px-4 py-3 border-t border-line text-[11.5px] text-stone flex items-center gap-2">
          <I n="link" s={13} />
          El primer clic consume el token (transacción atómica en PostgreSQL). Los links de pago rebotan a PayPhone con el monto bloqueado.
        </div>
      </Card>
    </div>
  );
}

/* ================= Infraestructura ================= */
const STACK = [
  { n: "React + Vite", r: "Tienda & panel", d: "SPA compilada a estáticos: navegación instantánea, sin recargas." },
  { n: "Node.js + Fastify", r: "API", d: "Responde en ~8 ms y delega todo trabajo pesado a la cola." },
  { n: "PostgreSQL 16", r: "Base de datos", d: "Pedidos, clientes, fichas, facturas. Respaldos diarios automáticos." },
  { n: "Redis + BullMQ", r: "Motor de eventos", d: "Absorbe +2.000 eventos simultáneos sin que la web se entere." },
  { n: "MinIO", r: "DAM / objetos", d: "Almacenamiento S3 open source para fotos y documentos." },
  { n: "Nginx + Let's Encrypt", r: "Entrada segura", d: "HTTPS automático y cambio de versión atómico (cero caída)." },
  { n: "PM2 cluster", r: "Procesos", d: "Un worker por núcleo del VPS; si uno cae, renace en segundos." },
  { n: "Docker Compose", r: "Entorno", d: "Staging y producción idénticos: si funciona ahí, funciona acá." },
];

/* ---------- Respaldo & restauración (los datos del panel, como un banco) ----------
   Exporta TODO lo guardado (clientes, pedidos, CMS, RRHH, compras, secciones…)
   en un solo JSON descargable, y lo restaura completo cuando quieras. */
const BACKUP_KEYS = [
  ["bletia-cart", "Carritos"],
  ["bletia-wish", "Listas de deseos"],
  ["bletia-cuenta", "Cuentas de visitante"],
  ["bletia-cms", "Blog / diario"],
  ["bletia-paginas", "Páginas (Políticas, Contacto…)"],
  ["bletia-sitio", "Configuración del sitio"],
  ["bletia-suscriptores-web", "Suscriptores newsletter"],
  ["bletia-rrhh", "RRHH / nómina"],
  ["bletia-compras", "Órdenes de compra"],
  ["bletia-home", "Secciones de la portada"],
  ["bletia-blog-cats", "Categorías del blog"],
  ["bletia-prod-cats", "Categorías de producto"],
  ["bletia-login", "Pantalla de acceso"],
  ["bletia-pim-custom", "Productos creados en el PIM"],
  ["bletia-pim-overrides", "Publicar / ocultar productos"],
  ["bletia-session-v2", "Sesión del panel"],
] as const;

function Respaldo() {
  const existentes = BACKUP_KEYS.filter(([k]) => localStorage.getItem(k) !== null);

  const exportar = () => {
    const datos: Record<string, string> = {};
    BACKUP_KEYS.forEach(([k]) => { const v = localStorage.getItem(k); if (v !== null) datos[k] = v; });
    const blob = new Blob([JSON.stringify({ app: "BLETIA", version: "1.0.0", fecha: new Date().toISOString(), datos }, null, 2)], { type: "application/json" });
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = `bletia-respaldo-${new Date().toISOString().slice(0, 10)}.json`;
    a.click();
    URL.revokeObjectURL(a.href);
    toast(`Respaldo descargado (${Object.keys(datos).length} áreas)`, "ok");
  };

  const importar = (file: File) => {
    const reader = new FileReader();
    reader.onload = () => {
      try {
        const json = JSON.parse(String(reader.result));
        if (json.app !== "BLETIA" || !json.datos) throw new Error("formato");
        let n = 0;
        Object.entries(json.datos).forEach(([k, v]) => { localStorage.setItem(k, String(v)); n++; });
        toast(`Respaldo restaurado (${n} áreas). Recargando…`, "ok");
        setTimeout(() => window.location.reload(), 900);
      } catch {
        toast("El archivo no es un respaldo BLETIA válido", "bad");
      }
    };
    reader.readAsText(file);
  };

  return (
    <Card className="p-5 sm:p-6">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h3 className="font-bold text-[15px] tracking-tight flex items-center gap-2">
            <I n="shield" s={16} className="text-maroon" /> Respaldo & restauración
          </h3>
          <p className="text-[12px] text-stone mt-0.5 max-w-[56ch]">
            Descarga todo lo que has cargado (clientes, pedidos, blog, RRHH, compras, secciones) en un solo archivo.
            Si cambias de navegador o de equipo, lo restauras y no pierdes nada. Como un banco: tu información, siempre recuperable.
          </p>
        </div>
        <div className="flex gap-2">
          <label className={`${btnGhost} cursor-pointer`}>
            <I n="doc" s={14} /> Restaurar respaldo
            <input type="file" accept="application/json,.json" className="hidden"
              onChange={(e) => { const f = e.target.files?.[0]; if (f) importar(f); e.target.value = ""; }} />
          </label>
          <button onClick={exportar} className={btnDark}>
            <I n="spark" s={14} /> Descargar respaldo
          </button>
        </div>
      </div>
      <div className="flex flex-wrap gap-1.5 mt-4">
        {existentes.map(([k, label]) => (
          <span key={k} className="text-[10.5px] font-semibold bg-paper2 text-ink2 px-2 py-1 flex items-center gap-1.5">
            <span className="w-1.5 h-1.5 rounded-full bg-ok" /> {label}
          </span>
        ))}
        {existentes.length === 0 && <span className="text-[11.5px] text-stone">Aún no hay datos guardados en este navegador.</span>}
      </div>
    </Card>
  );
}

export function Infra() {
  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Infraestructura & despliegue"
        sub="Stack 100% open source sobre tu VPS de OVH Cloud. Staging primero, producción después, datos siempre intactos."
        right={<Chip tone="ok" dot>VPS OVH · CloudPanel · v1.0.0</Chip>}
      />

      {/* Lanzamiento a producción con CloudPanel */}
      <Card className="p-5 sm:p-6 border-maroon/40">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h3 className="font-bold text-[15px] tracking-tight flex items-center gap-2">
              <span className="w-2 h-2 bg-maroon pulse-maroon" /> Lanzamiento oficial · v1.0.0 en bletia.ec
            </h3>
            <p className="text-[12px] text-stone mt-0.5">Guía exacta para publicar en tu VPS OVH con CloudPanel y dominio real.</p>
          </div>
          <Chip tone="maroon" dot>Listo para producción</Chip>
        </div>

        {/* ficha real del servidor */}
        <div className="grid grid-cols-2 md:grid-cols-5 gap-px bg-line border border-line mt-5">
          {[
            ["Proveedor", "OVH Cloud"],
            ["Hostname", "vps-9cb251e8"],
            ["IPv4 pública", "54.39.21.79"],
            ["Sistema", "Ubuntu 26.04"],
            ["Recursos", "2 vCPU · 4 GB RAM"],
          ].map(([k, v]) => (
            <div key={k} className="bg-card p-3">
              <p className="text-[9.5px] font-bold tracking-[0.14em] uppercase text-stone">{k}</p>
              <p className="text-[12.5px] font-semibold font-mono mt-1">{v}</p>
            </div>
          ))}
        </div>

        <div className="grid md:grid-cols-2 gap-5 mt-5">
          <div className="space-y-3">
            {[
              ["0", "Tus fuentes Geomanist", "Copia tus 3 .woff2 licenciados a public/fonts/ (Geomanist-Regular/Medium/Bold.woff2). Vite los mete solos en dist/ al compilar."],
              ["1", "Compila el proyecto", "En tu equipo: npm run build. Se genera la carpeta dist/ con la tienda y el panel listos para internet."],
              ["2", "Comprime dist/", "Haz un .zip con el CONTENIDO de dist/ (no la carpeta en sí, sino lo que hay dentro)."],
              ["3", "Crea el sitio: STATIC SITE", "CloudPanel → Sites → Add Site → tarjeta «Static Site» (NUNCA «PHP Site»: esa es para Laravel/WordPress y mostrará errores PHP; NO Reverse Proxy). Vhost: Default · Dominio: bletia.ec. CloudPanel crea htdocs."],
              ["4", "Apunta el dominio", "En tu registrador: registro A · bletia.ec → 54.39.21.79 (y www → igual). Espera propagación (minutos a horas)."],
              ["5", "Sube los archivos", "CloudPanel → File Manager → htdocs del sitio → sube el .zip y extráelo ahí. O por SFTP/SCP a esa carpeta."],
              ["6", "Activa el SSL gratis", "CloudPanel → SSL → Let's Encrypt → Issue para bletia.ec. En segundos responde con https://."],
              ["7", "Verifica", "Abre https://bletia.ec (tienda) y https://bletia.ec/#/dash (panel). Si se ven, estás en producción."],
            ].map(([n, t, d]) => (
              <div key={n} className="flex gap-3.5 border border-line bg-card p-3.5 hover:border-maroon/40 transition-colors">
                <span className="w-6 h-6 shrink-0 bg-ink text-paper text-[11px] font-bold flex items-center justify-center">{n}</span>
                <div>
                  <p className="text-[13px] font-bold leading-tight">{t}</p>
                  <p className="text-[11.5px] text-stone leading-snug mt-1">{d}</p>
                </div>
              </div>
            ))}
          </div>

          <div className="space-y-4">
            <CodeBlock title="Comandos en tu equipo (opcional, vía SSH/SFTP)" code={`# Compilar la versión de producción
npm run build

# La carpeta dist/ es lo que subes a CloudPanel (solo su CONTENIDO).
# Si prefieres terminal sobre SSH (tu VPS: 54.39.21.79):
# (la ruta exacta de htdocs la ves en CloudPanel → tu sitio → Settings → Document Root)
scp -r dist/* cloudpanel@54.39.21.79:/home/cloudpanel/htdocs/`} />
            <CodeBlock title="Despliegue directo desde GitHub (cadaidea/blthm-2 · PR #1)" code={`# El VPS jala el código directo de GitHub. Publicas en la rama 'web'
# (tu PR #1). deploy/ tiene: provision.sh · deploy.sh · rollback.sh

# UNA SOLA VEZ (prepara el VPS: Node 22 + clona blthm-2 + estructura):
ssh ubuntu@54.39.21.79
cd /home/ubuntu/bletia && bash provision.sh
#   → luego edita deploy.conf (DOC_ROOT) y deja tus .woff2 en shared/fonts/

# PRIMER DESPLIEGUE (jala la rama web, compila, publica):
bash deploy.sh

# CADA MEJORA: mergea tu PR a 'web' en GitHub, y en el VPS:
bash deploy.sh             # sin caída + respaldo automático previo

# PROBAR EL PR SIN MERGE (revisarlo en el servidor):
bash deploy.sh origin/pr/1/head

# SI ALGO SALE MAL (vuelve al release anterior en 10 s):
bash rollback.sh`} />
            <div className="bg-okbg/60 border border-ok/30 p-4">
              <p className="text-[12px] font-bold text-ok flex items-center gap-1.5"><I n="check" s={14} /> Ventaja de este build</p>
              <p className="text-[11.5px] text-ink2 leading-snug mt-1.5">
                Usamos rutas con <code className="font-mono">#/</code> (hash), así que <strong>no necesitas configurar nginx</strong>:
                tienda, blog, productos y panel funcionan tal cual los subas. Cero configuración extra en CloudPanel.
              </p>
            </div>
          </div>
        </div>
      </Card>

      {/* Troubleshooting de despliegue */}
      <Card className="p-5 sm:p-6 border-warn/40">
        <h3 className="font-bold text-[15px] tracking-tight flex items-center gap-2">
          <I n="alert" s={16} className="text-warn" /> Si CloudPanel muestra «Laravel» o errores PHP
        </h3>
        <p className="text-[12px] text-stone mt-0.5 mb-4">Este proyecto NO usa Laravel ni PHP: es 100% estático. Si aparece ese error, el sitio se creó con el tipo equivocado.</p>
        <div className="grid md:grid-cols-2 gap-4">
          <div className="border border-line bg-card p-4">
            <p className="text-[12.5px] font-bold text-warn">Causa</p>
            <p className="text-[12px] text-ink2 leading-relaxed mt-1.5">
              El sitio se creó como <strong>PHP Site</strong> (la tarjeta de CloudPanel para Laravel/WordPress).
              Esa plantilla manda todas las rutas a <code className="font-mono">index.php</code>, que aquí no existe — por eso el mensaje.
            </p>
          </div>
          <div className="border border-ok/40 bg-okbg/40 p-4">
            <p className="text-[12.5px] font-bold text-ok">Solución (2 minutos)</p>
            <p className="text-[12px] text-ink2 leading-relaxed mt-1.5">
              <strong>1.</strong> Sites → tu sitio → Settings → <strong>Delete Site</strong> (no toca tu dominio ni tus DNS).
              <strong> 2.</strong> Add Site → tarjeta <strong>Static Site</strong> → mismo dominio.
              <strong> 3.</strong> Vuelve a subir el zip a <code className="font-mono">htdocs/</code> y extrae.
              <strong> 4.</strong> Verifica que quede <code className="font-mono">htdocs/index.html</code> (no dentro de una subcarpeta <code className="font-mono">dist/</code>).
            </p>
          </div>
        </div>
      </Card>

      {/* Fases de persistencia: la verdad sobre los datos */}
      <Card className="p-5 sm:p-6">
        <h3 className="font-bold text-[15px] tracking-tight">Cómo se protegen tus datos (como un banco)</h3>
        <p className="text-[12px] text-stone mt-0.5 mb-5">Dos fases. La v1.0.0 lanza la Fase 1; la Fase 2 conecta el servidor para que todo sea compartido y automático.</p>
        <div className="grid md:grid-cols-2 gap-4">
          <div className="border border-warn/40 bg-warnbg/40 p-4">
            <div className="flex items-center justify-between">
              <p className="text-[12.5px] font-bold text-warn">Fase 1 · v1.0.0 (hoy)</p>
              <Chip tone="warn" dot>Activa</Chip>
            </div>
            <ul className="text-[11.5px] text-ink2 leading-relaxed mt-2.5 space-y-1.5">
              <li className="flex gap-2"><I n="check" s={13} className="text-warn shrink-0 mt-0.5" /> La tienda y el panel funcionan completos en tu VPS.</li>
              <li className="flex gap-2"><I n="check" s={13} className="text-warn shrink-0 mt-0.5" /> Lo que cargas en el panel se guarda en <strong>tu navegador</strong> (localStorage).</li>
              <li className="flex gap-2"><I n="check" s={13} className="text-warn shrink-0 mt-0.5" /> Puedes descargar/restaurar TODO con «Respaldo» (arriba).</li>
              <li className="flex gap-2"><I n="alert" s={13} className="text-warn shrink-0 mt-0.5" /> Los cambios del panel se ven en tu navegador; los visitantes ven lo compilado en el build.</li>
            </ul>
          </div>
          <div className="border border-ok/40 bg-okbg/40 p-4">
            <div className="flex items-center justify-between">
              <p className="text-[12.5px] font-bold text-ok">Fase 2 · Servidor (siguiente)</p>
              <Chip tone="ok" dot>Planeada</Chip>
            </div>
            <ul className="text-[11.5px] text-ink2 leading-relaxed mt-2.5 space-y-1.5">
              <li className="flex gap-2"><I n="check" s={13} className="text-ok shrink-0 mt-0.5" /> API + PostgreSQL en el mismo VPS: los datos viven en el servidor, no en el navegador.</li>
              <li className="flex gap-2"><I n="check" s={13} className="text-ok shrink-0 mt-0.5" /> Los pedidos reales de los clientes llegan solos al panel.</li>
              <li className="flex gap-2"><I n="check" s={13} className="text-ok shrink-0 mt-0.5" /> Todos los colaboradores ven la misma información, al instante.</li>
              <li className="flex gap-2"><I n="check" s={13} className="text-ok shrink-0 mt-0.5" /> Respaldos automáticos diarios de la base de datos.</li>
            </ul>
          </div>
        </div>
        <p className="text-[11.5px] text-stone mt-4 flex items-start gap-2">
          <I n="shield" s={13} className="mt-0.5 shrink-0 text-maroon" />
          Ninguna mejora futura borra tus datos: el código se actualiza por separado de la información. En Fase 1 usas «Respaldo»; en Fase 2 la base de datos se respalda sola.
        </p>
      </Card>

      {/* Respaldo funcional */}
      <Respaldo />

      {/* Arquitectura de eventos */}
      <Card className="p-5 sm:p-6">
        <h3 className="font-bold text-[15px] tracking-tight">Cómo soporta +2.000 eventos simultáneos</h3>
        <p className="text-[12.5px] text-stone mt-1 mb-5">La web nunca procesa trabajo pesado: solo recibe, agradece y encola.</p>
        <div className="flex flex-wrap items-stretch gap-2">
          {[
            ["Web / PayPhone / SRI", "eventos entrantes"],
            ["Nginx", "TLS + balanceo"],
            ["Fastify", "valida y responde (ACK ~8 ms)"],
            ["Redis · BullMQ", "cola durable, +2.000 ev/s"],
            ["Workers ×4", "OMS · PIM · CRM · Contabilidad"],
            ["PostgreSQL / MinIO", "verdad única + archivos"],
          ].map(([t, d], i, arr) => (
            <div key={t} className="flex items-center gap-2">
              <div className={`border px-3.5 py-3 min-w-[128px] ${i === 3 ? "border-maroon bg-maroon/5" : "border-linedark bg-card"}`}>
                <p className="text-[12px] font-bold leading-tight">{t}</p>
                <p className="text-[10.5px] text-stone mt-0.5 leading-snug">{d}</p>
              </div>
              {i < arr.length - 1 && <I n="chev-r" s={13} className="text-stone shrink-0" />}
            </div>
          ))}
        </div>
        <p className="text-[11.5px] text-stone mt-4 flex items-start gap-2">
          <I n="pulse" s={13} className="mt-0.5 shrink-0 text-maroon" />
          Si PayPhone manda 300 webhooks de golpe o el SRI autoriza 500 facturas, todo entra a la cola y se procesa en orden.
          El cliente navega la tienda a 60 fps pase lo que pase. Pruébalo en «Visión general → Simular pico».
        </p>
      </Card>

      {/* Stack */}
      <div>
        <h3 className="font-bold text-[15px] tracking-tight mb-4">Stack open source (licencias permisivas)</h3>
        <div className="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
          {STACK.map((s) => (
            <Card key={s.n} className="p-4.5 p-5 hover:border-maroon/40 transition-colors group">
              <p className="text-[10.5px] font-bold tracking-[0.14em] uppercase text-maroon">{s.r}</p>
              <p className="font-bold text-[14.5px] mt-1.5">{s.n}</p>
              <p className="text-[12px] text-stone leading-relaxed mt-1.5">{s.d}</p>
            </Card>
          ))}
        </div>
      </div>

      {/* Flujo Git */}
      <div>
        <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
          <div>
            <h3 className="font-bold text-[15px] tracking-tight">Flujo Git: dónde subes cada cosa</h3>
            <p className="text-[12.5px] text-stone mt-1">
              Nada llega a <code className="font-mono font-semibold text-ink">main</code> sin haber pasado por{" "}
              <code className="font-mono font-semibold text-warn">staging</code>. Producción solo se toca con tags versionados.
            </p>
          </div>
          <button onClick={() => {
            const content = ["node_modules/", "dist/", ".env", ".env.*", "!.env.example", "*.zip", "*.p12", "*.pem", "*.key", "backups/", "*.log", ".DS_Store", ""].join("\n");
            const blob = new Blob([content], { type: "text/plain;charset=utf-8" });
            const a = document.createElement("a");
            a.href = URL.createObjectURL(blob);
            a.download = ".gitignore";
            a.click();
            URL.revokeObjectURL(a.href);
          }} className={btnGhost}>
            <I n="doc" s={14} /> Descargar .gitignore
          </button>
        </div>

        <Card className="p-5 sm:p-6">
          <div className="flex flex-col md:flex-row md:items-stretch gap-2">
            {[
              ["feat/lo-tuyo", "rama de trabajo", "Aquí preparas el cambio. Commits chicos, mensajes claros.", "border-linedark bg-card", "text-ink2"],
              ["staging", "rama de pruebas", "El VPS de staging hace checkout de esta rama. Se prueba con datos reales.", "border-warn/50 bg-warnbg/50", "text-warn"],
              ["main + tag v2.4.x", "producción", "Solo merges aprobados. El VPS de producción hace checkout del tag.", "border-ok/50 bg-okbg/50", "text-ok"],
            ].map(([t, s, d, cls, tc], i, arr) => (
              <div key={t} className="flex items-center gap-2 flex-1">
                <div className={`border px-4 py-3.5 flex-1 ${cls}`}>
                  <p className={`text-[12.5px] font-bold font-mono ${tc}`}>{t}</p>
                  <p className="text-[10.5px] font-bold tracking-[0.12em] uppercase text-stone mt-0.5">{s}</p>
                  <p className="text-[11.5px] text-ink2 leading-snug mt-1.5">{d}</p>
                </div>
                {i < arr.length - 1 && <I n="chev-r" s={14} className="text-stone shrink-0 hidden md:block" />}
                {i < arr.length - 1 && <I n="arrow" s={14} className="text-stone shrink-0 md:hidden rotate-90 mx-auto" />}
              </div>
            ))}
          </div>
        </Card>

        <div className="grid xl:grid-cols-3 gap-5 mt-5">
          <div className="xl:col-span-2">
            <CodeBlock title="Ciclo completo de un cambio (copia y pega)" code={`# 1) Trabaja en tu rama — nunca directo a main
git checkout -b feat/checkout-payphone
git add . && git commit -m "feat: pago PayPhone directo y por link"
git push origin feat/checkout-payphone

# 2) Pásala a staging y pruébala en el VPS
git checkout staging && git merge feat/checkout-payphone
git push origin staging
# En el VPS (staging):
#   cd /home/ubuntu/bletia-app && git checkout staging && git pull
#   npm ci && npm run build
#   pm2 reload ecosystem.staging.js
# Prueba en https://staging.bletia.ec

# 3) Si staging aprobó → main + tag de producción
git checkout main && git merge staging
git tag v2.4.1 && git push origin main --tags
# En el VPS (producción):
#   git fetch --tags && git checkout v2.4.1
#   npm ci && npm run build → symlink atomico → sudo systemctl reload nginx`} />
          </div>
          <div className="space-y-5">
            <Card className="p-4 border-bad/35 bg-badbg/40">
              <p className="text-[10.5px] font-bold tracking-[0.16em] uppercase text-bad mb-2.5 flex items-center gap-1.5">
                <I n="alert" s={13} /> Nunca va al repositorio
              </p>
              <ul className="space-y-2 text-[12px] text-ink2 leading-snug">
                <li className="flex gap-2"><span className="text-bad font-bold">✕</span> <span><code className="font-mono">.env</code> y claves de PayPhone / API keys</span></li>
                <li className="flex gap-2"><span className="text-bad font-bold">✕</span> <span>Firma electrónica SRI (<code className="font-mono">.p12</code>) — vive solo en el VPS</span></li>
                <li className="flex gap-2"><span className="text-bad font-bold">✕</span> <span><code className="font-mono">node_modules/</code>, <code className="font-mono">dist/</code>, <code className="font-mono">*.zip</code> y respaldos del VPS</span></li>
              </ul>
              <p className="text-[11px] text-stone mt-3 pt-3 border-t border-bad/15">
                En su lugar sube un <code className="font-mono">.env.example</code> sin valores reales y un{" "}
                <code className="font-mono">.gitignore</code> que cubra lo anterior.
              </p>
            </Card>
            <Card className="p-4 border-warn/35 bg-warnbg/40">
              <p className="text-[10.5px] font-bold tracking-[0.16em] uppercase text-warn mb-2 flex items-center gap-1.5">
                <I n="doc" s={13} /> Geomanist y el repo público
              </p>
              <p className="text-[12px] text-ink2 leading-relaxed">
                Si el repo será público (para que yo lo lea), deja tus <code className="font-mono">.woff2</code> licenciados{" "}
                <strong className="text-ink">fuera del repo</strong>: en{" "}
                <code className="font-mono text-[11px]">/home/ubuntu/bletia/private/fonts</code> en el VPS, servidos con un alias de nginx hacia{" "}
                <code className="font-mono text-[11px]">/fonts/</code>. La licencia no viaja por internet y la web carga igual.
              </p>
            </Card>
          </div>
        </div>
      </div>

      {/* Guía de despliegue */}
      <div>
        <div className="flex flex-wrap items-end justify-between gap-3 mb-4">
          <div>
            <h3 className="font-bold text-[15px] tracking-tight">Despliegue en tu VPS OVH</h3>
            <p className="text-[12.5px] text-stone mt-1">Con .zip o directo desde GitHub: pegas los comandos en SSH y listo. Cada bloque se copia con un clic.</p>
          </div>
          <div className="flex items-center gap-2 text-[11.5px] font-semibold text-ink2">
            <span className="w-5 h-5 bg-warnbg text-warn flex items-center justify-center text-[10px] font-bold">1</span> staging
            <I n="arrow" s={12} className="text-stone" />
            <span className="w-5 h-5 bg-okbg text-ok flex items-center justify-center text-[10px] font-bold">2</span> producción
          </div>
        </div>

        <div className="grid xl:grid-cols-2 gap-5">
          <div className="space-y-5">
            <CodeBlock title="Opción A · subir el .zip al VPS" code={`# Desde tu computador (o usa el gestor de archivos de OVH)
scp bletia-staging.zip ubuntu@TU_IP_OVH:/home/ubuntu/releases/

# Luego conectas por SSH:
ssh ubuntu@TU_IP_OVH`} />
            <CodeBlock title="Opción B · desde GitHub (recomendado)" code={`# 1) En el VPS: crea una llave de solo lectura para GitHub
ssh-keygen -t ed25519 -C "deploy-bletia" -f ~/.ssh/bletia_deploy -N ""
cat ~/.ssh/bletia_deploy.pub
# Copia la salida a GitHub → Repo → Settings → Deploy keys (sin marcar "Allow write")

# 2) Clona el proyecto
git clone git@github.com:TU_USUARIO/bletia.git /home/ubuntu/bletia-app

# 3) Staging siempre apunta a la rama staging
cd /home/ubuntu/bletia-app && git checkout staging && git pull origin staging

# Ventaja: cada release es un tag (v2.4.1), el rollback es un checkout
# y no hay .zip flotando por ahí. Sigue con el Paso 1 de abajo.`} />
            <Card className="p-4.5 p-5 border-maroon/40 bg-maroon/5 flex items-start gap-3 text-[12px] text-ink2">
              <I n="spark" s={15} className="text-maroon shrink-0 mt-0.5" />
              <span>
                <strong className="text-ink">¿Por qué GitHub le gana al .zip?</strong> Historial auditable de cada cambio,
                versiones con tags (v2.4.0, v2.4.1…), rollback de un comando y staging/producción corriendo el mismo commit.
                El asistente puede leer tu repo público archivo por archivo y ayudarte a desplegar sin tocar los datos.
              </span>
            </Card>
            <CodeBlock title="Paso 1 · desplegar en STAGING (pruebas)" code={`# Opción A: cd /home/ubuntu/releases && unzip -o bletia-staging.zip -d bletia-staging
# Opción B: cd /home/ubuntu/bletia-app && git checkout staging && git pull origin staging

# Apuntar staging a la nueva versión y recargar sin caída
cd /home/ubuntu/bletia
ln -sfn /home/ubuntu/releases/bletia-staging/dist staging-dist
# (Opción B: ln -sfn /home/ubuntu/bletia-app/dist staging-dist)
pm2 reload ecosystem.staging.js --update-env || pm2 start ecosystem.staging.js
sudo nginx -t && sudo systemctl reload nginx

# Probar en https://staging.bletia.ec`} />
            <CodeBlock title="Paso 3 · rollback en 10 segundos (si hiciera falta)" code={`cd /home/ubuntu/bletia
ln -sfn /home/ubuntu/releases/VERSION_ANTERIOR current
# Opción B: cd /home/ubuntu/bletia-app && git checkout v2.4.0
# (el symlink 'current' ya apunta al repo: se refleja solo, sin mover nada)
sudo systemctl reload nginx
# La base de datos NUNCA se toca: todo sigue intacto.`} />
          </div>
          <div className="space-y-5">
            <CodeBlock title="Paso 2 · pasar a PRODUCCIÓN (cero caída)" code={`# 1) Respaldo primero (30 segundos, siempre)
pg_dump -U bletia bletia_prod | gzip > /home/ubuntu/backups/bletia_$(date +%F_%H%M).sql.gz

# 2) Preparar la versión probada en staging
#    Opción A: cd /home/ubuntu/releases && unzip -o bletia-prod.zip -d bletia-prod-$(date +%Y%m%d)
#    Opción B: cd /home/ubuntu/bletia-app && git fetch --tags && git checkout v2.4.1

# 3) Cambio atómico de versión (el truco del symlink)
cd /home/ubuntu/bletia
ln -sfn /home/ubuntu/releases/bletia-prod-$(date +%Y%m%d) current
# (Opción B: ln -sfn /home/ubuntu/bletia-app current)
pm2 reload ecosystem.prod.js --update-env
sudo nginx -t && sudo systemctl reload nginx
pm2 save`} />
            <Card className="p-5 bg-coal border-coal text-cream">
              <p className="text-[10.5px] font-bold tracking-[0.16em] uppercase text-cream/40 mb-3">Garantías del proceso</p>
              <ul className="space-y-2.5 text-[12.5px] text-cream/80">
                {[
                  "Clientes y colaboradores no notan el cambio: la URL no cambia y no hay pantalla de mantenimiento.",
                  "La información cargada queda intacta: el deploy solo reemplaza código, jamás toca PostgreSQL ni MinIO.",
                  "Staging y producción corren el mismo zip: lo que aprobaste es exactamente lo que se publica.",
                  "PM2 recarga en caliente (cluster): siempre hay workers atendiendo mientras otros se actualizan.",
                ].map((t) => (
                  <li key={t} className="flex gap-2.5">
                    <I n="check" s={14} className="text-ok shrink-0 mt-0.5" /> {t}
                  </li>
                ))}
              </ul>
            </Card>
            <Card className="p-4 flex items-start gap-3 text-[12px] text-ink2 border-warn/40 bg-warnbg/40">
              <I n="alert" s={15} className="text-warn shrink-0 mt-0.5" />
              <span>
                <strong className="text-ink">Regla de oro:</strong> ningún .zip pasa a producción sin haber sido probado en
                staging con datos reales replicados. El respaldo del paso 2 se guarda 30 días en /home/ubuntu/backups.
              </span>
            </Card>
          </div>
        </div>
      </div>
    </div>
  );
}
