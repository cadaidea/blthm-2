import { useMemo, useState } from "react";
import {
  BLOG_AUTORES, BLOG_ETIQUETAS, CMS_POSTS_SEED, PRODUCTS, SECCIONES_HOME_SEED, autorDe,
  loadBlogCategorias, loadCMS, loadCategoriasProducto, loadPaginas, loadSecciones, loadSite,
  minutosLectura, saveBlogCategorias, saveCMS, saveCategoriasProducto, savePaginas, saveSecciones,
  saveSite, type BlogCategoria, type CMSPost, type CategoriaProducto, type Pagina,
  type SeccionHome, type SiteConfig,
} from "../../data";
import { I, Modal, toast } from "../ui";
import { Card, Chip, SectionTitle, Stat, Td, Th, btnDark, btnGhost, inp } from "./pui";
import { StatusChip } from "./Panel";

/* ---------- switch reutilizable ---------- */
function Sw({ on, onClick, disabled }: { on: boolean; onClick: () => void; disabled?: boolean }) {
  return (
    <button onClick={onClick} disabled={disabled} aria-label="alternar"
      className={`relative w-10 h-[22px] shrink-0 transition-colors duration-200 ${on ? "bg-ok" : "bg-linedark"} ${disabled ? "opacity-40 cursor-not-allowed" : ""}`}>
      <span className={`absolute top-[3px] w-4 h-4 bg-card shadow transition-all duration-200 ${on ? "left-[21px]" : "left-[3px]"}`} />
    </button>
  );
}

