import { useCallback, useEffect, useRef, useState } from "react";
import { ORDERS, INVOICES, VERSION, fmt2, loadNotifs, saveNotifs, loadRealEvents, seed } from "../../data";
import { I, ToastHost, type IconName } from "../ui";
import { Card, Chip, Stat, Td, Th, btnDark, btnGhost } from "./pui";
import { CRM, PIM } from "./Modules";
import { CRMReal } from "./CRMReal";
import { PIMReal } from "./PIMReal";
import { OMSReal } from "./OMSReal";
import { DAM, Proveedores, Taller } from "./Modules2";
import { Contabilidad, Infra } from "./Modules3";
import { BOM, Cobros, Logistica, Seguridad } from "./Modules4";
import { OMS15 } from "./Modules5";
import { CMS, EditorHome, SitioPublico } from "./Modules6";
import { Marketing, Stock, Variantes } from "./Modules7";
import { Compras, RRHH } from "./Modules8";
import { AdminPedidosWeb } from "../CustomerAccount";
import { adminCreado } from "../../data";
import { BletiaMark, LoginScreen, ROLE_LABEL, SetupScreen, useAuth, type Role } from "./auth";

export type Mod =
  | "vision" | "oms" | "logistica" | "taller" | "bom" | "stock"
  | "relaciones" | "cobros" | "compras" | "pim" | "variantes" | "dam"
  | "conta" | "rrhh" | "seguridad" | "infra" | "sitio" | "cms" | "marketing" | "home";

/* Estructura de TALLER UNO (rama ac8f5) — 15 módulos en 6 grupos */
const NAV: { group: string; items: { id: Mod; label: string; icon: IconName; api?: boolean }[] }[] = [
  { group: "Operación", items: [
    { id: "vision", label: "Panel de control", icon: "grid" },
    { id: "oms", label: "Pedidos · OMS", icon: "box", api: true },
    { id: "logistica", label: "Logística & guías SRI", icon: "truck" },
    { id: "taller", label: "Taller & fabricación", icon: "hammer" },
    { id: "bom", label: "BOM & materiales", icon: "tag" },
    { id: "stock", label: "Stock & bodegas", icon: "box" },
  ]},
  { group: "Relaciones", items: [
    { id: "relaciones", label: "Clientes & proveedores", icon: "users" },
    { id: "cobros", label: "Cobros PayPhone", icon: "card" },
    { id: "compras", label: "Compras · OC proveedores", icon: "truck" },
  ]},
  { group: "Producto & activos", items: [
    { id: "pim", label: "Productos · PIM", icon: "tag", api: true },
    { id: "variantes", label: "Variables & variantes", icon: "spark" },
    { id: "dam", label: "Fototeca · DAM", icon: "image" },
  ]},
  { group: "Finanzas & equipo", items: [
    { id: "conta", label: "Contabilidad & SRI", icon: "calc" },
    { id: "rrhh", label: "RRHH · Nómina", icon: "users" },
  ]},
  { group: "Plataforma", items: [
    { id: "seguridad", label: "Seguridad & porting", icon: "shield" },
    { id: "infra", label: "Ajustes & despliegue", icon: "server" },
  ]},
  { group: "Canal digital", items: [
    { id: "home", label: "Portada · Home", icon: "image" },
    { id: "sitio", label: "Sitio público", icon: "eye" },
    { id: "cms", label: "Contenido web · CMS", icon: "doc" },
    { id: "marketing", label: "Marketing · Digest", icon: "pulse" },
  ]},
];

