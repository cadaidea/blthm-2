import { useEffect, useRef, useState, type ReactNode } from "react";

/* ---------- Iconos SVG propios (trazo 1.6) ---------- */
export type IconName =
  | "grid" | "cart" | "user" | "users" | "tag" | "box" | "truck" | "hammer"
  | "image" | "calc" | "link" | "server" | "close" | "plus" | "minus" | "check"
  | "copy" | "arrow" | "chev-r" | "search" | "star" | "clock" | "doc" | "pulse"
  | "shield" | "card" | "menu" | "alert" | "eye" | "spark" | "back";

const P: Record<IconName, ReactNode> = {
  grid: <><rect x="3" y="3" width="7" height="7" /><rect x="14" y="3" width="7" height="7" /><rect x="3" y="14" width="7" height="7" /><rect x="14" y="14" width="7" height="7" /></>,
  cart: <><circle cx="9" cy="20" r="1.4" /><circle cx="17" cy="20" r="1.4" /><path d="M3 4h2.4l2.2 11h10l2.2-8H7" /></>,
  user: <><circle cx="12" cy="8" r="3.4" /><path d="M5 20c.8-3.6 3.6-5.4 7-5.4s6.2 1.8 7 5.4" /></>,
  users: <><circle cx="9" cy="9" r="3" /><path d="M3.5 19c.7-3 2.9-4.5 5.5-4.5s4.8 1.5 5.5 4.5" /><path d="M15.5 6.4a3 3 0 0 1 0 5.2M17.5 14.9c1.6.7 2.7 2 3 4.1" /></>,
  tag: <><path d="M3 3h8l10 10-8 8L3 11z" /><circle cx="8" cy="8" r="1.4" /></>,
  box: <><path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5z" /><path d="M3 7.5 12 12l9-4.5M12 12v9" /></>,
  truck: <><path d="M2 5h12v11H2zM14 9h4.5L21.5 13v3H14" /><circle cx="6.5" cy="17.5" r="1.6" /><circle cx="17" cy="17.5" r="1.6" /></>,
  hammer: <><path d="M3.5 20.5 12 12" /><path d="M10 4.5 14.5 3l6.5 6.5-1.5 4.5L15 15.5 10.5 11z" /><path d="M10.5 11 8 8.5" /></>,
  image: <><rect x="3" y="4" width="18" height="16" /><circle cx="9" cy="10" r="1.6" /><path d="M21 16l-5.5-5.5L8 18" /></>,
  calc: <><rect x="5" y="3" width="14" height="18" /><path d="M8 7h8M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h4" /></>,
  link: <><path d="M10 14a4.5 4.5 0 0 0 6.4.4l2.6-2.6a4.5 4.5 0 0 0-6.4-6.4L11.4 6.6" /><path d="M14 10a4.5 4.5 0 0 0-6.4-.4L5 12.2a4.5 4.5 0 0 0 6.4 6.4l1.2-1.2" /></>,
  server: <><rect x="3" y="4" width="18" height="7" /><rect x="3" y="13" width="18" height="7" /><path d="M7 7.5h.01M7 16.5h.01M11 7.5h3M11 16.5h3" /></>,
  close: <path d="M5 5l14 14M19 5L5 19" />,
  plus: <path d="M12 5v14M5 12h14" />,
  minus: <path d="M5 12h14" />,
  check: <path d="M4.5 12.5 10 18 19.5 6.5" />,
  copy: <><rect x="9" y="9" width="12" height="12" /><path d="M5 15H4a1.5 1.5 0 0 1-1.5-1.5v-9A1.5 1.5 0 0 1 4 3h9A1.5 1.5 0 0 1 14.5 4.5V5" /></>,
  arrow: <path d="M4 12h15M13 6l6 6-6 6" />,
  "chev-r": <path d="M9 5l7 7-7 7" />,
  search: <><circle cx="10.5" cy="10.5" r="6.5" /><path d="M15.5 15.5 21 21" /></>,
  star: <path d="M12 3.5 14.5 9l6 .6-4.5 4 1.3 5.9L12 16.4 6.7 19.5 8 13.6 3.5 9.6l6-.6z" />,
  clock: <><circle cx="12" cy="12" r="8.5" /><path d="M12 7v5.2l3.4 2" /></>,
  doc: <><path d="M6 2.5h8L20 8v13.5H6z" /><path d="M14 2.5V8h6M9.5 13h5M9.5 16.5h5" /></>,
  pulse: <path d="M2.5 12h4l2.5-7 4.5 14 2.5-7h5.5" />,
  shield: <><path d="M12 3 5 5.5v6c0 4.4 3 7.6 7 9.5 4-1.9 7-5.1 7-9.5v-6z" /><path d="M8.8 12l2.3 2.3 4.2-4.6" /></>,
  card: <><rect x="2.5" y="5" width="19" height="14" /><path d="M2.5 10h19M6.5 15h4" /></>,
  menu: <path d="M3.5 6.5h17M3.5 12h17M3.5 17.5h17" />,
  alert: <><path d="M12 3 1.8 20.5h20.4z" /><path d="M12 9.5V14M12 17.2h.01" /></>,
  eye: <><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z" /><circle cx="12" cy="12" r="2.8" /></>,
  spark: <path d="M12 2.5 14 9.5 21 12l-7 2.5-2 7-2-7L3 12l7-2.5z" />,
  back: <path d="M20 12H5M11 6l-6 6 6 6" />,
};

