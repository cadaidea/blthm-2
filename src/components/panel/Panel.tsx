import { useCallback, useEffect, useRef, useState } from "react";
import { ORDERS, EVENT_TYPES, fmt2 } from "../../data";
import { I, type IconName } from "../ui";
import { Card, Chip, SectionTitle, Stat, Td, Th, btnDark, btnGhost } from "./pui";
import { CRM, OMS, PIM } from "./Modules";
import { DAM, Proveedores, Taller } from "./Modules2";
import { Contabilidad, Enlaces, Infra } from "./Modules3";

type Mod = "vision" | "oms" | "crm" | "pim" | "taller" | "prov" | "dam" | "conta" | "links" | "infra";

const NAV: { group: string; items: { id: Mod; label: string; icon: IconName }[] }[] = [
  { group: "Operación", items: [
    { id: "vision", label: "Visión general", icon: "grid" },
    { id: "oms", label: "Pedidos · OMS", icon: "box" },
    { id: "taller", label: "Taller", icon: "hammer" },
    { id: "prov", label: "Proveedores", icon: "truck" },
  ]},
  { group: "Cliente & catálogo", items: [
    { id: "crm", label: "Clientes · CRM", icon: "users" },
    { id: "pim", label: "Productos · PIM", icon: "tag" },
    { id: "dam", label: "Medios · DAM", icon: "image" },
  ]},
  { group: "Finanzas & sistema", items: [
    { id: "conta", label: "Contabilidad", icon: "calc" },
    { id: "links", label: "Enlaces de un uso", icon: "link" },
    { id: "infra", label: "Infraestructura", icon: "server" },
  ]},
];

const TITLES: Record<Mod, string> = {
  vision: "Visión general", oms: "Gestión de pedidos", crm: "Relación con clientes",
  pim: "Información de producto", taller: "Órdenes de fabricación", prov: "Proveedores",
  dam: "Activos digitales", conta: "Contabilidad · SRI", links: "Enlaces de un solo uso",
  infra: "Infraestructura & despliegue",
};

/* ---- motor de eventos simulado (Redis + BullMQ en producción) ---- */
export type LogItem = { t: string; type: string; mod: string };
const MODS: Record<string, string> = {
  "pago.payphone.aprobado": "OMS", "pedido.creado": "OMS", "oms.estado.actualizado": "OMS",
  "pim.precio.sincronizado": "PIM", "crm.cliente.creado": "CRM", "dam.asset.procesado": "DAM",
  "factura.sri.autorizada": "Conta", "taller.fase.avanzada": "Taller", "link.uso_registrado": "Links",
  "transporte.gps.ping": "OMS", "inventario.movimiento": "PIM", "sesion.panel.iniciada": "Auth",
};

function useEventEngine() {
  const [series, setSeries] = useState<number[]>(() => Array.from({ length: 32 }, () => 40 + Math.random() * 90));
  const [log, setLog] = useState<LogItem[]>([]);
  const [stress, setStress] = useState(false);
  const stressRef = useRef(false);
  stressRef.current = stress;

  useEffect(() => {
    const push = () => {
      const type = EVENT_TYPES[Math.floor(Math.random() * EVENT_TYPES.length)];
      setLog((l) => [{ t: new Date().toLocaleTimeString("es-EC"), type, mod: MODS[type] }, ...l].slice(0, 12));
    };
    push(); push(); push();
    const t = setInterval(() => {
      const stressOn = stressRef.current;
      setSeries((s) => [...s.slice(1), stressOn ? 2150 + Math.random() * 500 : 40 + Math.random() * 90]);
      if (Math.random() < (stressOn ? 0.95 : 0.55)) push();
    }, 650);
    return () => clearInterval(t);
  }, []);

  const startStress = useCallback(() => {
    setStress(true);
    setTimeout(() => setStress(false), 7000);
  }, []);

  return { series, log, stress, startStress, eps: Math.round(series[series.length - 1]) };
}

