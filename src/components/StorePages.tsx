import { useEffect, useMemo, useState } from "react";
import {
  ATRIBUTOS, IMG, PRODUCTS, articuloUrl, autorDe, fmt, fmt2, loadCMS, loadPaginas, minutosLectura,
  nombreOpcion, slugDe, tagTexto, tagUrl, variantesDe, type Product,
} from "../data";
import { I } from "./ui";

/* ---------- carrito compartido (mismo localStorage que la home) ---------- */
function addToCart(id: string, qty = 1) {
  let cart: { id: string; qty: number }[] = [];
  try { cart = JSON.parse(localStorage.getItem("bletia-cart") || "[]"); } catch { /* vacío */ }
  const ex = cart.find((l) => l.id === id);
  cart = ex ? cart.map((l) => (l.id === id ? { ...l, qty: Math.min(l.qty + qty, 12) } : l)) : [...cart, { id, qty }];
  localStorage.setItem("bletia-cart", JSON.stringify(cart));
}
function cartCount(): number {
  try {
    return (JSON.parse(localStorage.getItem("bletia-cart") || "[]") as { qty: number }[]).reduce((a, l) => a + l.qty, 0);
  } catch { return 0; }
}

/* ---------- barra superior compartida ---------- */
function Barra({ titulo }: { titulo: string }) {
  const [count, setCount] = useState(cartCount());
  useEffect(() => { setCount(cartCount()); }, [titulo]);
  return (
    <header className="sticky top-0 z-40 bg-paper/90 backdrop-blur-md border-b border-line">
      <div className="max-w-[1200px] mx-auto px-5 sm:px-8 h-16 flex items-center justify-between gap-6">
        <a href="#/" className="font-display font-semibold tracking-[0.32em] text-[17px] leading-none">
          BLETIA<span className="text-maroon">.</span>
        </a>
        <p className="hidden md:block text-[12px] font-medium tracking-[0.14em] uppercase text-stone truncate max-w-[40%]">{titulo}</p>
        <div className="flex items-center gap-2">
          <a href="#/" className="text-[12.5px] font-semibold text-stone hover:text-ink transition-colors flex items-center gap-1.5">
            <I n="back" s={14} /> Tienda
          </a>
          <a href="#/" className="relative p-2.5 hover:bg-paper2 transition-colors" aria-label="Carrito">
            <I n="cart" s={19} />
            {count > 0 && (
              <span className="absolute -top-0.5 -right-0.5 min-w-[17px] h-[17px] px-1 bg-maroon text-cream text-[10px] font-bold flex items-center justify-center tnum">{count}</span>
            )}
          </a>
        </div>
      </div>
    </header>
  );
}

function Pie() {
  return (
    <footer className="mt-20 bg-ink text-cream">
      <div className="max-w-[1200px] mx-auto px-5 sm:px-8 py-10 flex flex-col sm:flex-row items-center justify-between gap-4">
        <p className="font-display font-semibold tracking-[0.32em]">BLETIA<span className="text-maroon">.</span></p>
        <p className="text-[12px] text-cream/50">Hecho en Cuenca, Ecuador · <a href="#/pagina/politicas" className="underline underline-offset-2 hover:text-cream">Políticas</a> · <a href="#/pagina/contacto" className="underline underline-offset-2 hover:text-cream">Contacto</a></p>
      </div>
    </footer>
  );
}

function NoEncontrado({ que }: { que: string }) {
  return (
    <div className="min-h-screen bg-paper flex flex-col">
      <Barra titulo="No encontrado" />
      <div className="flex-1 flex flex-col items-center justify-center text-center px-6 py-24">
        <p className="font-display font-medium text-[64px] leading-none text-linedark select-none">404</p>
        <h1 className="font-display font-medium text-4xl mt-5">Ese {que} no existe.</h1>
        <p className="text-stone text-[14px] mt-3 max-w-[40ch]">Puede que el enlace haya cambiado o la pieza ya no esté disponible.</p>
        <a href="#/" className="mt-8 inline-flex items-center gap-2 bg-ink text-paper px-6 py-3.5 text-[13px] font-semibold hover:bg-maroon transition-colors">
          <I n="back" s={14} /> Volver a la tienda
        </a>
      </div>
      <Pie />
    </div>
  );
}

