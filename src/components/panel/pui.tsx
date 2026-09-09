import type { ReactNode } from "react";

type Tone = "ok" | "warn" | "bad" | "neutral" | "maroon";

const tones: Record<Tone, string> = {
  ok: "bg-okbg text-ok",
  warn: "bg-warnbg text-warn",
  bad: "bg-badbg text-bad",
  neutral: "bg-paper2 text-ink2",
  maroon: "bg-maroon/10 text-maroon",
};

export function Chip({ tone = "neutral", children, dot }: { tone?: Tone; children: ReactNode; dot?: boolean }) {
  return (
    <span className={`inline-flex items-center gap-1.5 px-2 py-1 text-[10.5px] font-bold tracking-[0.06em] uppercase whitespace-nowrap ${tones[tone]}`}>
      {dot && <span className="w-1.5 h-1.5 rounded-full bg-current" />}
      {children}
    </span>
  );
}

export function Card({ children, className = "" }: { children: ReactNode; className?: string }) {
  return <div className={`bg-card border border-line ${className}`}>{children}</div>;
}

export function Stat({ label, value, sub, live }: { label: string; value: ReactNode; sub?: ReactNode; live?: boolean }) {
  return (
    <Card className="p-5 hover:border-linedark transition-colors group">
      <p className="flex items-center gap-2 text-[10.5px] font-bold tracking-[0.16em] uppercase text-stone">
        {live && <span className="w-1.5 h-1.5 rounded-full bg-ok pulse-ok" />}
        {label}
      </p>
      <p className="font-display font-medium text-[28px] leading-none mt-3 tnum">{value}</p>
      {sub && <div className="text-[12px] text-stone mt-2.5">{sub}</div>}
    </Card>
  );
}

export function Th({ children, className = "" }: { children?: ReactNode; className?: string }) {
  return (
    <th className={`text-left text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone px-4 py-3 border-b border-line whitespace-nowrap ${className}`}>
      {children}
    </th>
  );
}

export function Td({ children, className = "" }: { children?: ReactNode; className?: string }) {
  return <td className={`px-4 py-3.5 border-b border-line/70 text-[13px] align-middle ${className}`}>{children}</td>;
}

export function Bar({ value, tone = "ok" }: { value: number; tone?: "ok" | "maroon" | "warn" }) {
  const c = tone === "maroon" ? "bg-maroon" : tone === "warn" ? "bg-warn" : "bg-ok";
  return (
    <div className="h-1.5 w-full bg-paper2 overflow-hidden">
      <div className={`h-full ${c} transition-all duration-700 ease-out`} style={{ width: `${Math.min(value, 100)}%` }} />
    </div>
  );
}

export function SectionTitle({ title, sub, right }: { title: string; sub?: string; right?: ReactNode }) {
  return (
    <div className="flex flex-wrap items-end justify-between gap-4 mb-6">
      <div>
        <h2 className="font-dash font-bold text-[20px] tracking-tight">{title}</h2>
        {sub && <p className="text-[13px] text-stone mt-1">{sub}</p>}
      </div>
      {right}
    </div>
  );
}

export const btn = "inline-flex items-center justify-center gap-2 text-[12.5px] font-semibold px-4 py-2.5 transition-colors cursor-pointer";
export const btnDark = `${btn} bg-ink text-paper hover:bg-maroon`;
export const btnGhost = `${btn} border border-linedark text-ink2 hover:border-ink hover:text-ink`;
export const inp = "w-full border border-linedark bg-card px-3 py-2.5 text-[13px] outline-none focus:border-ink transition-colors placeholder:text-stone/70";