export default function Panel() {
  const [mod, setMod] = useState<Mod>("vision");
  const [env, setEnv] = useState<"staging" | "producción">("staging");
  const [nav, setNav] = useState(false);
  const engine = useEventEngine();

  useEffect(() => { window.scrollTo(0, 0); }, [mod]);

  const go = (m: Mod) => { setMod(m); setNav(false); };

  return (
    <div className="min-h-screen bg-paper text-ink font-dash">
      {/* ---------- Sidebar ---------- */}
      <aside className={`fixed inset-y-0 left-0 z-[70] w-[248px] bg-coal text-cream flex flex-col transition-transform duration-300 lg:translate-x-0 ${nav ? "translate-x-0" : "-translate-x-full"}`}>
        <div className="h-16 px-5 flex items-center justify-between border-b border-cream/10 shrink-0">
          <a href="#top" className="font-display font-semibold tracking-[0.3em] text-[15px]">BLETIA<span className="text-maroon">.</span></a>
          <button onClick={() => setNav(false)} className="lg:hidden p-1.5 text-cream/60 hover:text-cream" aria-label="Cerrar menú"><I n="close" s={16} /></button>
        </div>
        <nav className="flex-1 overflow-y-auto py-5 px-3">
          {NAV.map((g) => (
            <div key={g.group} className="mb-6">
              <p className="px-3 mb-2 text-[9.5px] font-bold tracking-[0.22em] uppercase text-cream/35">{g.group}</p>
              {g.items.map((it) => (
                <button key={it.id} onClick={() => go(it.id)}
                  className={`w-full flex items-center gap-3 px-3 py-2.5 text-[13px] font-medium transition-all duration-200 relative ${mod === it.id ? "text-cream bg-cream/5" : "text-cream/55 hover:text-cream hover:bg-cream/5"}`}>
                  {mod === it.id && <span className="absolute left-0 top-1.5 bottom-1.5 w-[2.5px] bg-maroon" />}
                  <I n={it.icon} s={16} className={mod === it.id ? "text-maroon" : ""} />
                  {it.label}
                  {it.id === "oms" && <span className="ml-auto text-[10px] font-bold bg-maroon text-cream px-1.5 py-0.5 tnum">3</span>}
                </button>
              ))}
            </div>
          ))}
        </nav>
        <div className="p-4 border-t border-cream/10 shrink-0">
          <div className="flex items-center gap-2.5 mb-3">
            <span className={`w-2 h-2 rounded-full ${env === "producción" ? "bg-ok pulse-ok" : "bg-warn"}`} />
            <span className="text-[11px] font-semibold text-cream/70">Entorno: {env}</span>
          </div>
          <p className="text-[10.5px] text-cream/35 leading-relaxed">OVH VPS · nginx 1.24 · PM2 cluster<br />v2.4.1 · datos intactos ✓</p>
        </div>
      </aside>
      {nav && <div className="fixed inset-0 z-[65] bg-ink/50 lg:hidden fade-in" onClick={() => setNav(false)} />}

      {/* ---------- Main ---------- */}
      <div className="lg:pl-[248px]">
        <header className="sticky top-0 z-[60] h-16 bg-paper/90 backdrop-blur-md border-b border-line flex items-center justify-between px-5 sm:px-8">
          <div className="flex items-center gap-3 min-w-0">
            <button onClick={() => setNav(true)} className="lg:hidden p-2 -ml-2" aria-label="Menú"><I n="menu" s={19} /></button>
            <div className="min-w-0">
              <h1 className="font-bold text-[15px] tracking-tight truncate">{TITLES[mod]}</h1>
              <p className="text-[11px] text-stone hidden sm:block">BLETIA OS · panel interno</p>
            </div>
          </div>
          <div className="flex items-center gap-3 sm:gap-4">
            <span className="hidden md:inline-flex items-center gap-2 text-[11.5px] font-semibold text-ok bg-okbg px-2.5 py-1.5">
              <span className="w-1.5 h-1.5 rounded-full bg-ok pulse-ok" />
              <span className="tnum">{engine.eps.toLocaleString("es-EC")}</span> ev/s
            </span>
            <button onClick={() => setEnv(env === "staging" ? "producción" : "staging")}
              className={`text-[11px] font-bold tracking-wide uppercase px-3 py-1.5 border transition-colors ${env === "staging" ? "border-warn/40 text-warn bg-warnbg hover:border-warn" : "border-ok/40 text-ok bg-okbg hover:border-ok"}`}>
              {env}
            </button>
            <span className="w-8 h-8 bg-ink text-paper text-[11px] font-bold flex items-center justify-center">RB</span>
          </div>
        </header>

        <main className="p-5 sm:p-8 max-w-[1240px]">
          {mod === "vision" && <Vision engine={engine} go={go} />}
          {mod === "oms" && <OMS />}
          {mod === "crm" && <CRM />}
          {mod === "pim" && <PIM />}
          {mod === "taller" && <Taller />}
          {mod === "prov" && <Proveedores />}
          {mod === "dam" && <DAM />}
          {mod === "conta" && <Contabilidad />}
          {mod === "links" && <Enlaces />}
          {mod === "infra" && <Infra />}
        </main>
      </div>
    </div>
  );
}