/* ================= PÁGINA DE PRODUCTO ================= */
export function ProductoPage({ slug }: { slug: string }) {
  const p = PRODUCTS.find((x) => slugDe(x.slug || x.name) === slug);
  const [varSel, setVarSel] = useState<Record<string, string>>({});
  const [qty, setQty] = useState(1);
  const [added, setAdded] = useState(false);

  const vars = useMemo(() => (p ? variantesDe(p.id) : []), [p]);
  const attrsUsados = useMemo(
    () => ATRIBUTOS.filter((a) => vars.some((v) => v.opciones[a.id])),
    [vars],
  );

  if (!p) return <NoEncontrado que="producto" />;

  const matched = vars.find((v) =>
    attrsUsados.every((a) => v.opciones[a.id] && varSel[a.id] === v.opciones[a.id]),
  );
  const precio = matched ? matched.pvp : p.price;
  const relacionados = PRODUCTS.filter((x) => x.category === p.category && x.id !== p.id).slice(0, 3);

  const comprar = () => {
    addToCart(p.id, qty);
    setAdded(true);
    setTimeout(() => setAdded(false), 1600);
  };

  return (
    <div className="min-h-screen bg-paper flex flex-col">
      <Barra titulo={p.name} />
      <main className="flex-1 max-w-[1200px] mx-auto px-5 sm:px-8 py-10 w-full">
        <nav className="text-[12px] text-stone flex items-center gap-2 mb-8">
          <a href="#/" className="hover:text-ink">Inicio</a> <I n="chev-r" s={11} />
          <a href={`#/categoria/${slugDe(p.category)}`} className="hover:text-ink">{p.category}</a> <I n="chev-r" s={11} />
          <span className="text-ink font-medium">{p.name}</span>
        </nav>

        <div className="grid lg:grid-cols-2 gap-10 lg:gap-16">
          {/* galería */}
          <div className="space-y-3">
            <div className="relative aspect-[4/5] overflow-hidden bg-paper2">
              <img src={p.img} alt={p.name} className="w-full h-full object-cover" />
              {p.state === "En taller" && (
                <span className="absolute top-4 left-4 text-[10px] font-bold tracking-[0.16em] uppercase px-2.5 py-1.5 bg-card/95 text-warn">En fabricación</span>
              )}
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div className="aspect-[4/3] overflow-hidden bg-paper2"><img src={IMG.taller} alt="Proceso en taller" className="w-full h-full object-cover" /></div>
              <div className="aspect-[4/3] overflow-hidden bg-paper2"><img src={IMG.detalle} alt="Detalle de materiales" className="w-full h-full object-cover" /></div>
            </div>
          </div>

          {/* detalle */}
          <div>
            <p className="text-[10.5px] font-bold tracking-[0.18em] uppercase text-stone tnum">{p.sku} · {p.origin}</p>
            <h1 className="font-display font-medium text-[clamp(1.8rem,3vw,2.6rem)] leading-tight mt-2">{p.name}</h1>
            <p className="text-ink2 text-[13px] mt-1.5">{p.material}</p>

            <div className="mt-6">
              <p className="font-display font-medium text-[30px] tnum">{fmt(precio * qty)}</p>
              <p className="text-[11.5px] text-stone mt-0.5">
                IVA 15% incluido · base {fmt2((precio * qty) / 1.15)}
                {qty > 1 && <span> · {qty} × {fmt(precio)}</span>}
              </p>
              <p className={`text-[12px] font-semibold mt-2 ${p.stock > 3 ? "text-ok" : "text-warn"}`}>
                {p.state === "En taller" ? `En fabricación · entrega en ${p.lead}` : p.stock > 3 ? `En stock · ${p.stock} disponibles` : `Últimas ${p.stock} unidades`}
              </p>
            </div>

            {/* selector de variantes */}
            {attrsUsados.length > 0 && (
              <div className="mt-7 space-y-5">
                {attrsUsados.map((a) => {
                  const opciones = [...new Set(vars.map((v) => v.opciones[a.id]).filter(Boolean))] as string[];
                  return (
                    <div key={a.id}>
                      <p className="text-[11px] font-bold tracking-[0.16em] uppercase text-stone mb-2.5">
                        {a.nombre} {varSel[a.id] && <span className="text-ink normal-case tracking-normal">· {nombreOpcion(a.id, varSel[a.id])}</span>}
                      </p>
                      <div className="flex flex-wrap gap-2.5">
                        {opciones.map((oid) => {
                          const sel = varSel[a.id] === oid;
                          const esColor = a.tipo === "color";
                          const color = a.opciones.find((o) => o.id === oid)?.color || "#c9c2b4";
                          return (
                            <button key={oid} onClick={() => setVarSel({ ...varSel, [a.id]: oid })}
                              className={`flex items-center gap-2 border px-3.5 py-2.5 text-[12.5px] font-medium transition-all ${sel ? "border-ink bg-ink text-paper" : "border-linedark text-ink2 hover:border-ink"}`}>
                              {esColor && <span className="w-4 h-4 rounded-full border border-ink/10" style={{ background: color }} />}
                              {nombreOpcion(a.id, oid)}
                            </button>
                          );
                        })}
                      </div>
                    </div>
                  );
                })}
                <p className={`text-[12px] ${matched ? "text-ok" : "text-stone"}`}>
                  {matched ? `Combinación disponible · PVP ${fmt(matched.pvp)}` : "Elige una opción de cada atributo para ver la combinación."}
                </p>
              </div>
            )}

            {/* cantidad + comprar */}
            <div className="flex items-center gap-3 mt-8">
              <div className="flex items-center border border-linedark">
                <button onClick={() => setQty(Math.max(1, qty - 1))} className="px-3.5 py-3 hover:bg-paper2" aria-label="Menos"><I n="minus" s={13} /></button>
                <span className="px-3 text-[14px] font-semibold tnum">{qty}</span>
                <button onClick={() => setQty(Math.min(12, qty + 1))} className="px-3.5 py-3 hover:bg-paper2" aria-label="Más"><I n="plus" s={13} /></button>
              </div>
              <button onClick={comprar}
                className={`flex-1 py-4 text-[13px] font-semibold flex items-center justify-center gap-2 transition-colors ${added ? "bg-ok text-paper" : "bg-ink text-paper hover:bg-maroon"}`}>
                {added ? (<><I n="check" s={15} /> Añadido al carrito</>) : (<><I n="cart" s={15} /> Comprar</>)}
              </button>
            </div>

            {p.mto && (
              <p className="mt-4 text-[12.5px] font-semibold text-maroon bg-maroon/5 border-l-2 border-maroon px-4 py-3">
                Made to Order · {p.mto}
              </p>
            )}

            <p className="text-ink2 text-[14px] leading-relaxed mt-7">{p.desc}</p>

            <dl className="mt-7 space-y-2.5 text-[13px] border-t border-line pt-5">
              {[["Dimensiones", p.dims], ["Material", p.material], ["Origen", p.origin], ["Entrega", p.lead]].map(([k, v]) => (
                <div key={k} className="flex justify-between gap-6">
                  <dt className="text-stone">{k}</dt><dd className="font-medium text-right">{v}</dd>
                </div>
              ))}
            </dl>
          </div>
        </div>

        {/* relacionados */}
        {relacionados.length > 0 && (
          <div className="mt-20">
            <h2 className="font-display font-medium text-2xl">También en {p.category}</h2>
            <div className="grid sm:grid-cols-3 gap-6 mt-7">
              {relacionados.map((r) => (
                <a key={r.id} href={`#/producto/${slugDe(r.slug || r.name)}`} className="group">
                  <div className="aspect-[4/5] overflow-hidden bg-paper2">
                    <img src={r.img} alt={r.name} loading="lazy" className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-[1.05]" />
                  </div>
                  <p className="font-display font-medium text-[16px] mt-3 group-hover:text-maroon transition-colors">{r.name}</p>
                  <p className="text-[13px] text-stone tnum">{fmt(r.price)}</p>
                </a>
              ))}
            </div>
          </div>
        )}
      </main>
      <Pie />
    </div>
  );
}