const TITLES: Record<Mod, string> = {
  vision: "Panel de control", oms: "Pedidos · máquina de 15 estados", logistica: "Logística & guías SRI",
  taller: "Taller & fabricación", bom: "BOM & materiales · MRP", relaciones: "Clientes & proveedores",
  cobros: "Cobros PayPhone", pim: "Productos · PIM", dam: "Fototeca · DAM",
  conta: "Contabilidad & SRI", seguridad: "Seguridad & porting",
  infra: "Ajustes & despliegue", sitio: "Sitio público · bletia.ec", cms: "Contenido web · CMS",
  variantes: "Variables & variantes", stock: "Stock & bodegas", marketing: "Marketing · Digest",
  compras: "Compras · Órdenes al proveedor", rrhh: "RRHH · Nómina", home: "Portada · bletia.ec",
};

/* Cada rol ve solo su área. Gerencia lo ve todo.
   "infra" (stack/lenguaje) y "seguridad" quedan solo para gerencia. */
const ACCESS: Record<Mod, Role[]> = {
  vision: ["gerencia", "ventas", "taller", "logistica", "contabilidad"],
  oms: ["gerencia", "ventas", "taller", "logistica"],
  logistica: ["gerencia", "logistica"],
  taller: ["gerencia", "taller"],
  bom: ["gerencia", "taller"],
  relaciones: ["gerencia", "ventas", "taller", "logistica"],
  cobros: ["gerencia", "ventas", "contabilidad"],
  pim: ["gerencia", "ventas", "taller"],
  dam: ["gerencia", "ventas", "taller"],
  conta: ["gerencia", "contabilidad"],
  seguridad: ["gerencia"],
  infra: ["gerencia"],
  sitio: ["gerencia", "ventas"],
  cms: ["gerencia"],
  marketing: ["gerencia", "ventas"],
  variantes: ["gerencia", "ventas", "taller"],
  stock: ["gerencia", "taller", "logistica"],
  compras: ["gerencia", "logistica", "contabilidad"],
  rrhh: ["gerencia", "contabilidad"],
  home: ["gerencia"],
};

/* ---- motor de eventos REALES (solo muestra lo que realmente pasa) ---- */
export type LogItem = { t: string; type: string; mod: string };

function useEventEngine() {
  const [log, setLog] = useState<LogItem[]>([]);
  const [eventCount, setEventCount] = useState(0);

  // Cargar eventos reales del localStorage
  useEffect(() => {
    const loadEvents = () => {
      const events = loadRealEvents();
      setLog(events.map((e) => ({ t: e.timestamp, type: e.type, mod: e.module })).slice(0, 12));
      setEventCount(events.length);
    };
    loadEvents();
    // Actualizar cada 2 segundos para ver eventos nuevos
    const t = setInterval(loadEvents, 2000);
    return () => clearInterval(t);
  }, []);

  return { log, eventCount };
}

/* ---- iconos sol/luna (inline, no dependen de la librería) ---- */
const SunIcon = ({ s = 16 }: { s?: number }) => (
  <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round"><circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4" /></svg>
);
const MoonIcon = ({ s = 16 }: { s?: number }) => (
  <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round"><path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5z" /></svg>
);