/* ================= Sitio público ================= */
export function SitioPublico() {
  const [draft, setDraft] = useState<SiteConfig>(() => loadSite());
  const [saved, setSaved] = useState(false);
  const [lastPub, setLastPub] = useState("03:12");

  const destacado = PRODUCTS.find((p) => p.id === draft.destacadoId) ?? PRODUCTS[0];
  const sinPago = !draft.pagoLink && !draft.pagoDirecto;

  const publicar = () => {
    if (sinPago) return;
    const cfg = { ...draft, seoTitulo: draft.seoTitulo.trim() || draft.seoTitulo, seoDesc: draft.seoDesc.trim() };
    saveSite(cfg);
    toast("Configuración del sitio publicada en bletia.ec", "ok");
    setDraft(cfg);
    setLastPub(new Date().toLocaleTimeString("es-EC", { hour: "2-digit", minute: "2-digit" }));
    setSaved(true);
    setTimeout(() => setSaved(false), 2600);
  };

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Sitio público"
        sub="Lo que publica aquí aparece en bletia.ec al instante: sin redeploy, sin caída, con cambio atómico."
        right={
          <button onClick={publicar} disabled={sinPago} className={`${btnDark} ${saved ? "!bg-ok !border-ok" : ""}`}>
            <I n={saved ? "check" : "spark"} s={14} /> {saved ? "Publicado en bletia.ec" : "Publicar cambios"}
          </button>
        }
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Estado" value="Operativo" live sub={<span className="text-ok font-semibold">200 OK · 8 ms</span>} />
        <Stat label="Versión en línea" value="v2.4.1" sub="commit 654460d · hoy 03:12" />
        <Stat label="TLS / SSL" value="Let's Encrypt" sub="Renovación automática · 84 días" />
        <Stat label="Última publicación" value={lastPub} sub="Cambios atómicos vía symlink" />
      </div>

      <div className="grid xl:grid-cols-3 gap-6">
        <div className="xl:col-span-2 space-y-6">
          {/* Barra de anuncios */}
          <Card className="p-5">
            <div className="flex items-center justify-between gap-4">
              <div>
                <h3 className="font-bold text-[15px] tracking-tight">Barra de anuncios</h3>
                <p className="text-[12px] text-stone mt-0.5">Fija arriba del encabezado, en color de marca.</p>
              </div>
              <Sw on={draft.anuncioActivo} onClick={() => setDraft({ ...draft, anuncioActivo: !draft.anuncioActivo })} />
            </div>
            <input value={draft.anuncioTexto} disabled={!draft.anuncioActivo}
              onChange={(e) => setDraft({ ...draft, anuncioTexto: e.target.value })}
              className={`${inp} mt-4 ${!draft.anuncioActivo ? "opacity-50" : ""}`} placeholder="Mensaje del anuncio…" />
            {draft.anuncioActivo && (
              <div className="mt-3 bg-maroon text-cream text-[12px] font-medium px-4 py-2.5 flex items-center gap-2 fade-in">
                <I n="spark" s={13} /> {draft.anuncioTexto || "Escribe el mensaje…"}
                <span className="ml-auto opacity-60">vista previa</span>
              </div>
            )}
          </Card>

          {/* Producto destacado */}
          <Card className="p-5">
            <h3 className="font-bold text-[15px] tracking-tight">Producto destacado del hero</h3>
            <p className="text-[12px] text-stone mt-0.5">La pieza que abre la tienda y su tarjeta flotante.</p>
            <div className="grid sm:grid-cols-[1fr_auto] gap-4 mt-4 items-center">
              <select value={draft.destacadoId} onChange={(e) => setDraft({ ...draft, destacadoId: e.target.value })} className={inp}>
                {PRODUCTS.map((p) => <option key={p.id} value={p.id}>{p.name} — {p.sku}</option>)}
              </select>
              <div className="flex items-center gap-3 border border-line bg-paper2/50 p-2.5">
                <img src={destacado.img} alt={destacado.name} className="w-14 h-16 object-cover" />
                <div>
                  <p className="font-display font-medium text-[14px] leading-tight">{destacado.name}</p>
                  <p className="text-[11px] text-stone mt-0.5">{destacado.material}</p>
                  <p className="text-[12px] font-semibold mt-0.5 tnum">${destacado.price.toLocaleString("es-EC")}</p>
                </div>
              </div>
            </div>
          </Card>

          {/* Colecciones */}
          <Card className="p-5">
            <h3 className="font-bold text-[15px] tracking-tight">Colecciones visibles</h3>
            <p className="text-[12px] text-stone mt-0.5">Apaga una categoría y desaparece de la tienda y de sus filtros.</p>
            <div className="grid sm:grid-cols-2 gap-3 mt-4">
              {Object.keys(draft.colecciones).map((c) => {
                const n = PRODUCTS.filter((p) => p.category === c).length;
                return (
                  <div key={c} className="flex items-center justify-between border border-line px-3.5 py-3">
                    <div>
                      <p className="text-[13px] font-semibold">{c}</p>
                      <p className="text-[11px] text-stone">{n} piezas</p>
                    </div>
                    <Sw on={draft.colecciones[c] !== false}
                      onClick={() => setDraft({ ...draft, colecciones: { ...draft.colecciones, [c]: !(draft.colecciones[c] !== false) } })} />
                  </div>
                );
              })}
            </div>
          </Card>
        </div>

        <div className="space-y-6">
          {/* SEO */}
          <Card className="p-5">
            <h3 className="font-bold text-[15px] tracking-tight">SEO & metadatos</h3>
            <p className="text-[12px] text-stone mt-0.5">Cómo aparece bletia.ec en buscadores.</p>
            <label className="block mt-4">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Título</span>
              <input value={draft.seoTitulo} onChange={(e) => setDraft({ ...draft, seoTitulo: e.target.value })} className={inp} />
            </label>
            <label className="block mt-3">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Descripción</span>
              <textarea value={draft.seoDesc} onChange={(e) => setDraft({ ...draft, seoDesc: e.target.value })} rows={3} className={`${inp} resize-none`} />
            </label>
            <div className="mt-3 border border-line bg-card p-3">
              <p className="text-[12.5px] font-semibold text-[#1a0dab] truncate">{draft.seoTitulo}</p>
              <p className="text-[11px] text-[#006621]">bletia.ec</p>
              <p className="text-[11.5px] text-stone line-clamp-2">{draft.seoDesc}</p>
            </div>
          </Card>

          {/* Pagos */}
          <Card className="p-5">
            <h3 className="font-bold text-[15px] tracking-tight">Métodos de pago</h3>
            <p className="text-[12px] text-stone mt-0.5">Procesados por PayPhone. Debe quedar al menos uno activo.</p>
            <div className="space-y-3 mt-4">
              <div className="flex items-center justify-between border border-line px-3.5 py-3">
                <div>
                  <p className="text-[13px] font-semibold flex items-center gap-1.5"><I n="link" s={13} className="text-stone" /> Link de un solo uso</p>
                  <p className="text-[11px] text-stone">Llega por WhatsApp · expira en 24 h</p>
                </div>
                <Sw on={draft.pagoLink} onClick={() => setDraft({ ...draft, pagoLink: !draft.pagoLink })} disabled={!draft.pagoLink && !draft.pagoDirecto} />
              </div>
              <div className="flex items-center justify-between border border-line px-3.5 py-3">
                <div>
                  <p className="text-[13px] font-semibold flex items-center gap-1.5"><I n="card" s={13} className="text-stone" /> Pago directo en la web</p>
                  <p className="text-[11px] text-stone">Tarjeta · con diferido</p>
                </div>
                <Sw on={draft.pagoDirecto} onClick={() => setDraft({ ...draft, pagoDirecto: !draft.pagoDirecto })} disabled={!draft.pagoLink && !draft.pagoDirecto} />
              </div>
            </div>
            {sinPago && (
              <p className="mt-3 text-[12px] font-medium text-warn flex items-center gap-1.5">
                <I n="alert" s={13} /> Activa al menos un método: la tienda no puede quedar sin cobrar.
              </p>
            )}
          </Card>

          <Card className="p-4 bg-coal border-coal text-cream">
            <p className="text-[11px] text-cream/60 leading-relaxed flex items-start gap-2">
              <I n="shield" s={14} className="text-ok shrink-0 mt-0.5" />
              Publicar es un cambio atómico: nginx apunta al nuevo estático en 1 segundo, los visitantes en curso no notan nada y puedes revertir igual de rápido.
            </p>
          </Card>
        </div>
      </div>
    </div>
  );
}