/* ================= PÁGINA DE CATEGORÍA ================= */
export function CategoriaPage({ slug }: { slug: string }) {
  const cats = [...new Set(PRODUCTS.map((p) => p.category))];
  const cat = cats.find((c) => slugDe(c) === slug);
  if (!cat) return <NoEncontrado que="categoría" />;
  const items = PRODUCTS.filter((p) => p.category === cat);

  return (
    <div className="min-h-screen bg-paper flex flex-col">
      <Barra titulo={cat} />
      <main className="flex-1 max-w-[1200px] mx-auto px-5 sm:px-8 py-10 w-full">
        <h1 className="font-display font-medium text-[clamp(1.8rem,3.4vw,2.8rem)]">{cat}</h1>
        <p className="text-stone text-[13px] mt-2">{items.length} {items.length === 1 ? "pieza" : "piezas"} · hechas en Cuenca</p>

        <div className="flex flex-wrap gap-2 mt-7">
          {cats.map((c) => (
            <a key={c} href={`#/categoria/${slugDe(c)}`}
              className={`px-4 py-2 text-[12.5px] font-semibold border transition-colors ${c === cat ? "bg-ink text-paper border-ink" : "border-linedark text-ink2 hover:border-ink"}`}>
              {c}
            </a>
          ))}
        </div>

        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-12 mt-10">
          {items.map((p) => (
            <a key={p.id} href={`#/producto/${slugDe(p.slug || p.name)}`} className="group">
              <div className="relative aspect-[4/5] overflow-hidden bg-paper2">
                <img src={p.img} alt={p.name} loading="lazy" className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-[1.05]" />
                {p.mto && <span className="absolute top-3.5 left-3.5 text-[10px] font-bold tracking-[0.14em] uppercase px-2.5 py-1.5 bg-card/95 text-maroon">A medida</span>}
              </div>
              <p className="text-[10.5px] font-semibold tracking-[0.16em] uppercase text-stone mt-3.5 tnum">{p.sku}</p>
              <p className="font-display font-medium text-[17px] mt-0.5 group-hover:text-maroon transition-colors">{p.name}</p>
              <p className="text-[13px] text-stone tnum">{fmt(p.price)} <span className="text-[11px]">IVA incl.</span></p>
            </a>
          ))}
        </div>
      </main>
      <Pie />
    </div>
  );
}

