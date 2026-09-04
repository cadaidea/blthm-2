import { useState } from "react";
import { ASSETS, PHASES, SUPPLIERS, WORK_ORDERS, type Asset, type WorkOrder } from "../../data";
import { CopyBtn, I, Modal } from "../ui";
import { Bar, Card, Chip, SectionTitle, Stat, Td, Th, btnDark, btnGhost } from "./pui";
import { StatusChip } from "./Panel";

/* ================= Proveedores ================= */
export function Proveedores() {
  const [tab, setTab] = useState<"Muebles" | "Transporte">("Muebles");
  const shown = SUPPLIERS.filter((s) => s.type === tab);

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Proveedores"
        sub="Muebles de curaduría y red de transporte auditada. SLA medido por entrega, no por promesa."
        right={
          <div className="flex gap-1.5">
            {(["Muebles", "Transporte"] as const).map((t) => (
              <button key={t} onClick={() => setTab(t)}
                className={`px-4 py-2 text-[12px] font-semibold border transition-colors flex items-center gap-2 ${tab === t ? "bg-ink text-paper border-ink" : "border-linedark text-ink2 hover:border-ink"}`}>
                <I n={t === "Muebles" ? "box" : "truck"} s={14} /> {t}
              </button>
            ))}
          </div>
        }
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Proveedores activos" value={SUPPLIERS.length} sub={`${SUPPLIERS.filter((s) => s.type === "Muebles").length} muebles · ${SUPPLIERS.filter((s) => s.type === "Transporte").length} transporte`} />
        <Stat label="Órdenes en curso" value={SUPPLIERS.reduce((a, s) => a + s.active, 0)} sub="Con seguimiento semanal" />
        <Stat label="SLA promedio" value="97,0%" sub={<span className="text-ok font-semibold">Últimos 90 días</span>} />
        <Stat label="Rating promedio" value="4,65 / 5" sub="Evaluación post-entrega" />
      </div>

      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[860px]">
            <thead><tr><Th>Proveedor</Th><Th>Especialidad</Th><Th>Ciudad</Th><Th>Lead time</Th><Th>Rating</Th><Th>Órdenes</Th><Th>SLA</Th><Th>Contacto</Th></tr></thead>
            <tbody>
              {shown.map((s) => (
                <tr key={s.id} className="hover:bg-paper2/50 transition-colors fade-in">
                  <Td>
                    <div className="flex items-center gap-3">
                      <span className="w-9 h-9 bg-ink text-paper text-[11px] font-bold flex items-center justify-center shrink-0">
                        {s.name.split(" ").slice(0, 2).map((w) => w[0]).join("")}
                      </span>
                      <span className="font-medium">{s.name}</span>
                    </div>
                  </Td>
                  <Td className="text-ink2">{s.specialty}</Td>
                  <Td>{s.city}</Td>
                  <Td className="tnum">{s.lead}</Td>
                  <Td>
                    <span className="inline-flex items-center gap-1.5 font-semibold tnum">
                      <I n="star" s={13} className="text-warn" /> {s.rating.toFixed(1).replace(".", ",")}
                    </span>
                  </Td>
                  <Td className="tnum">{s.active}</Td>
                  <Td className="text-[12px] text-ink2">{s.sla}</Td>
                  <Td>
                    <span className="text-[12px] font-mono text-ink2">{s.contact}</span>
                  </Td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="px-4 py-3 border-t border-line text-[11.5px] text-stone flex items-center gap-2">
          <I n="truck" s={13} />
          Cada transporte asignado en el OMS aparece aquí con su SLA; si un proveedor baja de 94% se marca para revisión.
        </div>
      </Card>
    </div>
  );
}

/* ================= Taller ================= */
const MILESTONES = [30, 58, 82, 100];

export function Taller() {
  const [wos, setWos] = useState<WorkOrder[]>(WORK_ORDERS);

  const advance = (id: string) =>
    setWos((ws) =>
      ws.map((w) => {
        if (w.id !== id) return w;
        const phase = Math.min(w.phase + 1, PHASES.length - 1);
        return { ...w, phase, progress: MILESTONES[phase] };
      }),
    );

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Órdenes de fabricación"
        sub="Lo que nace en casa: series numeradas, madera certificada y avance visible para el cliente."
        right={<button className={btnDark}><I n="plus" s={14} /> Nueva orden</button>}
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Órdenes activas" value={wos.length} sub={`${wos.reduce((a, w) => a + w.qty, 0)} piezas en proceso`} />
        <Stat label="En corte / ensamble" value={wos.filter((w) => w.phase < 2).length} sub="Líneas 1 y 2 operativas" />
        <Stat label="Acabado + QC" value={wos.filter((w) => w.phase >= 2).length} sub="Tres manos de aceite" />
        <Stat label="Entrega a tiempo" value="96,8%" sub={<span className="text-ok font-semibold">Mejor mes del año</span>} />
      </div>

      <div className="grid lg:grid-cols-2 gap-5">
        {wos.map((w) => {
          const done = w.phase === PHASES.length - 1 && w.progress === 100;
          return (
            <Card key={w.id + w.phase + w.progress} className="p-5 fade-in hover:border-linedark transition-colors">
              <div className="flex items-start justify-between gap-4">
                <div className="min-w-0">
                  <p className="text-[10.5px] font-bold tracking-[0.16em] uppercase text-stone font-mono">{w.ref}</p>
                  <h3 className="font-bold text-[16px] mt-1 leading-tight">{w.piece}</h3>
                  <p className="text-[12px] text-stone mt-0.5 truncate">{w.order}</p>
                </div>
                {done ? <Chip tone="ok" dot>Lista</Chip> : <Chip tone="maroon" dot>{w.qty} pzas</Chip>}
              </div>

              {/* pipeline de fases */}
              <div className="grid grid-cols-4 gap-1.5 mt-5">
                {PHASES.map((p, i) => (
                  <div key={p}>
                    <div className={`h-1.5 ${i < w.phase ? "bg-ink" : i === w.phase ? (done ? "bg-ok" : "bg-maroon") : "bg-paper2"}`} />
                    <p className={`text-[10px] font-bold tracking-wide uppercase mt-1.5 ${i === w.phase ? "text-ink" : "text-stone/70"}`}>{p}</p>
                  </div>
                ))}
              </div>

              <div className="mt-4">
                <div className="flex justify-between text-[11.5px] mb-1.5">
                  <span className="text-stone">{w.artisan} · {w.wood}</span>
                  <span className="font-semibold tnum">{w.progress}%</span>
                </div>
                <Bar value={w.progress} tone={done ? "ok" : "maroon"} />
              </div>

              <div className="flex items-center justify-between mt-5 pt-4 border-t border-line">
                <span className="text-[12px] text-stone flex items-center gap-1.5">
                  <I n="clock" s={13} /> Entrega: <strong className="text-ink font-semibold">{w.due}</strong>
                </span>
                <button onClick={() => advance(w.id)} disabled={done}
                  className={`${done ? btnGhost + " opacity-40 cursor-default" : btnGhost} !py-2 text-[12px] hover:bg-ink hover:text-paper`}>
                  {done ? "Completada" : "Avanzar fase"} {!done && <I n="chev-r" s={12} />}
                </button>
              </div>
            </Card>
          );
        })}
      </div>

      <Card className="p-4 flex items-center gap-3 text-[12px] text-stone">
        <I n="hammer" s={15} className="text-maroon shrink-0" />
        Al completar «Control de calidad», el PIM actualiza el stock, el OMS notifica al cliente con su link privado y contabilidad registra el costo de la serie.
      </Card>
    </div>
  );
}

/* ================= DAM · Medios ================= */
export function DAM() {
  const [assets, setAssets] = useState<Asset[]>(ASSETS);
  const [filter, setFilter] = useState("Todo");
  const [uploading, setUploading] = useState(false);
  const [preview, setPreview] = useState<Asset | null>(null);

  const shown = filter === "Todo" ? assets : assets.filter((a) => a.kind === filter || a.status === filter);

  const simulateUpload = () => {
    setUploading(true);
    setTimeout(() => {
      setAssets((a) => [
        {
          id: `a${Date.now()}`, name: `nueva-campana_${a.length + 1}.jpg`, img: "img/detalle.jpg",
          kind: "Campaña", size: "3,3 MB", tags: ["nuevo", "web"], status: "En revisión", uses: 0, date: "hoy",
        },
        ...a,
      ]);
      setUploading(false);
    }, 1400);
  };

  const approve = (id: string) =>
    setAssets((a) => a.map((x) => (x.id === id ? { ...x, status: "Aprobado" } : x)));

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Activos digitales"
        sub="Fotos, renders y fichas en MinIO (S3 open source). Aprobación obligatoria antes de publicar."
        right={
          <button onClick={simulateUpload} disabled={uploading} className={btnDark}>
            <I n="plus" s={14} /> {uploading ? "Procesando…" : "Subir archivo"}
          </button>
        }
      />

      <div className="flex flex-wrap gap-1.5">
        {["Todo", "Fotografía", "Material", "Campaña", "Aprobado", "En revisión"].map((f) => (
          <button key={f} onClick={() => setFilter(f)}
            className={`px-3 py-1.5 text-[11.5px] font-semibold border transition-colors ${filter === f ? "bg-ink text-paper border-ink" : "border-linedark text-ink2 hover:border-ink"}`}>
            {f}
          </button>
        ))}
      </div>

      <div className="grid sm:grid-cols-2 xl:grid-cols-3 gap-5">
        {uploading && (
          <Card className="overflow-hidden fade-in">
            <div className="shimmer aspect-[4/3]" />
            <div className="p-3.5">
              <p className="text-[12.5px] font-semibold text-stone">Generando miniaturas…</p>
              <p className="text-[11px] text-stone mt-0.5">Worker DAM · cola Redis</p>
            </div>
          </Card>
        )}
        {shown.map((a) => (
          <Card key={a.id} className="overflow-hidden group fade-in hover:border-linedark transition-colors">
            <div className="relative aspect-[4/3] overflow-hidden bg-paper2 cursor-pointer" onClick={() => setPreview(a)}>
              <img src={a.img} alt={a.name} loading="lazy" className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-[1.04]" />
              <span className="absolute top-2.5 left-2.5"><StatusChip s={a.status} /></span>
              <button onClick={(e) => { e.stopPropagation(); approve(a.id); }}
                className={`absolute bottom-2.5 right-2.5 text-[11px] font-bold px-3 py-2 transition-all flex items-center gap-1.5 ${a.status === "En revisión" ? "bg-ink text-paper opacity-0 group-hover:opacity-100 hover:bg-ok" : "bg-card/95 text-ink2 hover:bg-ink hover:text-paper"}`}>
                <I n={a.status === "En revisión" ? "check" : "eye"} s={12} />
                {a.status === "En revisión" ? "Aprobar" : "Vista previa"}
              </button>
            </div>
            <div className="p-3.5">
              <div className="flex items-center justify-between gap-3">
                <p className="font-mono text-[11.5px] font-semibold truncate">{a.name}</p>
                <span className="text-[10.5px] text-stone shrink-0 tnum">{a.size}</span>
              </div>
              <div className="flex flex-wrap gap-1 mt-2.5">
                {a.tags.map((t) => (
                  <span key={t} className="text-[10px] font-bold uppercase tracking-wider bg-paper2 text-ink2 px-1.5 py-0.5">#{t}</span>
                ))}
              </div>
              <div className="flex items-center justify-between mt-3 pt-3 border-t border-line">
                <span className="text-[11px] text-stone">{a.uses} usos · {a.date}</span>
                <CopyBtn text={`https://cdn.bletia.ec/${a.name}`} label="URL" />
              </div>
            </div>
          </Card>
        ))}
      </div>

      {/* Vista previa del activo */}
      <Modal open={!!preview} onClose={() => setPreview(null)} w="max-w-3xl">
        {preview && (
          <div>
            <div className="flex items-center justify-between px-5 h-14 border-b border-line">
              <p className="font-mono text-[12.5px] font-semibold truncate">{preview.name}</p>
              <button onClick={() => setPreview(null)} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={16} /></button>
            </div>
            <div className="bg-coal">
              <img src={preview.img} alt={preview.name} className="w-full max-h-[52vh] object-contain" />
            </div>
            <div className="p-5 grid sm:grid-cols-[1fr_auto] gap-4 items-end">
              <div>
                <div className="flex flex-wrap gap-1.5 mb-2.5">
                  {preview.tags.map((t) => (
                    <span key={t} className="text-[10px] font-bold uppercase tracking-wider bg-paper2 text-ink2 px-1.5 py-0.5">#{t}</span>
                  ))}
                  <StatusChip s={preview.status} />
                </div>
                <p className="text-[12px] text-stone">
                  {preview.kind} · {preview.size} · {preview.uses} usos en tienda, catálogo y campañas · subido el {preview.date}
                </p>
              </div>
              <div className="flex gap-2.5">
                <CopyBtn text={`https://cdn.bletia.ec/${preview.name}`} label="Copiar URL CDN" className="border border-linedark px-3.5 py-2.5" />
                {preview.status === "En revisión" && (
                  <button onClick={() => { approve(preview.id); setPreview(null); }}
                    className="text-[12px] font-semibold px-3.5 py-2.5 bg-ink text-paper hover:bg-ok transition-colors flex items-center gap-1.5">
                    <I n="check" s={13} /> Aprobar
                  </button>
                )}
              </div>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}