/* ---------- Editor de páginas del sitio (Políticas / Contacto / Nosotros) ---------- */
function PaginasEditor() {
  const [pags, setPags] = useState<Pagina[]>(() => loadPaginas());
  const [sel, setSel] = useState(pags[0]?.slug || "politicas");
  const [saved, setSaved] = useState(false);
  const actual = pags.find((p) => p.slug === sel);

  const editar = (cuerpo: string) => setPags((ps) => ps.map((p) => (p.slug === sel ? { ...p, cuerpo } : p)));
  const guardar = () => {
    savePaginas(pags);
    setSaved(true);
    toast(`Página «${actual?.titulo}» publicada`, "ok");
    setTimeout(() => setSaved(false), 1800);
  };

  return (
    <Card className="p-5 sm:p-6">
      <div className="flex flex-wrap items-center justify-between gap-3 mb-1">
        <div>
          <h3 className="font-bold text-[15px] tracking-tight flex items-center gap-2">
            <I n="doc" s={16} className="text-stone" /> Páginas del sitio
          </h3>
          <p className="text-[12px] text-stone mt-0.5">
            Políticas, Contacto y Nosotros. Se publican al instante y se enlazan desde el pie de página de bletia.ec.
          </p>
        </div>
        <button onClick={guardar} className={btnDark}>
          <I n={saved ? "check" : "doc"} s={14} /> {saved ? "Publicado" : "Publicar página"}
        </button>
      </div>
      <div className="flex flex-wrap gap-2 mt-4">
        {pags.map((p) => (
          <button key={p.slug} onClick={() => setSel(p.slug)}
            className={`px-3.5 py-2 text-[12px] font-semibold border transition-colors ${sel === p.slug ? "bg-ink text-paper border-ink" : "border-linedark text-ink2 hover:border-ink"}`}>
            {p.titulo}
          </button>
        ))}
      </div>
      {actual && (
        <div className="mt-4">
          <p className="text-[11px] text-stone mb-1.5">URL: <code className="font-mono">bletia.ec/#/pagina/{actual.slug}</code></p>
          <textarea value={actual.cuerpo} onChange={(e) => editar(e.target.value)} rows={6}
            className={`${inp} resize-y leading-relaxed`} />
        </div>
      )}
    </Card>
  );
}

/* ================= Contenido web · CMS ================= */
const TAGS = ["Materia", "Taller", "Servicio", "Proceso"];