/* ================= BLOG (listado + filtros) ================= */
export function BlogPage({ kind, value }: { kind?: "categoria" | "etiqueta" | "autor"; value?: string }) {
  const posts = useMemo(() => loadCMS().filter((p) => p.estado === "Publicado"), []);
  const cats = [...new Set(posts.map((p) => p.tag))];
  const tags = [...new Set(posts.flatMap((p) => p.etiquetas || []))];

  const filtrados = posts.filter((p) => {
    if (kind === "categoria") return slugDe(p.tag) === value;
    if (kind === "etiqueta") return (p.etiquetas || []).some((e) => slugDe(e) === value);
    if (kind === "autor") return p.autor === value;
    return true;
  });

  const titulo =
    kind === "categoria" ? `Categoría: ${value}` :
    kind === "etiqueta" ? `Etiqueta: ${tagTexto(value || "")}` :
    kind === "autor" ? `Autor: ${autorDe(value)?.nombre || value}` : "Diario de taller";

  return (
    <div className="min-h-screen bg-paper flex flex-col">
      <Barra titulo="Diario" />
      <main className="flex-1 max-w-[900px] mx-auto px-5 sm:px-8 py-10 w-full">
        <h1 className="font-display font-medium text-[clamp(1.8rem,3.4vw,2.8rem)]">{titulo}</h1>
        <p className="text-stone text-[13px] mt-2">{filtrados.length} {filtrados.length === 1 ? "artículo" : "artículos"}</p>

        {!kind && (
          <div className="flex flex-wrap gap-2 mt-7">
            {cats.map((c) => (
              <a key={c} href={`#/blog/categoria/${slugDe(c)}`} className="px-3.5 py-1.5 text-[12px] font-semibold border border-linedark text-ink2 hover:border-ink hover:text-ink transition-colors">{c}</a>
            ))}
          </div>
        )}

        <div className="mt-9 divide-y divide-line border-y border-line">
          {filtrados.map((p) => {
            const a = autorDe(p.autor);
            return (
                <a key={p.id} href={articuloUrl(p)} className="group flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6 py-6 hover:px-4 transition-all">                <span className="text-[11px] font-bold tracking-[0.16em] text-maroon uppercase shrink-0 w-14">{p.num}</span>
                <span className="flex-1">
                  <span className="font-display font-medium text-[18px] leading-snug group-hover:text-maroon transition-colors block">{p.titulo}</span>
                  <span className="text-[12px] text-stone mt-1 block">{a ? `${a.nombre} · ${a.cargo}` : "BLETIA"} · {minutosLectura(p.cuerpo)} min de lectura</span>
                </span>
                <span className="text-[11px] font-semibold uppercase tracking-[0.14em] text-stone shrink-0">{p.tag}</span>
              </a>
            );
          })}
          {filtrados.length === 0 && <p className="py-12 text-center text-stone text-[14px]">Aún no hay artículos aquí.</p>}
        </div>

        {!kind && tags.length > 0 && (
          <div className="mt-10">
            <p className="text-[11px] font-bold tracking-[0.16em] uppercase text-stone mb-3">Etiquetas</p>
            <div className="flex flex-wrap gap-2">
              {tags.map((t) => (
                <a key={t} href={tagUrl(t)} className="px-3 py-1.5 text-[12px] border border-line text-ink2 hover:border-maroon hover:text-maroon transition-colors">{tagTexto(t)}</a>
              ))}
            </div>
          </div>
        )}
      </main>
      <Pie />
    </div>
  );
}

