import { useEffect, useState } from "react";
import { I } from "../ui";

/* ---------- Modelo de roles ---------- */
export type Role = "gerencia" | "ventas" | "taller" | "logistica" | "contabilidad";

export const ROLES: { id: Role; label: string; desc: string; icon: "grid" | "cart" | "hammer" | "truck" | "calc" }[] = [
  { id: "gerencia", label: "Gerencia", desc: "Acceso total · los 13 módulos", icon: "grid" },
  { id: "ventas", label: "Ventas", desc: "OMS · CRM · Cobros · PIM · DAM", icon: "cart" },
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
const KEY = "bletia-session";
export type Session = { role: Role; name: string };

export function useAuth() {
  const [session, setSession] = useState<Session | null>(() => {
    try { return JSON.parse(localStorage.getItem(KEY) || "null"); } catch { return null; }
  });
  const login = (s: Session) => { localStorage.setItem(KEY, JSON.stringify(s)); setSession(s); };
  const logout = () => { localStorage.removeItem(KEY); setSession(null); };
  return { session, login, logout };
}

/* ---------- Pantalla de login (bletia.ec/dash/login) ---------- */
export function LoginScreen({ onLogin }: { onLogin: (s: Session) => void }) {
  const [role, setRole] = useState<Role>("gerencia");
  const [name, setName] = useState("");
  const [err, setErr] = useState("");

  const submit = () => {
    if (name.trim().length < 2) { setErr("Escribe tu nombre para entrar."); return; }
    onLogin({ role, name: name.trim() });
  };

  return (
    <div className="min-h-screen grid lg:grid-cols-2 bg-paper font-dash text-ink">
      {/* panel de marca */}
      <div className="hidden lg:flex flex-col justify-between bg-coal text-cream p-12 grain relative overflow-hidden">
        <BletiaMark light size={26} />
        <div>
          <p className="eyebrow !text-cream/40">Panel interno · TALLER UNO</p>
          <h1 className="font-display font-medium text-[clamp(2rem,3.4vw,3rem)] leading-[1.05] mt-4">
            Cada rol ve su área.<br />Gerencia lo ve todo.
          </h1>
          <p className="text-cream/60 text-[14px] leading-relaxed max-w-[44ch] mt-5">
            ERP · CRM · PIM · OMS · MES · DAM · Contabilidad. Cobros PayPhone por links de un solo uso
            y facturación electrónica SRI con IVA 15%.
          </p>
        </div>
        <div className="flex items-center gap-6 text-[11px] font-semibold tracking-[0.18em] uppercase text-cream/40">
          <span className="flex items-center gap-2"><span className="w-1.5 h-1.5 bg-maroon pulse-maroon" />13 módulos</span>
          <span>+2.000 ev/s</span>
          <span>OVH Cloud</span>
        </div>
      </div>

      {/* formulario */}
      <div className="flex items-center justify-center p-6 sm:p-12">
        <div className="w-full max-w-[420px] fade-in">
          <div className="lg:hidden mb-10"><BletiaMark size={24} /></div>
          <p className="eyebrow">Acceso de colaboradores</p>
          <h2 className="font-display font-medium text-[28px] mt-3">Buen día. Entra a tu área.</h2>
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

          <label className="block mt-6">
            <span className="block text-[11px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Tu nombre</span>
            <input value={name} onChange={(e) => { setName(e.target.value); setErr(""); }}
              onKeyDown={(e) => e.key === "Enter" && submit()}
              placeholder="Ej. Rosa Burbano"
              className="w-full border border-linedark bg-card px-3.5 py-3 text-[13.5px] outline-none focus:border-ink transition-colors placeholder:text-stone/60" />
          </label>
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
        </div>
      </div>
    </div>
  );
}