export function CMS() {
  const [posts, setPosts] = useState<CMSPost[]>(() => loadCMS());
  const [editing, setEditing] = useState<CMSPost | null>(null);
  const [confirmDel, setConfirmDel] = useState<string | null>(null);

  /* menús del sitio público (footer + navegación) */
  const [menus, setMenus] = useState(() => loadSite().menus);
  const [menusSaved, setMenusSaved] = useState(false);
  const editMenu = (grupo: "tienda" | "empresa", i: number, campo: "label" | "url", valor: string) =>
    setMenus((m) => ({ ...m, [grupo]: m[grupo].map((it, x) => (x === i ? { ...it, [campo]: valor } : it)) }));
  const moverMenu = (grupo: "tienda" | "empresa", i: number, dir: -1 | 1) =>
    setMenus((m) => {
      const arr = [...m[grupo]];
      const j = i + dir;
      if (j < 0 || j >= arr.length) return m;
      [arr[i], arr[j]] = [arr[j], arr[i]];
      return { ...m, [grupo]: arr };
    });
  const quitarMenu = (grupo: "tienda" | "empresa", i: number) =>
    setMenus((m) => ({ ...m, [grupo]: m[grupo].filter((_, x) => x !== i) }));
  const agregarMenu = (grupo: "tienda" | "empresa") =>
    setMenus((m) => ({ ...m, [grupo]: [...m[grupo], { label: "Nuevo enlace", url: "#" }] }));
  const guardarMenus = () => {
    saveSite({ ...loadSite(), menus });
    toast("Menús del pie de página actualizados", "ok");
    setMenusSaved(true);
    setTimeout(() => setMenusSaved(false), 1800);
  };

  const publicados = posts.filter((p) => p.estado === "Publicado").length;
  const borradores = posts.length - publicados;

  const nextNum = useMemo(() => {
    const max = posts.reduce((m, p) => Math.max(m, parseInt(p.num.replace(/\D/g, "")) || 0), 0);
    return `N° ${max + 1}`;
  }, [posts]);

  const persist = (next: CMSPost[]) => { setPosts(next); saveCMS(next); };

  const nuevo = () =>
    setEditing({
      id: `post-${Date.now()}`, num: nextNum,
      fecha: new Date().toLocaleDateString("es-EC", { day: "2-digit", month: "short", year: "numeric" }),
      titulo: "", tag: "Taller", cuerpo: "", estado: "Borrador",
    });

  const guardar = (estado: "Borrador" | "Publicado") => {
    if (!editing || editing.titulo.trim().length < 4) return;
    const p = { ...editing, estado };
    persist(posts.some((x) => x.id === p.id) ? posts.map((x) => (x.id === p.id ? p : x)) : [p, ...posts]);
    toast(estado === "Publicado" ? `«${p.titulo}» publicado en la tienda` : `«${p.titulo}» guardado como borrador`, estado === "Publicado" ? "ok" : "info");
    setEditing(null);
  };

  const toggleEstado = (id: string) => {
    const target = posts.find((p) => p.id === id);
    persist(posts.map((p) => (p.id === id ? { ...p, estado: p.estado === "Publicado" ? "Borrador" : "Publicado" } : p)));
    if (target) toast(target.estado === "Publicado" ? `«${target.titulo}» pasó a borrador` : `«${target.titulo}» publicado`, target.estado === "Publicado" ? "warn" : "ok");
  };

  const eliminar = (id: string) => { persist(posts.filter((p) => p.id !== id)); setConfirmDel(null); toast("Entrada eliminada", "bad"); };

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Contenido web · CMS"
        sub="El Diario de taller de bletia.ec se escribe aquí. Publicar es instantáneo: la tienda lo muestra al recargar."
        right={<button onClick={nuevo} className={btnDark}><I n="plus" s={14} /> Nueva entrada</button>}
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Publicadas" value={publicados} sub={<span className="text-ok font-semibold">Visibles en la tienda</span>} />
        <Stat label="Borradores" value={borradores} sub="Solo los ve el panel" />
        <Stat label="Próximo número" value={nextNum} sub="Serie del Diario" />
        <Stat label="Canal" value="Diario de taller" sub="bletia.ec/#diario" />
      </div>

      {/* categorías (blog + producto) */}
      <CategoriasEditor />

      {/* menús del sitio */}
      <Card className="p-5 sm:p-6">
        <div className="flex flex-wrap items-center justify-between gap-3 mb-1">
          <div>
            <h3 className="font-bold text-[15px] tracking-tight flex items-center gap-2">
              <I n="menu" s={16} className="text-stone" /> Menús del sitio
            </h3>
            <p className="text-[12px] text-stone mt-0.5">
              Cambia los enlaces del pie de página de bletia.ec como desees. Usa anclas internas (<code className="font-mono">#coleccion</code>, <code className="font-mono">#diario</code>…) o URLs externas.
            </p>
          </div>
          <button onClick={guardarMenus} className={btnDark}>
            <I n={menusSaved ? "check" : "doc"} s={14} /> {menusSaved ? "Guardado" : "Publicar menús"}
          </button>
        </div>
        <div className="grid md:grid-cols-2 gap-6 mt-5">
          {(["tienda", "empresa"] as const).map((grupo) => (
            <div key={grupo}>
              <div className="flex items-center justify-between mb-2.5">
                <p className="text-[10.5px] font-bold tracking-[0.18em] uppercase text-stone">
                  Columna «{grupo === "tienda" ? "Tienda" : "Empresa"}»
                </p>
                <button onClick={() => agregarMenu(grupo)}
                  className="text-[11.5px] font-semibold text-maroon hover:text-maroon2 flex items-center gap-1">
                  <I n="plus" s={12} /> Añadir
                </button>
              </div>
              <div className="space-y-2">
                {menus[grupo].map((it, i) => (
                  <div key={i} className="flex items-center gap-2 fade-in">
                    <input value={it.label} onChange={(e) => editMenu(grupo, i, "label", e.target.value)}
                      className={`${inp} !py-2 flex-1 min-w-0`} placeholder="Texto" />
                    <input value={it.url} onChange={(e) => editMenu(grupo, i, "url", e.target.value)}
                      className={`${inp} !py-2 w-[130px] font-mono text-[12px]`} placeholder="#ancla o url" />
                    <div className="flex flex-col shrink-0">
                      <button onClick={() => moverMenu(grupo, i, -1)} disabled={i === 0}
                        className="text-stone hover:text-ink disabled:opacity-25 leading-none p-0.5" aria-label="Subir">
                        <I n="chev-r" s={11} className="-rotate-90" />
                      </button>
                      <button onClick={() => moverMenu(grupo, i, 1)} disabled={i === menus[grupo].length - 1}
                        className="text-stone hover:text-ink disabled:opacity-25 leading-none p-0.5" aria-label="Bajar">
                        <I n="chev-r" s={11} className="rotate-90" />
                      </button>
                    </div>
                    <button onClick={() => quitarMenu(grupo, i)}
                      className="text-stone hover:text-bad transition-colors shrink-0 p-1" aria-label="Eliminar">
                      <I n="close" s={13} />
                    </button>
                  </div>
                ))}
                {menus[grupo].length === 0 && (
                  <p className="text-[12px] text-stone border border-dashed border-linedark px-3 py-4 text-center">
                    Sin enlaces. Añade el primero.
                  </p>
                )}
              </div>
            </div>
          ))}
        </div>
      </Card>

      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[760px]">
            <thead><tr><Th>N°</Th><Th>Título</Th><Th>Categoría</Th><Th>Autor</Th><Th>Lectura</Th><Th>Fecha</Th><Th>Estado</Th><Th>Acciones</Th></tr></thead>
            <tbody>
              {posts.map((p) => {
                const au = autorDe(p.autor);
                return (
                <tr key={p.id} className="hover:bg-paper2/50 transition-colors fade-in">
                  <Td className="font-mono text-[12px] font-semibold text-maroon whitespace-nowrap">{p.num}</Td>
                  <Td className="max-w-[300px]">
                    <span className="font-medium block leading-snug">{p.titulo}</span>
                    {p.etiquetas && p.etiquetas.length > 0 && (
                      <span className="text-[10.5px] text-stone">{p.etiquetas.map((e) => `#${e}`).join(" ")}</span>
                    )}
                  </Td>
                  <Td><Chip tone="neutral">{p.tag}</Chip></Td>
                  <Td className="text-[12px] whitespace-nowrap">{au ? au.nombre : <span className="text-stone">—</span>}</Td>
                  <Td className="text-[12px] text-stone tnum whitespace-nowrap">{minutosLectura(p.cuerpo)} min</Td>
                  <Td className="whitespace-nowrap text-stone">{p.fecha}</Td>
                  <Td><StatusChip s={p.estado} /></Td>
                  <Td>
                    <div className="flex items-center gap-1.5">
                      <button onClick={() => setEditing(p)} title="Editar"
                        className="p-2 border border-linedark hover:bg-ink hover:text-paper hover:border-ink transition-colors"><I n="doc" s={13} /></button>
                      <button onClick={() => toggleEstado(p.id)} title={p.estado === "Publicado" ? "Pasar a borrador" : "Publicar"}
                        className={`p-2 border transition-colors ${p.estado === "Publicado" ? "border-linedark text-stone hover:bg-warnbg hover:text-warn hover:border-warn/40" : "border-ok/40 text-ok hover:bg-ok hover:text-paper"}`}>
                        <I n={p.estado === "Publicado" ? "eye" : "spark"} s={13} />
                      </button>
                      <button onClick={() => setConfirmDel(p.id)} title="Eliminar"
                        className="p-2 border border-linedark text-bad hover:bg-bad hover:text-paper hover:border-bad transition-colors"><I n="close" s={13} /></button>
                    </div>
                  </Td>
                </tr>
                );
              })}
            </tbody>
          </table>
        </div>
        <div className="px-4 py-3 border-t border-line text-[11.5px] text-stone flex items-center gap-2">
          <I n="doc" s={13} />
          Las entradas publicadas reemplazan a la semilla ({CMS_POSTS_SEED.length} originales). Todo queda persistido y sobrevive recargas.
        </div>
      </Card>

      {/* Editor */}
      <Modal open={!!editing} onClose={() => setEditing(null)} w="max-w-2xl">
        {editing && (
          <div className="p-6 sm:p-8">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-[10.5px] font-bold tracking-[0.18em] uppercase text-maroon">{editing.num} · Diario de taller</p>
                <h3 className="font-bold text-[18px] mt-1">{posts.some((x) => x.id === editing.id) ? "Editar entrada" : "Nueva entrada"}</h3>
              </div>
              <button onClick={() => setEditing(null)} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={16} /></button>
            </div>
            <div className="grid sm:grid-cols-2 gap-4 mt-5">
              <label className="block sm:col-span-2">
                <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Título</span>
                <input value={editing.titulo} onChange={(e) => setEditing({ ...editing, titulo: e.target.value })}
                  className={inp} placeholder="Ej. El aceite de linaza se aplica tibio, nunca frío" />
              </label>
              <label className="block">
                <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Categoría</span>
                <select value={editing.tag} onChange={(e) => setEditing({ ...editing, tag: e.target.value })} className={inp}>
                  {TAGS.map((t) => <option key={t}>{t}</option>)}
                </select>
              </label>
              <label className="block">
                <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Fecha</span>
                <input value={editing.fecha} onChange={(e) => setEditing({ ...editing, fecha: e.target.value })} className={inp} placeholder="08 feb 2026" />
              </label>
              <label className="block">
                <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Autor</span>
                <select value={editing.autor || ""} onChange={(e) => setEditing({ ...editing, autor: e.target.value || undefined })} className={inp}>
                  <option value="">— sin autor —</option>
                  {BLOG_AUTORES.map((a) => <option key={a.id} value={a.id}>{a.nombre}</option>)}
                </select>
              </label>
              <div className="block sm:col-span-2">
                <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Etiquetas (blog)</span>
                <div className="flex flex-wrap gap-1.5">
                  {BLOG_ETIQUETAS.map((et) => {
                    const on = (editing.etiquetas || []).includes(et);
                    return (
                      <button key={et} type="button"
                        onClick={() => setEditing({ ...editing, etiquetas: on ? (editing.etiquetas || []).filter((x) => x !== et) : [...(editing.etiquetas || []), et] })}
                        className={`text-[11.5px] font-semibold border px-2.5 py-1.5 transition-colors ${on ? "bg-ink text-paper border-ink" : "border-linedark text-ink2 hover:border-ink"}`}>
                        #{et}
                      </button>
                    );
                  })}
                </div>
              </div>
              <label className="block sm:col-span-2">
                <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Cuerpo</span>
                <textarea value={editing.cuerpo} onChange={(e) => setEditing({ ...editing, cuerpo: e.target.value })}
                  rows={4} className={`${inp} resize-none`} placeholder="El texto breve que se lee en la portada…" />
              </label>
            </div>
            {editing.titulo.trim().length > 0 && editing.titulo.trim().length < 4 && (
              <p className="text-[12px] text-bad mt-3 flex items-center gap-1.5"><I n="alert" s={13} /> El título necesita al menos 4 caracteres.</p>
            )}
            <div className="flex flex-wrap gap-2.5 mt-6">
              <button onClick={() => guardar("Borrador")} className={btnGhost}>
                <I n="doc" s={14} /> Guardar borrador
              </button>
              <button onClick={() => guardar("Publicado")} className={`${btnDark} ml-auto`}>
                <I n="spark" s={14} /> Publicar en la tienda
              </button>
            </div>
          </div>
        )}
      </Modal>

      {/* Confirmar borrado */}
      <Modal open={!!confirmDel} onClose={() => setConfirmDel(null)}>
        <div className="p-6 sm:p-8">
          <h3 className="font-bold text-[17px]">¿Eliminar esta entrada?</h3>
          <p className="text-[13px] text-stone mt-2">Desaparece de la tienda al instante. Esta acción no se puede deshacer.</p>
          <div className="flex gap-2.5 mt-6">
            <button onClick={() => setConfirmDel(null)} className={btnGhost}>Conservar</button>
            <button onClick={() => confirmDel && eliminar(confirmDel)} className={`${btnDark} !bg-bad !border-bad`}>
              <I n="close" s={14} /> Eliminar definitivamente
            </button>
          </div>
        </div>
      </Modal>
    </div>
  );
}

