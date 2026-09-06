import { useState } from "react";
import { VERSION, adminCreado, loadAdmin, loadLogin, saveAdmin } from "../../data";
import { I } from "../ui";

/* ---------- Modelo de roles ---------- */
export type Role = "gerencia" | "ventas" | "taller" | "logistica" | "contabilidad";

export const ROLES: { id: Role; label: string; desc: string; icon: "grid" | "cart" | "hammer" | "truck" | "calc" }[] = [
  { id: "gerencia", label: "Gerencia", desc: "Acceso total · los 15 módulos", icon: "grid" },
  { id: "ventas", label: "Ventas", desc: "OMS · Relaciones · Cobros · PIM · DAM · Canal digital", icon: "cart" },
  { id: "taller", label: "Taller", desc: "MES · BOM · PIM · DAM", icon: "hammer" },
  { id: "logistica", label: "Logística", desc: "Guías SRI · Despachos · Transporte", icon: "truck" },
  { id: "contabilidad", label: "Contabilidad", desc: "SRI · Facturas · Cobros", icon: "calc" },
];

export const ROLE_LABEL: Record<Role, string> = {
  gerencia: "Gerencia", ventas: "Ventas", taller: "Taller",
  logistica: "Logística", contabilidad: "Contabilidad",
};

/* ---------- Logo BLETIA (SVG, nunca texto plano) ---------- */
export function BletiaMark({ light = false, size = 22 }: { light?: boolean; size?: number }) {
  return (
    <span className="inline-flex items-center gap-2.5 select-none">
      <svg width={size} height={size} viewBox="0 0 24 24" fill="none" aria-hidden="true">
        {/* monograma B: dos arcos apilados sobre una base de mueble */}
        <path d="M7 3h6a3.5 3.5 0 0 1 0 7H7zM7 10h7a3.5 3.5 0 0 1 0 7H7z"
          stroke={light ? "#ece8df" : "#1c1a16"} strokeWidth="1.8" strokeLinejoin="round" />
        <path d="M5 21h14M7 17.5V21M17 17.5V21"
          stroke="#800000" strokeWidth="1.8" strokeLinecap="round" />
      </svg>
      <span className={`font-display font-semibold tracking-[0.3em] text-[15px] leading-none ${light ? "text-cream" : "text-ink"}`}>
        BLETIA<span className="text-maroon">.</span>
      </span>
    </span>
  );
}

/* ---------- Sesión (localStorage) ---------- */
/* v2: invalida sesiones viejas (p. ej. un rol de trabajador guardado de una
   versión previa) para que el dueño siempre aterrice en Gerencia. */
const KEY = "bletia-session-v2";
export type Session = { role: Role; name: string };

/* El dueño entra con su correo+contraseña (rol gerencia, los 15 módulos).
   Los colaboradores eligen su rol. Si no hay sesión, se muestra el login. */
const VALID_ROLES: Role[] = ["gerencia", "ventas", "taller", "logistica", "contabilidad"];

function isSession(s: unknown): s is Session {
  if (!s || typeof s !== "object") return false;
  const o = s as Record<string, unknown>;
  return typeof o.name === "string" && o.name.length > 0 &&
    VALID_ROLES.includes(o.role as Role);
}

export function useAuth() {
  const [session, setSession] = useState<Session | null>(() => {
    try {
      const saved = JSON.parse(localStorage.getItem(KEY) || "null");
      return isSession(saved) ? saved : null;
    } catch { return null; }
  });
  const login = (s: Session) => { localStorage.setItem(KEY, JSON.stringify(s)); setSession(s); };
  const logout = () => { localStorage.removeItem(KEY); setSession(null); };
  return { session, login, logout };
}