export function I({ n, s = 18, className = "" }: { n: IconName; s?: number; className?: string }) {
  return (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor"
      strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className={className} aria-hidden="true">
      {P[n]}
    </svg>
  );
}

/* ---------- Reveal en scroll ---------- */
export function Reveal({ children, delay = 0, className = "" }: { children: ReactNode; delay?: number; className?: string }) {
  const ref = useRef<HTMLDivElement>(null);
  const [on, setOn] = useState(false);
  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const io = new IntersectionObserver(
      ([e]) => { if (e.isIntersecting) { setOn(true); io.disconnect(); } },
      { threshold: 0.1, rootMargin: "0px 0px -40px 0px" },
    );
    io.observe(el);
    return () => io.disconnect();
  }, []);
  return (
    <div ref={ref} style={{ transitionDelay: `${delay}ms` }} className={`reveal ${on ? "reveal-on" : ""} ${className}`}>
      {children}
    </div>
  );
}

/* ---------- Botón copiar ---------- */
export function CopyBtn({ text, label = "Copiar", className = "" }: { text: string; label?: string; className?: string }) {
  const [ok, setOk] = useState(false);
  const copy = async () => {
    try {
      await navigator.clipboard.writeText(text);
    } catch {
      const t = document.createElement("textarea");
      t.value = text;
      document.body.appendChild(t);
      t.select();
      document.execCommand("copy");
      t.remove();
    }
    setOk(true);
    setTimeout(() => setOk(false), 1600);
  };
  return (
    <button onClick={copy}
      className={`inline-flex items-center gap-1.5 text-[12px] font-semibold transition-colors ${ok ? "text-ok" : "hover:text-ink"} ${className}`}>
      <I n={ok ? "check" : "copy"} s={14} />
      {ok ? "Copiado" : label}
    </button>
  );
}

/* ---------- Modal ---------- */
export function Modal({ open, onClose, children, w = "max-w-lg" }: { open: boolean; onClose: () => void; children: ReactNode; w?: string }) {
  useEffect(() => {
    if (!open) return;
    const f = (e: KeyboardEvent) => e.key === "Escape" && onClose();
    window.addEventListener("keydown", f);
    document.body.style.overflow = "hidden";
    return () => { window.removeEventListener("keydown", f); document.body.style.overflow = ""; };
  }, [open, onClose]);
  if (!open) return null;
  return (
    <div className="fixed inset-0 z-[90] flex items-end sm:items-center justify-center p-0 sm:p-6">
      <div className="absolute inset-0 bg-ink/50 backdrop-blur-[2px] fade-in" onClick={onClose} />
      <div className={`relative w-full ${w} bg-card border border-line shadow-[0_30px_80px_rgba(20,16,10,0.35)] slide-up max-h-[92vh] overflow-y-auto`}>
        {children}
      </div>
    </div>
  );
}

/* ---------- Bloque de código con copiar ---------- */
export function CodeBlock({ title, code }: { title: string; code: string }) {
  return (
    <div className="border border-coal2 bg-coal overflow-hidden">
      <div className="flex items-center justify-between px-4 py-2.5 border-b border-coal2">
        <div className="flex items-center gap-2">
          <span className="w-2 h-2 rounded-full bg-maroon pulse-maroon" />
          <span className="text-[11px] font-semibold tracking-[0.14em] uppercase text-cream/60">{title}</span>
        </div>
        <CopyBtn text={code} className="text-cream/70 hover:text-cream" />
      </div>
      <pre className="p-4 text-[12.5px] leading-relaxed text-[#c8e6c9] font-mono overflow-x-auto whitespace-pre">{code}</pre>
    </div>
  );
}