/* ================= Portada · Home (composición de la página de inicio) ================= */
const TIPO_LABEL: Record<SeccionHome["tipo"], string> = {
  hero: "Apertura", coleccion: "Colección", taller: "Taller",
  servicios: "Servicios", diario: "Diario", custom: "Sección libre",
};
const TIPO_BG: Record<SeccionHome["tipo"], string> = {
  hero: "bg-paper", coleccion: "bg-paper2", taller: "bg-coal",
  servicios: "bg-paper", diario: "bg-paper2", custom: "bg-paper2",
};

export function EditorHome() {
  const [secs, setSecs] = useState<SeccionHome[]>(() => loadSecciones());
  const [saved, setSaved] = useState(false);

  const upd = (id: string, patch: Partial<SeccionHome>) =>
    setSecs((l) => l.map((s) => (s.id === id ? { ...s, ...patch } : s)));
  const mover = (i: number, dir: -1 | 1) =>
    setSecs((l) => {
      const j = i + dir;
      if (j < 0 || j >= l.length) return l;
      const c = [...l];
      [c[i], c[j]] = [c[j], c[i]];
      return c;
    });
  const quitar = (id: string) => setSecs((l) => l.filter((s) => s.id !== id));
  const agregar = () =>
    setSecs((l) => [...l, { id: `custom-${Date.now()}`, tipo: "custom", visible: true, titulo: "Nueva sección", texto: "Escribe aquí el contenido de tu sección.", oscuro: false }]);
  const restaurar = () => { setSecs(SECCIONES_HOME_SEED); toast("Se restauró la portada original", "info"); };
  const publicar = () => {
    saveSecciones(secs);
    setSaved(true);
    toast("Portada publicada en bletia.ec", "ok");
    setTimeout(() => setSaved(false), 2000);
  };

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Portada · bletia.ec"
        sub="Compones la página de inicio: ordena, oculta, edita textos y añade secciones. Se publica al instante."
        right={
          <div className="flex gap-2">
            <button onClick={restaurar} className={btnGhost}>Restaurar original</button>
            <button onClick={publicar} className={`${btnDark} ${saved ? "!bg-ok !border-ok" : ""}`}>
              <I n={saved ? "check" : "spark"} s={14} /> {saved ? "Publicada" : "Publicar portada"}
            </button>
          </div>
        }
      />

      {/* maqueta en vivo del orden */}
      <Card className="p-5">
        <p className="text-[10.5px] font-bold tracking-[0.18em] uppercase text-stone mb-3">Así se apila tu portada (de arriba a abajo)</p>
        <div className="flex items-stretch gap-1.5 overflow-x-auto pb-1">
          {secs.map((s) => (
            <div key={s.id}
              className={`shrink-0 w-[110px] border ${s.visible ? "border-linedark" : "border-dashed border-linedark opacity-40"} ${TIPO_BG[s.tipo] === "bg-coal" ? "bg-coal text-cream" : "bg-card"}`}>
              <div className={`h-9 ${TIPO_BG[s.tipo]}`} />
              <p className="text-[10px] font-semibold px-2 py-1.5 truncate">{TIPO_LABEL[s.tipo]}</p>
            </div>
          ))}
          <button onClick={agregar}
            className="shrink-0 w-[110px] border border-dashed border-linedark hover:border-maroon hover:text-maroon transition-colors flex flex-col items-center justify-center gap-1 text-stone">
            <I n="plus" s={16} />
            <span className="text-[10px] font-semibold">Añadir</span>
          </button>
        </div>
      </Card>

      {/* lista de secciones */}
      <div className="space-y-3">
        {secs.map((s, i) => (
          <Card key={s.id} className={`p-4 sm:p-5 transition-opacity ${s.visible ? "" : "opacity-50"}`}>
            <div className="flex items-start gap-3.5">
              <div className="flex flex-col items-center gap-1 pt-1 shrink-0">
                <button onClick={() => mover(i, -1)} disabled={i === 0} aria-label="Subir"
                  className="text-stone hover:text-ink disabled:opacity-25 transition-colors"><I n="chev-r" s={14} className="-rotate-90" /></button>
                <span className="text-[10px] font-bold text-stone tnum">{i + 1}</span>
                <button onClick={() => mover(i, 1)} disabled={i === secs.length - 1} aria-label="Bajar"
                  className="text-stone hover:text-ink disabled:opacity-25 transition-colors"><I n="chev-r" s={14} className="rotate-90" /></button>
              </div>

              <div className="flex-1 min-w-0">
                <div className="flex flex-wrap items-center gap-2.5 mb-2">
                  <Chip tone={s.tipo === "custom" ? "maroon" : "neutral"}>{TIPO_LABEL[s.tipo]}</Chip>
                  {s.tipo === "custom" && (
                    <button onClick={() => upd(s.id, { oscuro: !s.oscuro })}
                      className={`text-[10.5px] font-bold uppercase tracking-wider px-2 py-1 border transition-colors ${s.oscuro ? "bg-coal text-cream border-coal" : "border-linedark text-ink2"}`}>
                      {s.oscuro ? "Banda oscura" : "Banda clara"}
                    </button>
                  )}
                </div>
                <input value={s.titulo} onChange={(e) => upd(s.id, { titulo: e.target.value })}
                  className={`${inp} font-display !text-[16px]`} placeholder="Título de la sección" />
                {(s.tipo === "hero" || s.tipo === "taller" || s.tipo === "servicios" || s.tipo === "custom") && (
                  <textarea value={s.texto} onChange={(e) => upd(s.id, { texto: e.target.value })} rows={2}
                    className={`${inp} resize-y mt-2`} placeholder="Texto de la sección" />
                )}
              </div>

              <div className="flex flex-col items-end gap-2 shrink-0">
                <Sw on={s.visible} onClick={() => upd(s.id, { visible: !s.visible })} />
                <span className="text-[10px] font-semibold text-stone">{s.visible ? "Visible" : "Oculta"}</span>
                {s.tipo === "custom" && (
                  <button onClick={() => quitar(s.id)} className="text-[11px] font-semibold text-bad hover:underline mt-1">Eliminar</button>
                )}
              </div>
            </div>
          </Card>
        ))}
      </div>
    </div>
  );
}