/* ---------- Asistente de configuración inicial (crear el admin, una sola vez) ---------- */
export function SetupScreen({ onDone }: { onDone: (s: Session) => void }) {
  const [f, setF] = useState({ empresa: "", nombre: "", email: "", pass: "", pass2: "" });
  const [err, setErr] = useState("");

  const crear = () => {
    if (f.empresa.trim().length < 2) return setErr("Escribe el nombre de tu empresa.");
    if (f.nombre.trim().length < 2) return setErr("Escribe tu nombre (serás el administrador).");
    if (!/^\S+@\S+\.\S+$/.test(f.email.trim())) return setErr("Escribe un correo válido.");
    if (f.pass.length < 6) return setErr("La contraseña debe tener al menos 6 caracteres.");
    if (f.pass !== f.pass2) return setErr("Las contraseñas no coinciden.");
    saveAdmin({
      nombre: f.nombre.trim(), email: f.email.trim().toLowerCase(), empresa: f.empresa.trim(),
      pass: f.pass, creado: new Date().toLocaleDateString("es-EC"),
    });
    onDone({ role: "gerencia", name: f.nombre.trim() });
  };

  return (
    <div className="min-h-screen grid place-items-center bg-paper font-dash text-ink p-6">
      <div className="w-full max-w-[460px] fade-in">
        <BletiaMark size={26} />
        <h1 className="font-display font-medium text-[clamp(1.8rem,3vw,2.4rem)] leading-[1.05] mt-8">
          Bienvenido. Configuremos tu empresa.
        </h1>
        <p className="text-[13.5px] text-stone leading-relaxed mt-3">
          Este es el primer acceso. Crea tu <strong className="text-ink">usuario administrador</strong>: con él
          entrarás al panel y cargarás toda la información de tu negocio. Guárdalo bien, que no se puede recuperar.
        </p>

        <div className="grid gap-3.5 mt-7">
          <label className="block">
            <span className="block text-[11px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Nombre de la empresa</span>
            <input value={f.empresa} onChange={(e) => { setF({ ...f, empresa: e.target.value }); setErr(""); }} placeholder="Ej. BLETIA S.A.S." className={campo} />
          </label>
          <label className="block">
            <span className="block text-[11px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Tu nombre (administrador)</span>
            <input value={f.nombre} onChange={(e) => { setF({ ...f, nombre: e.target.value }); setErr(""); }} placeholder="Ej. Diego Pillacela" className={campo} />
          </label>
          <label className="block">
            <span className="block text-[11px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Correo</span>
            <input value={f.email} onChange={(e) => { setF({ ...f, email: e.target.value }); setErr(""); }} placeholder="tu@empresa.ec" type="email" className={campo} />
          </label>
          <div className="grid grid-cols-2 gap-3">
            <label className="block">
              <span className="block text-[11px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Contraseña</span>
              <input value={f.pass} onChange={(e) => { setF({ ...f, pass: e.target.value }); setErr(""); }} type="password" className={campo} />
            </label>
            <label className="block">
              <span className="block text-[11px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Repítela</span>
              <input value={f.pass2} onChange={(e) => { setF({ ...f, pass2: e.target.value }); setErr(""); }} type="password" onKeyDown={(e) => e.key === "Enter" && crear()} className={campo} />
            </label>
          </div>
        </div>

        {err && <p className="text-bad text-[12.5px] font-medium mt-4 flex items-center gap-2"><I n="alert" s={14} />{err}</p>}

        <button onClick={crear}
          className="w-full mt-6 bg-ink text-paper text-[13px] font-semibold py-4 hover:bg-maroon transition-colors flex items-center justify-center gap-2">
          Crear administrador y entrar <I n="arrow" s={15} />
        </button>
        <p className="text-[11px] text-stone text-center mt-4 flex items-center justify-center gap-1.5">
          <I n="shield" s={12} /> Solo tú verás esta pantalla, una única vez.
        </p>
      </div>
    </div>
  );
}
const campo = "w-full border border-linedark bg-card px-3.5 py-3 text-[13.5px] outline-none focus:border-ink transition-colors placeholder:text-stone/60";