/* ---- banner de bienvenida editable ---- */
const WELCOME_KEY = "bletia-welcome";
const DEFAULT_WELCOME = "Bienvenidos a un día más de trabajo. Hoy también creamos piezas que durarán generaciones.";
function WelcomeBanner({ name }: { name: string }) {
  const [text, setText] = useState(() => localStorage.getItem(WELCOME_KEY) || DEFAULT_WELCOME);
  const [editing, setEditing] = useState(false);
  const [draft, setDraft] = useState(text);

  const save = () => {
    const t = draft.trim() || DEFAULT_WELCOME;
    localStorage.setItem(WELCOME_KEY, t);
    setText(t); setEditing(false);
  };

  return (
    <div className="bg-coal text-cream p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4 relative overflow-hidden grain">
      <span className="absolute left-0 top-0 bottom-0 w-[3px] bg-maroon" />
      <div className="flex-1 min-w-0">
        <p className="text-[10.5px] font-bold tracking-[0.2em] uppercase text-cream/40">
          {new Date().toLocaleDateString("es-EC", { weekday: "long", day: "numeric", month: "long" })}
        </p>
        {editing ? (
          <div className="flex flex-col sm:flex-row gap-2 mt-2">
            <input autoFocus value={draft} onChange={(e) => setDraft(e.target.value)}
              onKeyDown={(e) => e.key === "Enter" && save()}
              className="flex-1 bg-cream/10 border border-cream/25 px-3 py-2 text-[14px] text-cream outline-none focus:border-maroon" />
            <button onClick={save} className={`${btnDark} !bg-maroon !border-maroon !py-2 text-[12px]`}>
              <I n="check" s={13} /> Guardar
            </button>
          </div>
        ) : (
          <p className="font-display font-medium text-[17px] sm:text-[19px] leading-snug mt-1.5">
            Buen día, {name.split(" ")[0]}. <span className="text-cream/80">{text}</span>
          </p>
        )}
      </div>
      {!editing && (
        <button onClick={() => { setDraft(text); setEditing(true); }}
          className="shrink-0 inline-flex items-center gap-2 text-[11.5px] font-semibold text-cream/60 hover:text-cream border border-cream/25 hover:border-cream/50 px-3 py-2 transition-colors">
          <I n="spark" s={13} /> inspírate · editar mensaje
        </button>
      )}
    </div>
  );
}