/* ================= Categorías (blog + producto) ================= */
export function CategoriasEditor() {
  const [blog, setBlog] = useState<BlogCategoria[]>(() => loadBlogCategorias());
  const [prod, setProd] = useState<CategoriaProducto[]>(() => loadCategoriasProducto());
  const [nb, setNb] = useState("");
  const [npr, setNpr] = useState("");

  const addBlog = () => {
    const n = nb.trim();
    if (!n || blog.some((c) => c.nombre.toLowerCase() === n.toLowerCase())) return;
    const l = [...blog, { id: `bc${Date.now()}`, nombre: n }];
    setBlog(l); saveBlogCategorias(l); setNb("");
    toast(`Categoría de blog «${n}» creada`, "ok");
  };
  const delBlog = (id: string) => {
    const l = blog.filter((c) => c.id !== id);
    setBlog(l); saveBlogCategorias(l);
    toast("Categoría de blog eliminada", "warn");
  };
  const addProd = () => {
    const n = npr.trim();
    if (!n || prod.some((c) => c.nombre.toLowerCase() === n.toLowerCase())) return;
    const l = [...prod, { id: `cp${Date.now()}`, nombre: n, activa: true }];
    setProd(l); saveCategoriasProducto(l); setNpr("");
    toast(`Categoría de producto «${n}» creada`, "ok");
  };
  const delProd = (id: string) => {
    const l = prod.filter((c) => c.id !== id);
    setProd(l); saveCategoriasProducto(l);
    toast("Categoría de producto eliminada", "warn");
  };

  const Bloque = ({ titulo, ayuda, items, onAdd, onDel, val, setVal, addLabel }: {
    titulo: string; ayuda: string; items: { id: string; nombre: string }[];
    onAdd: () => void; onDel: (id: string) => void; val: string; setVal: (v: string) => void; addLabel: string;
  }) => (
    <Card className="p-5">
      <h3 className="font-bold text-[15px] tracking-tight">{titulo}</h3>
      <p className="text-[12px] text-stone mt-0.5">{ayuda}</p>
      <div className="flex flex-wrap gap-1.5 mt-4">
        {items.map((c) => (
          <span key={c.id} className="inline-flex items-center gap-1.5 border border-linedark px-2.5 py-1.5 text-[12px] font-medium fade-in">
            {c.nombre}
            <button onClick={() => onDel(c.id)} className="text-stone hover:text-bad transition-colors" aria-label={`Eliminar ${c.nombre}`}>
              <I n="close" s={11} />
            </button>
          </span>
        ))}
        {items.length === 0 && <span className="text-[12px] text-stone">Sin categorías.</span>}
      </div>
      <div className="flex gap-2 mt-4">
        <input value={val} onChange={(e) => setVal(e.target.value)} onKeyDown={(e) => e.key === "Enter" && onAdd()}
          className={inp} placeholder={addLabel} />
        <button onClick={onAdd} className={`${btnDark} !py-2.5 whitespace-nowrap`}><I n="plus" s={13} /> Añadir</button>
      </div>
    </Card>
  );

  return (
    <div className="grid md:grid-cols-2 gap-5">
      <Bloque titulo="Categorías del blog" ayuda="Agrupan los artículos del diario (Materia, Taller…)."
        items={blog} onAdd={addBlog} onDel={delBlog} val={nb} setVal={setNb} addLabel="Nueva categoría de blog" />
      <Bloque titulo="Categorías de producto" ayuda="Agrupan las piezas de la tienda y sus filtros."
        items={prod} onAdd={addProd} onDel={delProd} val={npr} setVal={setNpr} addLabel="Nueva categoría de producto" />
    </div>
  );
}