/* ---------- Pantalla de login (bletia.ec/dash/login) ---------- */
export function LoginScreen({ onLogin, onBack }: { onLogin: (s: Session) => void; onBack?: () => void }) {
  const cfg = loadLogin();
  const [role, setRole] = useState<Role>("gerencia");
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [pass, setPass] = useState("");
  const [err, setErr] = useState("");

  const submit = () => {
    if (role === "gerencia") {
      const admin = loadAdmin();
      if (!admin) return setErr("Aún no hay administrador creado.");
      if (email.trim().toLowerCase() !== admin.email || pass !== admin.pass) {
        return setErr("Correo o contraseña incorrectos.");
      }
      onLogin({ role, name: admin.nombre });
      return;
    }
    if (name.trim().length < 2) { setErr("Escribe tu nombre para entrar."); return; }
    onLogin({ role, name: name.trim() });
  };

  return (
    <div className="min-h-screen grid lg:grid-cols-2 bg-paper font-dash text-ink">
      {/* panel de marca */}
      <div className="hidden lg:flex flex-col justify-between bg-coal text-cream p-12 grain relative overflow-hidden">
        <BletiaMark light size={26} />
        <div>
          <h1 className="font-display font-medium text-[clamp(2rem,3.4vw,3rem)] leading-[1.05]">
            {cfg.titulo}
          </h1>
          <p className="text-cream/60 text-[14px] leading-relaxed max-w-[44ch] mt-5">
            {cfg.subtitulo}
          </p>
        </div>
        <div className="flex items-center gap-6 text-[11px] font-semibold tracking-[0.18em] uppercase text-cream/40">
          <span className="flex items-center gap-2"><span className="w-1.5 h-1.5 bg-maroon pulse-maroon" />Suite BLETIA OS</span>
          <span>Stack open source</span>
        </div>
      </div>

      {/* formulario */}
      <div className="flex items-center justify-center p-6 sm:p-12">
        <div className="w-full max-w-[420px] fade-in">
          <div className="lg:hidden mb-10"><BletiaMark size={24} /></div>
          {onBack && (
            <button onClick={onBack}
              className="mb-5 inline-flex items-center gap-1.5 text-[12px] font-semibold text-stone hover:text-ink transition-colors">
              <I n="back" s={13} /> Volver al panel de Gerencia
            </button>
          )}
          <h2 className="font-display font-medium text-[28px]">{cfg.bienvenida}</h2>
          <p className="text-[13px] text-stone mt-2">
            Selecciona tu rol: verás solo los módulos que te corresponden.
          </p>

          <div className="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-7">
            {ROLES.map((r) => (
              <button key={r.id} onClick={() => setRole(r.id)}
                className={`text-left border p-3 transition-all duration-200 ${role === r.id ? "border-maroon bg-card shadow-[inset_2px_0_0_#800000]" : "border-linedark hover:border-ink"}`}>
                <I n={r.icon} s={17} className={role === r.id ? "text-maroon" : "text-stone"} />
                <span className="block text-[12.5px] font-semibold mt-2">{r.label}</span>
                <span className="block text-[10px] text-stone leading-snug mt-0.5">{r.desc}</span>
              </button>
            ))}
          </div>

          {role === "gerencia" ? (
            <div className="grid gap-3.5 mt-6">
              <label className="block">
                <span className="block text-[11px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Correo del administrador</span>
                <input value={email} onChange={(e) => { setEmail(e.target.value); setErr(""); }}
                  placeholder="bletia@zohomail.com" type="email" className={campo} />
              </label>
              <label className="block">
                <span className="block text-[11px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Contraseña</span>
                <input value={pass} onChange={(e) => { setPass(e.target.value); setErr(""); }}
                  onKeyDown={(e) => e.key === "Enter" && submit()} type="password" className={campo} />
              </label>
              <p className="text-[11px] text-stone leading-relaxed border border-line bg-paper2/50 px-3 py-2.5">
                <strong className="text-ink">Acceso inicial:</strong> es el único dato que viene de fábrica.
                Entra con <span className="font-mono">bletia@zohomail.com</span> y tu contraseña, y desde el panel
                cargas toda la información de tu empresa.
              </p>
            </div>
          ) : (
            <label className="block mt-6">
              <span className="block text-[11px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Tu nombre</span>
              <input value={name} onChange={(e) => { setName(e.target.value); setErr(""); }}
                onKeyDown={(e) => e.key === "Enter" && submit()}
                placeholder="Ej. Rosa Burbano" className={campo} />
            </label>
          )}
          {err && <p className="text-bad text-[12px] font-medium mt-3 flex items-center gap-2"><I n="alert" s={13} />{err}</p>}

          <button onClick={submit}
            className="w-full mt-6 bg-ink text-paper text-[13px] font-semibold py-4 hover:bg-maroon transition-colors flex items-center justify-center gap-2">
            Entrar al panel <I n="arrow" s={15} />
          </button>

          <p className="text-[11px] text-stone text-center mt-5 flex items-center justify-center gap-1.5">
            <I n="shield" s={12} /> Acceso auditado · los enlaces de un solo uso expiran tras su uso
          </p>
          <p className="text-center mt-4">
            <a href="#top" className="text-[11.5px] font-semibold text-stone hover:text-ink u-grow">← Volver a la tienda pública</a>
          </p>
          <p className="text-center mt-5 text-[10px] font-semibold tracking-[0.22em] uppercase text-stone/60">
            BLETIA OS · {VERSION}
          </p>
        </div>
      </div>
    </div>
  );
}