/* ================= ARTÍCULO ================= */
export function ArticuloPage({ slug }: { slug: string }) {
  const posts = useMemo(() => loadCMS().filter((p) => p.estado === "Publicado"), []);
  const p = posts.find((x) => slugDe(x.titulo) === slug);
  if (!p) return <NoEncontrado que="artículo" />;
  const a = autorDe(p.autor);
  const [copied, setCopied] = useState(false);

  const copiar = () => {
    const url = `${window.location.origin}${window.location.pathname}${articuloUrl(p)}`;
    try { void navigator.clipboard.writeText(url); } catch { /* noop */ }
    setCopied(true); setTimeout(() => setCopied(false), 1500);
  };

  return (
    <div className="min-h-screen bg-paper flex flex-col">
      <Barra titulo={p.titulo} />
      <main className="flex-1 max-w-[760px] mx-auto px-5 sm:px-8 py-12 w-full">
        <nav className="text-[12px] text-stone flex items-center gap-2 mb-7">
          <a href="#/blog" className="hover:text-ink">Diario</a> <I n="chev-r" s={11} />
          <a href={`#/blog/categoria/${slugDe(p.tag)}`} className="hover:text-ink">{p.tag}</a>
        </nav>
        <p className="text-[12.5px] font-semibold text-maroon tracking-wide">{p.num} · {p.fecha}</p>
        <h1 className="font-display font-medium text-[clamp(1.8rem,3.6vw,2.9rem)] leading-[1.1] mt-4">{p.titulo}</h1>

        <div className="flex items-center gap-3 mt-7 pb-7 border-b border-line">
          <span className="w-10 h-10 bg-ink text-paper text-[12px] font-bold flex items-center justify-center shrink-0">
            {a ? a.nombre.split(" ").slice(0, 2).map((w) => w[0]).join("") : "B"}
          </span>
          <div className="flex-1">
            <p className="text-[13px] font-semibold">{a ? a.nombre : "BLETIA"}</p>
            <p className="text-[11.5px] text-stone">{a ? a.cargo : "Taller"} · {minutosLectura(p.cuerpo)} min de lectura</p>
          </div>
          <button onClick={copiar} className={`text-[12px] font-semibold flex items-center gap-1.5 border px-3 py-2 transition-colors ${copied ? "border-ok/40 text-ok bg-okbg" : "border-linedark text-ink2 hover:border-ink"}`}>
            <I n={copied ? "check" : "link"} s={13} /> {copied ? "Copiado" : "Compartir"}
          </button>
        </div>

        <p className="font-display text-[19px] leading-relaxed text-ink2 mt-8">{p.cuerpo}</p>

        {(p.etiquetas || []).length > 0 && (
          <div className="flex flex-wrap gap-2 mt-10">
            {(p.etiquetas || []).map((t) => (
              <a key={t} href={tagUrl(t)} className="px-3 py-1.5 text-[12px] border border-line text-ink2 hover:border-maroon hover:text-maroon transition-colors">{tagTexto(t)}</a>
            ))}
          </div>
        )}

        {a && (
          <div className="mt-12 border border-line bg-card p-6">
            <p className="text-[11px] font-bold tracking-[0.16em] uppercase text-stone">Sobre el autor</p>
            <p className="font-display font-medium text-[17px] mt-2">{a.nombre} · <span className="text-stone text-[14px]">{a.cargo}</span></p>
            <p className="text-[13.5px] text-ink2 leading-relaxed mt-2">{a.bio}</p>
            <a href={`#/blog/autor/${a.id}`} className="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-maroon mt-3 hover:text-maroon2">
              Ver todos sus artículos <I n="chev-r" s={12} />
            </a>
          </div>
        )}
      </main>
      <Pie />
    </div>
  );
}

/* ================= PÁGINA CMS (Políticas / Contacto / Nosotros) ================= */
export function PaginaPage({ slug }: { slug: string }) {
  const pag = loadPaginas().find((x) => x.slug === slug);
  if (!pag) return <NoEncontrado que="página" />;
  return (
    <div className="min-h-screen bg-paper flex flex-col">
      <Barra titulo={pag.titulo} />
      <main className="flex-1 max-w-[700px] mx-auto px-5 sm:px-8 py-14 w-full">
        <h1 className="font-display font-medium text-[clamp(1.8rem,3.4vw,2.7rem)]">{pag.titulo}</h1>
        <p className="font-display text-[18px] leading-relaxed text-ink2 mt-8 whitespace-pre-line">{pag.cuerpo}</p>
        {pag.slug === "contacto" && (
          <a href="#/" className="mt-9 inline-flex items-center gap-2 bg-ink text-paper px-6 py-3.5 text-[13px] font-semibold hover:bg-maroon transition-colors">
            <I n="back" s={14} /> Volver a la tienda
          </a>
        )}
      </main>
      <Pie />
    </div>
  );
}

export type { Product };