export default function Panel() {
  const { session, login, logout } = useAuth();
  const [mod, setMod] = useState<Mod>("vision");
  const [env, setEnv] = useState<"staging" | "producción">("staging");
  const [nav, setNav] = useState(false);
  const [userMenu, setUserMenu] = useState(false);
  const [workerLogin, setWorkerLogin] = useState(false);
  const [dark, setDark] = useState(() => localStorage.getItem("bletia-theme") === "dark");
  const [splash, setSplash] = useState(true);
  const [useAPI, setUseAPI] = useState(() => localStorage.getItem("bletia-use-api") === "true");
  const [notifOpen, setNotifOpen] = useState(false);
  const [notifs, setNotifs] = useState(loadNotifs);
  const engine = useEventEngine();

  /* nuevas órdenes de la tienda web → campana de notificaciones */
  useEffect(() => {
    const t = setInterval(() => setNotifs(loadNotifs()), 2500);
    return () => clearInterval(t);
  }, []);
  const noLeidas = notifs.filter((n) => !n.leida).length;
  const leerTodas = () => { saveNotifs(notifs.map((n) => ({ ...n, leida: true }))); setNotifs(loadNotifs()); };

  useEffect(() => {
    document.documentElement.classList.toggle("dark", dark);
    localStorage.setItem("bletia-theme", dark ? "dark" : "light");
  }, [dark]);

  useEffect(() => {
    localStorage.setItem("bletia-use-api", useAPI ? "true" : "false");
  }, [useAPI]);

  /* splash de arranque (firma de TALLER UNO) */
  useEffect(() => {
    const t = setTimeout(() => setSplash(false), 900);
    return () => clearTimeout(t);
  }, []);

  /* al entrar, asegura que el rol esté viendo un módulo permitido */
  useEffect(() => {
    if (session && !ACCESS[mod].includes(session.role)) setMod("vision");
  }, [session, mod]);

  /* entrada de colaboradores (los trabajadores eligen su rol aquí) */
  if (workerLogin)
    return <LoginScreen onLogin={(s) => { login(s); setWorkerLogin(false); setMod("vision"); }} onBack={() => setWorkerLogin(false)} />;

  /* primer acceso: crear el administrador (una sola vez) */
  if (!adminCreado())
    return <SetupScreen onDone={(s) => { login(s); setMod("vision"); }} />;

  /* sin sesión: mostrar login */
  if (!session)
    return <LoginScreen onLogin={(s) => { login(s); setMod("vision"); }} />;

  const role = session.role;
  const visibleNav = NAV.map((g) => ({ ...g, items: g.items.filter((it) => ACCESS[it.id].includes(role)) }))
    .filter((g) => g.items.length > 0);

  const go = (m: Mod) => { setMod(m); setNav(false); };

  return (
    <div className="min-h-screen bg-paper text-ink font-dash transition-colors">
      {/* splash de arranque */}
      <div className={`fixed inset-0 z-[99] bg-coal grid place-items-center transition-opacity duration-500 ${splash ? "opacity-100" : "opacity-0 pointer-events-none"}`}>
        <div className="text-center">
          <div className="w-16 h-16 mx-auto bg-maroon grid place-items-center text-cream anim-pop">
            <BletiaMark light size={30} />
          </div>
          <div className="font-display font-semibold text-[20px] text-cream tracking-[0.3em] mt-4">BLETIA</div>
          <div className="font-mono text-[10px] tracking-[0.3em] text-cream/40 uppercase mt-1.5">Suite mueblera · ERP · CRM · PIM · OMS · MES</div>
          <div className="mt-5 w-44 h-1 mx-auto bg-cream/10 overflow-hidden">
            <div className={`h-full bg-maroon transition-transform duration-700 origin-left ${splash ? "scale-x-100" : "scale-x-0"}`} />
          </div>
        </div>
      </div>

      <ToastHost />
      {/* ---------- Sidebar ---------- */}
      <aside className={`fixed inset-y-0 left-0 z-[70] w-[248px] bg-coal text-cream flex flex-col transition-transform duration-300 lg:translate-x-0 ${nav ? "translate-x-0" : "-translate-x-full"}`}>
        <div className="h-16 px-5 flex items-center justify-between border-b border-cream/10 shrink-0">
          <BletiaMark light size={18} />
          <button onClick={() => setNav(false)} className="lg:hidden p-1.5 text-cream/60 hover:text-cream" aria-label="Cerrar menú"><I n="close" s={16} /></button>
        </div>
        <nav className="flex-1 overflow-y-auto py-5 px-3">
          {visibleNav.map((g) => (
            <div key={g.group} className="mb-6">
              <p className="px-3 mb-2 text-[9.5px] font-bold tracking-[0.22em] uppercase text-cream/35">{g.group}</p>
              {g.items.map((it) => (
                <button key={it.id} onClick={() => go(it.id)}
                  className={`w-full flex items-center gap-3 px-3 py-2.5 text-[13px] font-medium transition-all duration-200 relative ${mod === it.id ? "text-cream bg-cream/5" : "text-cream/55 hover:text-cream hover:bg-cream/5"}`}>
                  {mod === it.id && <span className="absolute left-0 top-1.5 bottom-1.5 w-[2.5px] bg-maroon" />}
                  <I n={it.icon} s={16} className={mod === it.id ? "text-maroon" : ""} />
                  <span className="truncate">{it.label}</span>
                  {it.api && useAPI && <span className="ml-auto text-[9px] font-bold px-1.5 py-0.5 bg-okbg text-ok">API</span>}
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
          <p className="text-[10.5px] text-cream/35 leading-relaxed">OVH Cloud · CloudPanel<br />BLETIA OS {VERSION} · arranque vacío</p>
        </div>
      </aside>
      {nav && <div className="fixed inset-0 z-[65] bg-ink/50 lg:hidden fade-in" onClick={() => setNav(false)} />}

      {/* ---------- Main ---------- */}
      <div className="lg:pl-[248px]">
        <header className="sticky top-0 z-[60] h-16 bg-paper/90 backdrop-blur-md border-b border-line flex items-center justify-between px-5 sm:px-8 transition-colors">
          <div className="flex items-center gap-3 min-w-0">
            <button onClick={() => setNav(true)} className="lg:hidden p-2 -ml-2" aria-label="Menú"><I n="menu" s={19} /></button>
            <div className="min-w-0">
              <h1 className="font-bold text-[15px] tracking-tight truncate">{TITLES[mod]}</h1>
              <p className="text-[11px] text-stone hidden sm:block">
                {session.name} · {ROLE_LABEL[role]} {role !== "gerencia" && <span className="text-stone/70">· viendo solo tu área</span>}
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2.5 sm:gap-3.5">
            <span className="hidden md:inline-flex items-center gap-2 text-[11.5px] font-semibold text-ok bg-okbg px-2.5 py-1.5">
              <span className="w-1.5 h-1.5 rounded-full bg-ok pulse-ok" />
              <span className="tnum">{engine.eventCount}</span> eventos
            </span>
            {/* contraste del dash */}
            <button onClick={() => setDark(!dark)} title="Cambiar contraste del panel"
              className="p-2.5 border border-line bg-card hover:border-ink transition-colors" aria-label="Cambiar contraste">
              {dark ? <SunIcon /> : <MoonIcon />}
            </button>
            {/* alternar entre localStorage y API */}
            <button onClick={() => setUseAPI(!useAPI)} title={useAPI ? "Usando API (PostgreSQL)" : "Usando localStorage"}
              className={`p-2.5 border transition-colors ${useAPI ? "border-ok bg-okbg text-ok" : "border-line bg-card hover:border-ink"}`}
              aria-label="Alternar fuente de datos">
              <I n={useAPI ? "server" : "doc"} s={16} />
            </button>
            <button onClick={() => setEnv(env === "staging" ? "producción" : "staging")}
              className={`hidden sm:block text-[11px] font-bold tracking-wide uppercase px-3 py-1.5 border transition-colors ${env === "staging" ? "border-warn/40 text-warn bg-warnbg hover:border-warn" : "border-ok/40 text-ok bg-okbg hover:border-ok"}`}>
              {env}
            </button>
            {/* campana de notificaciones: nuevas órdenes de la tienda */}
            <div className="relative">
              <button onClick={() => setNotifOpen(!notifOpen)} title="Notificaciones de pedidos" aria-label="Notificaciones"
                className="relative p-2.5 border border-line bg-card hover:border-ink transition-colors">
                <I n="pulse" s={17} />
                {noLeidas > 0 && (
                  <span className="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 bg-maroon text-cream text-[9.5px] font-bold flex items-center justify-center tnum">{noLeidas}</span>
                )}
              </button>
              {notifOpen && (
                <>
                  <div className="fixed inset-0 z-[64]" onClick={() => setNotifOpen(false)} />
                  <div className="absolute right-0 top-full mt-2 w-[340px] bg-card border border-line shadow-[0_18px_50px_rgba(20,16,10,0.25)] z-[65] fade-in">
                    <div className="flex items-center justify-between px-4 py-3 border-b border-line">
                      <p className="text-[12.5px] font-bold">Notificaciones</p>
                      {noLeidas > 0 && <button onClick={leerTodas} className="text-[11px] font-semibold text-maroon u-grow">Marcar leídas</button>}
                    </div>
                    <div className="max-h-[320px] overflow-y-auto">
                      {notifs.length === 0 ? (
                        <p className="px-4 py-8 text-center text-[12px] text-stone">Aún no hay notificaciones.<br />Cuando entre un pedido de la web, aparecerá aquí.</p>
                      ) : (
                        notifs.map((n) => (
                          <button key={n.id} onClick={() => { saveNotifs(notifs.map((x) => x.id === n.id ? { ...x, leida: true } : x)); setNotifs(loadNotifs()); }}
                            className={`w-full text-left px-4 py-3 border-b border-line last:border-0 transition-colors hover:bg-paper2 ${n.leida ? "opacity-55" : ""}`}>
                            <div className="flex items-center gap-2">
                              <span className={`w-1.5 h-1.5 rounded-full shrink-0 ${n.tipo === "pedido" ? "bg-maroon" : n.tipo === "abandono" ? "bg-warn" : "bg-ok"}`} />
                              <p className="text-[10.5px] font-bold uppercase tracking-wider text-stone">{n.tipo === "pedido" ? "Nueva orden" : n.tipo === "abandono" ? "Carrito abandonado" : "Pago"} · {n.fecha}</p>
                            </div>
                            <p className="text-[12px] leading-snug mt-1.5">{n.texto}</p>
                          </button>
                        ))
                      )}
                    </div>
                  </div>
                </>
              )}
            </div>
            <div className="relative">
              <button onClick={() => setUserMenu(!userMenu)} title="Sesión y roles"
                className={`w-8 h-8 text-[11px] font-bold flex items-center justify-center transition-colors ${role === "gerencia" ? "bg-ink text-paper hover:bg-maroon" : "bg-maroon text-cream hover:bg-maroon2"}`}>
                {session.name.split(" ").slice(0, 2).map((w) => w[0]).join("")}
              </button>
              {userMenu && (
                <>
                  <div className="fixed inset-0 z-[64]" onClick={() => setUserMenu(false)} />
                  <div className="absolute right-0 top-full mt-2 w-60 bg-card border border-line shadow-[0_18px_50px_rgba(20,16,10,0.25)] z-[65] fade-in">
                    <div className="px-3.5 py-3 border-b border-line">
                      <p className="text-[12.5px] font-bold leading-tight">{session.name}</p>
                      <p className="text-[10.5px] text-stone mt-0.5">
                        {ROLE_LABEL[role]} {role === "gerencia" ? "· acceso a los 13 módulos" : "· viendo solo tu área"}
                      </p>
                    </div>
                    <button onClick={() => { setWorkerLogin(true); setUserMenu(false); }}
                      className="w-full text-left px-3.5 py-2.5 text-[12.5px] font-medium hover:bg-paper2 transition-colors flex items-center gap-2.5">
                      <I n="users" s={14} className="text-stone" /> Entrar como colaborador
                    </button>
                    {role !== "gerencia" && (
                      <button onClick={() => { logout(); setUserMenu(false); }}
                        className="w-full text-left px-3.5 py-2.5 text-[12.5px] font-medium hover:bg-paper2 transition-colors flex items-center gap-2.5">
                        <I n="shield" s={14} className="text-maroon" /> Volver a Gerencia (todo)
                      </button>
                    )}
                  </div>
                </>
              )}
            </div>
          </div>
        </header>

        <main className="p-5 sm:p-8 max-w-[1240px] space-y-6">
          {mod === "vision" && <WelcomeBanner name={session.name} />}
          {mod === "vision" && <Vision engine={engine} go={go} />}
          {mod === "oms" && (useAPI ? <OMSReal /> : <><OMS15 /><AdminPedidosWeb /></>)}
          {mod === "logistica" && <Logistica />}
          {mod === "taller" && <Taller />}
          {mod === "bom" && <BOM />}
          {mod === "relaciones" && <Relaciones role={role} useAPI={useAPI} />}
          {mod === "cobros" && <Cobros />}
          {mod === "pim" && (useAPI ? <PIMReal /> : <PIM />)}
          {mod === "variantes" && <Variantes />}
          {mod === "dam" && <DAM />}
          {mod === "stock" && <Stock />}
          {mod === "conta" && <Contabilidad />}
          {mod === "compras" && <Compras />}
          {mod === "rrhh" && <RRHH />}
          {mod === "seguridad" && <Seguridad />}
          {mod === "infra" && <Infra />}
          {mod === "sitio" && <SitioPublico />}
          {mod === "cms" && <CMS />}
          {mod === "home" && <EditorHome />}
          {mod === "marketing" && <Marketing />}
        </main>
      </div>
    </div>
  );
}

/* ---- Clientes & proveedores: un módulo, dos áreas (CRM + SRM intactos) ---- */
function Relaciones({ role, useAPI }: { role: Role; useAPI: boolean }) {
  const veClientes = role === "gerencia" || role === "ventas";
  const veProv = role === "gerencia" || role === "taller" || role === "logistica";
  const [tab, setTab] = useState<"clientes" | "proveedores">(veClientes ? "clientes" : "proveedores");
  const activo = tab === "clientes" && veClientes ? "clientes" : veProv ? "proveedores" : "clientes";

  return (
    <div className="space-y-5">
      <div className="flex gap-2">
        {veClientes && (
          <button onClick={() => setTab("clientes")}
            className={`px-4 py-2.5 text-[12.5px] font-semibold border transition-colors flex items-center gap-2 ${activo === "clientes" ? "bg-ink text-paper border-ink" : "border-linedark text-ink2 hover:border-ink"}`}>
            <I n="users" s={14} /> Clientes · CRM {useAPI && <span className="text-[9px] font-bold px-1.5 py-0.5 bg-okbg text-ok">API</span>}
          </button>
        )}
        {veProv && (
          <button onClick={() => setTab("proveedores")}
            className={`px-4 py-2.5 text-[12.5px] font-semibold border transition-colors flex items-center gap-2 ${activo === "proveedores" ? "bg-ink text-paper border-ink" : "border-linedark text-ink2 hover:border-ink"}`}>
            <I n="truck" s={14} /> Proveedores · SRM
          </button>
        )}
      </div>
      {activo === "clientes" ? (useAPI ? <CRMReal /> : <CRM />) : <Proveedores />}
    </div>
  );
}

/* ================= Visión general ================= */
function Vision({ engine, go }: { engine: ReturnType<typeof useEventEngine>; go: (m: Mod) => void }) {
  const { log, eventCount } = engine;

  /* Indicadores calculados de los datos reales (inician en cero con la empresa vacía) */
  const ventas = ORDERS.reduce((a, o) => a + o.total, 0);
  const activos = ORDERS.filter((o) => o.status !== "Entregado").length;
  const facturado = INVOICES.reduce((a, f) => a + f.total, 0);

  return (
    <div className="fade-in space-y-6">
      <div className="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Ventas registradas" value={fmt2(ventas)} sub={ORDERS.length ? `${ORDERS.length} pedidos` : "Aún sin ventas"} />
        <Stat label="Pedidos activos" value={activos} sub={activos ? "en proceso" : "Sin pedidos en curso"} />
        <Stat label="Eventos registrados" value={eventCount} sub={eventCount ? "acciones reales del sistema" : "Sin actividad aún"} />
        <Stat label="Facturado" value={fmt2(facturado)} sub={INVOICES.length ? `${INVOICES.length} facturas SRI` : "Sin facturas emitidas"} />
      </div>

      <div className="grid xl:grid-cols-3 gap-6">
        <Card className="xl:col-span-2 p-5 sm:p-6">
          <div className="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div>
              <h3 className="font-bold text-[15px] tracking-tight flex items-center gap-2">
                Registro de actividad
                <span className="text-[9px] font-bold tracking-[0.16em] uppercase px-2 py-1 bg-okbg text-ok">En vivo</span>
              </h3>
              <p className="text-[12px] text-stone mt-1">
                Solo muestra acciones reales del sistema: pedidos creados, correos enviados, cambios de estado, etc.
              </p>
            </div>
          </div>

          {log.length === 0 ? (
            <div className="py-12 text-center">
              <p className="text-[13px] text-stone">Aún no hay actividad registrada.</p>
              <p className="text-[11.5px] text-stone mt-1">Las acciones del sistema aparecerán aquí en tiempo real.</p>
            </div>
          ) : (
            <div className="space-y-1">
              {log.map((l, i) => (
                <div key={l.t + i + l.type} className={`flex items-center gap-2.5 py-2 border-b border-line/60 ${i === 0 ? "anim-feed" : ""}`}>
                  <span className={`w-1.5 h-1.5 rounded-full shrink-0 ${i === 0 ? "bg-ok pulse-ok" : "bg-linedark"}`} />
                  <code className="text-[11.5px] font-mono text-ink2 truncate flex-1">{l.type}</code>
                  <span className="text-[9.5px] font-bold uppercase tracking-wider text-stone bg-paper2 px-1.5 py-0.5">{l.mod}</span>
                  <span className="text-[10px] text-stone tnum shrink-0">{l.t}</span>
                </div>
              ))}
            </div>
          )}
        </Card>

        <Card className="p-5 sm:p-6 flex flex-col">
          <h3 className="font-bold text-[15px] tracking-tight">Acciones rápidas</h3>
          <p className="text-[12px] text-stone mt-0.5 mb-4">Accesos directos a los módulos más usados.</p>
          <div className="space-y-2 flex-1">
            <button onClick={() => go("oms")} className="w-full text-left px-3.5 py-2.5 border border-line hover:border-ink hover:bg-paper2 transition-colors flex items-center gap-2.5">
              <I n="box" s={15} className="text-stone" />
              <span className="text-[12.5px] font-medium">Gestionar pedidos</span>
            </button>
            <button onClick={() => go("pim")} className="w-full text-left px-3.5 py-2.5 border border-line hover:border-ink hover:bg-paper2 transition-colors flex items-center gap-2.5">
              <I n="tag" s={15} className="text-stone" />
              <span className="text-[12.5px] font-medium">Productos y catálogo</span>
            </button>
            <button onClick={() => go("cms")} className="w-full text-left px-3.5 py-2.5 border border-line hover:border-ink hover:bg-paper2 transition-colors flex items-center gap-2.5">
              <I n="doc" s={15} className="text-stone" />
              <span className="text-[12.5px] font-medium">Contenido web</span>
            </button>
            <button onClick={() => go("infra")} className="w-full text-left px-3.5 py-2.5 border border-line hover:border-ink hover:bg-paper2 transition-colors flex items-center gap-2.5">
              <I n="server" s={15} className="text-stone" />
              <span className="text-[12.5px] font-medium">Ajustes y despliegue</span>
            </button>
          </div>
        </Card>
      </div>

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
                {ORDERS.length === 0 && (
                  <tr>
                    <td colSpan={4} className="px-5 py-10 text-center text-[13px] text-stone">
                      Aún no hay pedidos. Cuando entre el primero, aparecerá aquí.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </Card>

        <Card className="p-5 bg-coal border-coal text-cream">
          <p className="text-[10.5px] font-bold tracking-[0.16em] uppercase text-cream/40">Estado del sistema</p>
          <div className="flex items-center gap-3 mt-3">
            <span className="w-2.5 h-2.5 rounded-full bg-ok pulse-ok" />
            <p className="font-display font-medium text-[18px]">Todo operativo</p>
          </div>
          <p className="text-[12px] text-cream/55 mt-2 leading-relaxed">
            Fase 1 en línea: tienda y panel publicados en tu VPS; los datos se guardan en este navegador.
            Fase 2 (API + PostgreSQL en el mismo VPS) convertirá eventos, pedidos y clientes en datos de servidor.
          </p>
          <div className="mt-4 pt-4 border-t border-cream/15 grid grid-cols-2 gap-3 text-[11px] text-cream/50">
            <span className="flex items-center gap-1.5"><I n="check" s={12} className="text-ok" /> Fase 1 · activa</span>
            <span className="flex items-center gap-1.5"><I n="server" s={12} className="text-maroon" /> Fase 2 · base de datos</span>
          </div>
        </Card>
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
    "En tránsito": { t: "neutral", d: true },
  };
  const m = map[s] || { t: "neutral" as const };
  return <Chip tone={m.t} dot={m.d}>{s}</Chip>;
}