/* ================= Visión general ================= */
function Vision({ engine, go }: { engine: ReturnType<typeof useEventEngine>; go: (m: Mod) => void }) {
  const { series, log, stress, startStress, eps } = engine;
  const max = Math.max(...series);
  const latency = stress ? 19 + Math.round(Math.random() * 9) : 7 + Math.round(Math.random() * 4);
  const queue = stress ? 210 + Math.round(Math.random() * 90) : 3 + Math.round(Math.random() * 9);

  return (
    <div className="fade-in space-y-6">
      {/* KPIs */}
      <div className="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Ventas de hoy" value={fmt2(3840)} sub={<span className="text-ok font-semibold">▲ 12,4% vs ayer</span>} />
        <Stat label="Pedidos activos" value={14} sub="3 en taller · 2 en transporte" />
        <Stat label="Eventos por segundo" value={eps.toLocaleString("es-EC")} live sub={stress ? <span className="text-warn font-semibold">Prueba de carga en curso</span> : "Redis + BullMQ · sin caídas"} />
        <Stat label="Facturación febrero" value="$48.600" sub={`IVA por declarar: $6.348`} />
      </div>

      <div className="grid xl:grid-cols-3 gap-6">
        {/* Monitor de eventos */}
        <Card className="xl:col-span-2 p-5 sm:p-6">
          <div className="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div>
              <h3 className="font-bold text-[15px] tracking-tight">Monitor de eventos</h3>
              <p className="text-[12px] text-stone mt-0.5">Ingesta desacoplada: la web jamás se bloquea, aunque entren +2.000 eventos simultáneos.</p>
            </div>
            <button onClick={startStress} disabled={stress}
              className={`${stress ? btnGhost + " opacity-60 cursor-wait" : btnDark}`}>
              <I n="pulse" s={14} /> {stress ? "Sometiendo al sistema…" : "Simular pico · 2.400 ev/s"}
            </button>
          </div>

          <div className="flex items-end gap-[3px] h-32 bg-paper2/60 px-3 pt-3 pb-0 overflow-hidden">
            {series.map((v, i) => (
              <div key={i} className="flex-1 flex flex-col justify-end h-full">
                <div className={`bar-in w-full ${v > 500 ? "bg-maroon" : "bg-ink/70"} hover:bg-maroon transition-colors`}
                  style={{ height: `${Math.max((v / max) * 100, 3)}%`, animationDelay: `${i * 8}ms` }} />
              </div>
            ))}
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-4 gap-px bg-line mt-5 border border-line">
            {[
              ["EPS actual", `${eps.toLocaleString("es-EC")}`, stress ? "text-warn" : "text-ok"],
              ["Latencia p95", `${latency} ms`, latency > 15 ? "text-warn" : "text-ok"],
              ["Cola Redis", `${queue} jobs`, queue > 50 ? "text-warn" : "text-ok"],
              ["Uptime 30 días", "99,98%", "text-ok"],
            ].map(([k, v, c]) => (
              <div key={k} className="bg-card p-3.5">
                <p className="text-[10px] font-bold tracking-[0.14em] uppercase text-stone">{k}</p>
                <p className={`font-display font-medium text-[19px] mt-1 tnum ${c}`}>{v}</p>
              </div>
            ))}
          </div>
        </Card>

        {/* Log en vivo */}
        <Card className="p-5 sm:p-6 flex flex-col">
          <h3 className="font-bold text-[15px] tracking-tight">Cola de procesamiento</h3>
          <p className="text-[12px] text-stone mt-0.5 mb-4">Workers por módulo consumiendo en paralelo.</p>
          <div className="flex-1 space-y-1 overflow-hidden">
            {log.map((l, i) => (
              <div key={l.t + i + l.type} className={`flex items-center gap-2.5 py-1.5 border-b border-line/60 ${i === 0 ? "fade-in" : ""}`}>
                <span className={`w-1.5 h-1.5 rounded-full shrink-0 ${i === 0 ? "bg-maroon pulse-maroon" : "bg-linedark"}`} />
                <code className="text-[11px] font-mono text-ink2 truncate flex-1">{l.type}</code>
                <span className="text-[9.5px] font-bold uppercase tracking-wider text-stone bg-paper2 px-1.5 py-0.5">{l.mod}</span>
                <span className="text-[10px] text-stone tnum shrink-0">{l.t}</span>
              </div>
            ))}
          </div>
        </Card>
      </div>

      {/* fila inferior */}
      <div className="grid xl:grid-cols-3 gap-6">
        <Card className="xl:col-span-2 overflow-hidden">
          <div className="flex items-center justify-between px-5 py-4 border-b border-line">
            <h3 className="font-bold text-[15px] tracking-tight">Últimos pedidos</h3>
            <button onClick={() => go("oms")} className="text-[12px] font-semibold text-maroon hover:text-maroon2 flex items-center gap-1.5">
              Ir al OMS <I n="chev-r" s={12} />
            </button>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full min-w-[560px]">
              <thead><tr><Th>Código</Th><Th>Cliente</Th><Th>Total</Th><Th>Estado</Th></tr></thead>
              <tbody>
                {ORDERS.slice(0, 5).map((o) => (
                  <tr key={o.id} className="hover:bg-paper2/50 transition-colors">
                    <Td className="font-mono text-[12px] font-semibold">{o.code}</Td>
                    <Td>{o.customer}</Td>
                    <Td className="tnum font-semibold">{fmt2(o.total)}</Td>
                    <Td><StatusChip s={o.status} /></Td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>

        <div className="space-y-6">
          <Card className="p-5">
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-bold text-[15px] tracking-tight">Taller ahora</h3>
              <button onClick={() => go("taller")} className="text-[12px] font-semibold text-maroon hover:text-maroon2 flex items-center gap-1.5">Ver <I n="chev-r" s={12} /></button>
            </div>
            {[["En corte", 1, "OF-2210 · 18 sillas Vela"], ["Ensamble", 1, "OF-2212 · Aparador Bruma"], ["Acabado", 1, "OF-2209 · Mesa a medida"], ["Control de calidad", 1, "OF-2207 · 4 butacas Aura"]].map(([k, , d]) => (
              <div key={k as string} className="flex items-center justify-between py-2 border-b border-line/60 last:border-0 text-[12.5px]">
                <span className="text-ink2 font-medium">{k}</span>
                <span className="text-stone text-[11.5px] truncate ml-3">{d}</span>
              </div>
            ))}
          </Card>
          <Card className="p-5 bg-coal border-coal text-cream">
            <p className="text-[10.5px] font-bold tracking-[0.16em] uppercase text-cream/40">Estado del sistema</p>
            <div className="flex items-center gap-3 mt-3">
              <span className="w-2.5 h-2.5 rounded-full bg-ok pulse-ok" />
              <p className="font-display font-medium text-[18px]">Todo operativo</p>
            </div>
            <p className="text-[12px] text-cream/55 mt-2 leading-relaxed">
              API 8 ms · PayPhone webhook OK · SRI en línea · MinIO 62% usado. Último deploy: hoy 03:12, cero caída.
            </p>
          </Card>
        </div>
      </div>
    </div>
  );
}

export function StatusChip({ s }: { s: string }) {
  const map: Record<string, { t: "ok" | "warn" | "bad" | "neutral" | "maroon"; d?: boolean }> = {
    "Pago pendiente": { t: "warn", d: true },
    "Pago aprobado": { t: "maroon", d: true },
    "En taller": { t: "neutral", d: true },
    "En transporte": { t: "neutral", d: true },
    "Entregado": { t: "ok", d: true },
    "Publicado": { t: "ok" }, "Borrador": { t: "neutral" },
    "Activo": { t: "ok" }, "Usado": { t: "neutral" }, "Revocado": { t: "bad" }, "Expirado": { t: "warn" },
    "Autorizada": { t: "ok" }, "En contingencia": { t: "warn" },
    "Aprobado": { t: "ok" }, "En revisión": { t: "warn" },
  };
  const m = map[s] || { t: "neutral" as const };
  return <Chip tone={m.t} dot={m.d}>{s}</Chip>;
}

export type { Mod };
