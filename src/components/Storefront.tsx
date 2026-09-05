import { useEffect, useMemo, useState } from "react";
import { ATRIBUTOS, BLOG_CATEGORIAS, CITIES, IMG, PRODUCTS, autorDe, fmt, fmt2, loadCMS, loadSite, minutosLectura, randomCode, saveWebSuscriptor, slugDe, variantesDe, type Product } from "../data";
import { detectarDocumento } from "../utils/sri";
import { I, Modal, Reveal } from "./ui";

type CartLine = { id: string; qty: number };
type Step = "datos" | "pago" | "link" | "directo" | "listo";

const CATS = ["Todo", "Sofás", "Sillones", "Mesas", "Sillas", "Centros", "Almacenaje", "Descanso"] as const;

/* El Diario se sirve desde el CMS del panel (bletia-cms en localStorage) */

export default function Storefront() {
  const [cat, setCat] = useState<(typeof CATS)[number]>("Todo");
  const [cart, setCart] = useState<CartLine[]>(() => {
    try { return JSON.parse(localStorage.getItem("bletia-cart") || "[]"); } catch { return []; }
  });
  const [cartOpen, setCartOpen] = useState(false);
  const [quick, setQuick] = useState<Product | null>(null);
  const [checkout, setCheckout] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const [menu, setMenu] = useState(false);
  const [lastOrder, setLastOrder] = useState<{ code: string; track: string } | null>(null);

  /* búsqueda global, cuenta y lista de deseos */
  const [searchOpen, setSearchOpen] = useState(false);
  const [accountOpen, setAccountOpen] = useState(false);
  const [wishOpen, setWishOpen] = useState(false);
  const [wish, setWish] = useState<string[]>(() => {
    try { return JSON.parse(localStorage.getItem("bletia-wish") || "[]"); } catch { return []; }
  });
  const [cuenta, setCuenta] = useState<string>(() => localStorage.getItem("bletia-cuenta") || "");
  const [accForm, setAccForm] = useState({ nombre: "", email: "" });

  /* selector de variantes (combinación) en la ficha rápida */
  const [varSel, setVarSel] = useState<Record<string, string>>({});
  const [nlEmail, setNlEmail] = useState("");
  const [nlOk, setNlOk] = useState(false);

  /* Canal digital: lo publicado en el panel rige la tienda */
  const [site] = useState(loadSite);
  const [posts] = useState(() => loadCMS().filter((p) => p.estado === "Publicado"));
  const [blogCat, setBlogCat] = useState("Todo");
  const postsFiltrados = blogCat === "Todo" ? posts : posts.filter((p) => p.tag === blogCat);
  const destacado = PRODUCTS.find((p) => p.id === site.destacadoId) ?? PRODUCTS[0];
  const catActivas = CATS.filter((c) => c === "Todo" || site.colecciones[c] !== false);

  useEffect(() => { localStorage.setItem("bletia-cart", JSON.stringify(cart)); }, [cart]);
  useEffect(() => { localStorage.setItem("bletia-wish", JSON.stringify(wish)); }, [wish]);
  useEffect(() => { setVarSel({}); }, [quick?.id]);

  const quickVars = quick ? variantesDe(quick.id) : [];
  const matchedVar = quickVars.find((v) => Object.entries(v.opciones).every(([aid, oid]) => varSel[aid] === oid));
  const precioFicha = matchedVar ? matchedVar.pvp : quick?.price ?? 0;

  const toggleWish = (id: string) =>
    setWish((w) => (w.includes(id) ? w.filter((x) => x !== id) : [...w, id]));
  const saveCuenta = () => {
    if (accForm.nombre.trim().length < 2) return;
    setCuenta(accForm.nombre.trim());
    localStorage.setItem("bletia-cuenta", accForm.nombre.trim());
    setAccountOpen(false);
    setAccForm({ nombre: "", email: "" });
  };

  useEffect(() => {
    const f = () => setScrolled(window.scrollY > 24);
    f(); window.addEventListener("scroll", f, { passive: true });
    return () => window.removeEventListener("scroll", f);
  }, []);

  const count = cart.reduce((a, l) => a + l.qty, 0);
  const lines = cart.map((l) => ({ ...l, p: PRODUCTS.find((p) => p.id === l.id)! })).filter((l) => l.p);
  const total = lines.reduce((a, l) => a + l.p.price * l.qty, 0);
  const base = total / 1.15;
  const iva = total - base;

  const catFinal = catActivas.includes(cat) ? cat : "Todo";
  const visibles = useMemo(() => PRODUCTS.filter((p) => site.colecciones[p.category] !== false), [site]);
  const shown = useMemo(
    () => (catFinal === "Todo" ? visibles : visibles.filter((p) => p.category === catFinal)),
    [catFinal, visibles],
  );

  const add = (id: string, qty = 1) => {
    setCart((c) => {
      const ex = c.find((l) => l.id === id);
      return ex ? c.map((l) => (l.id === id ? { ...l, qty: Math.min(l.qty + qty, 12) } : l)) : [...c, { id, qty }];
    });
  };
  const setQty = (id: string, qty: number) =>
    setCart((c) => (qty <= 0 ? c.filter((l) => l.id !== id) : c.map((l) => (l.id === id ? { ...l, qty: Math.min(qty, 12) } : l))));

  const finishOrder = (code: string) => {
    setCart([]);
    setLastOrder({ code, track: `bletia.ec/t/${randomCode().toLowerCase()}` });
  };

  return (
    <div className="min-h-screen bg-paper text-ink font-dash">
      {/* ================= HEADER ================= */}
      <header className={`fixed top-0 inset-x-0 z-50 transition-all duration-500 ${scrolled ? "bg-paper/90 backdrop-blur-md border-b border-line" : "bg-transparent border-b border-transparent"}`}>
        {site.anuncioActivo && site.anuncioTexto && (
          <div className="bg-maroon text-cream text-center text-[11.5px] font-medium px-4 py-2 flex items-center justify-center gap-2">
            <I n="spark" s={12} className="shrink-0" />
            <span className="truncate">{site.anuncioTexto}</span>
          </div>
        )}
        <div className="max-w-[1400px] mx-auto px-5 sm:px-8 h-16 flex items-center justify-between gap-6">
          <a href="#top" className="font-display font-semibold tracking-[0.32em] text-[17px] leading-none select-none">
            BLETIA<span className="text-maroon">.</span>
          </a>
          <nav className="hidden md:flex items-center gap-8 text-[13px] font-medium text-ink2">
            <a href="#coleccion" className="u-grow hover:text-ink">Colección</a>
            <a href="#taller" className="u-grow hover:text-ink">Taller</a>
            <a href="#servicios" className="u-grow hover:text-ink">Servicios</a>
            <a href="#diario" className="u-grow hover:text-ink">Diario</a>
          </nav>
          <div className="flex items-center gap-1 sm:gap-1.5">
            <button onClick={() => setSearchOpen(true)} className="p-2.5 hover:bg-paper2 transition-colors" aria-label="Buscar en el sitio">
              <I n="search" s={19} />
            </button>
            <button onClick={() => setAccountOpen(!accountOpen)} className="p-2.5 hover:bg-paper2 transition-colors" aria-label="Mi cuenta">
              {cuenta ? (
                <span className="w-6 h-6 bg-ink text-paper text-[10px] font-bold flex items-center justify-center">{cuenta[0]?.toUpperCase()}</span>
              ) : (
                <I n="user" s={19} />
              )}
            </button>
            <button onClick={() => setWishOpen(true)} className="relative p-2.5 hover:bg-paper2 transition-colors" aria-label="Mis deseos">
              <I n="heart" s={19} />
              {wish.length > 0 && (
                <span key={wish.length} className="fade-in absolute -top-0.5 -right-0.5 min-w-[17px] h-[17px] px-1 bg-maroon text-cream text-[10px] font-bold flex items-center justify-center tnum">
                  {wish.length}
                </span>
              )}
            </button>
            <button onClick={() => setCartOpen(true)} className="relative p-2.5 hover:bg-paper2 transition-colors" aria-label="Abrir carrito">
              <I n="cart" s={19} />
              {count > 0 && (
                <span key={count} className="fade-in absolute -top-0.5 -right-0.5 min-w-[17px] h-[17px] px-1 bg-maroon text-cream text-[10px] font-bold flex items-center justify-center tnum">
                  {count}
                </span>
              )}
            </button>
            <button onClick={() => setMenu(true)} className="md:hidden p-2.5 hover:bg-paper2" aria-label="Menú">
              <I n="menu" s={19} />
            </button>
          </div>
        </div>
      </header>

      {/* menú móvil */}
      {menu && (
        <div className="fixed inset-0 z-[80] bg-paper flex flex-col">
          <div className="h-16 px-5 flex items-center justify-between border-b border-line">
            <span className="font-display font-semibold tracking-[0.32em]">BLETIA<span className="text-maroon">.</span></span>
            <button onClick={() => setMenu(false)} className="p-2.5" aria-label="Cerrar"><I n="close" s={20} /></button>
          </div>
          <nav className="flex flex-col p-8 gap-6 font-display text-3xl">
            {site.menus.tienda.map((m) => (
              <a key={m.label} href={m.url} onClick={() => setMenu(false)} className="hover:text-maroon transition-colors">{m.label}</a>
            ))}
          </nav>
        </div>
      )}

      {/* popup de cuenta */}
      {accountOpen && (
        <>
          <div className="fixed inset-0 z-[84]" onClick={() => setAccountOpen(false)} />
          <div className="fixed z-[85] top-[4.4rem] right-4 sm:right-8 w-[300px] bg-card border border-line shadow-[0_24px_60px_rgba(20,16,10,0.22)] slide-up">
            {cuenta ? (
              <div className="p-5">
                <p className="eyebrow">Hola de nuevo</p>
                <p className="font-display font-medium text-[20px] mt-2">{cuenta}</p>
                <p className="text-[12.5px] text-stone mt-1">Tus pedidos y deseos quedan guardados en este dispositivo.</p>
                <button onClick={() => { setCuenta(""); localStorage.removeItem("bletia-cuenta"); setAccountOpen(false); }}
                  className="w-full mt-4 border border-linedark text-[12.5px] font-semibold py-2.5 hover:bg-paper2 transition-colors">
                  Cerrar sesión
                </button>
              </div>
            ) : (
              <div className="p-5">
                <p className="eyebrow">Mi cuenta</p>
                <p className="text-[13px] text-ink2 mt-2 mb-4">Guarda tu nombre para agilizar tus próximas compras.</p>
                <input value={accForm.nombre} onChange={(e) => setAccForm({ ...accForm, nombre: e.target.value })}
                  className={`${inp} mb-2.5`} placeholder="Nombre" />
                <input value={accForm.email} onChange={(e) => setAccForm({ ...accForm, email: e.target.value })}
                  className={inp} placeholder="Correo (opcional)" />
                <button onClick={saveCuenta} className="w-full mt-3.5 bg-ink text-paper text-[12.5px] font-semibold py-3 hover:bg-maroon transition-colors">
                  Entrar
                </button>
                <p className="text-[10.5px] text-stone mt-3 text-center">¿Eres del equipo? <a href="#/dash" className="underline underline-offset-2 hover:text-ink">bletia.ec/dash</a></p>
              </div>
            )}
          </div>
        </>
      )}

      {/* búsqueda global del sitio */}
      <BusquedaGlobal open={searchOpen} onClose={() => setSearchOpen(false)}
        onProduct={(p) => { setSearchOpen(false); setQuick(p); }} />

      {/* lista de deseos */}
      {wishOpen && (
        <div className="fixed inset-0 z-[85]">
          <div className="absolute inset-0 bg-ink/45 fade-in" onClick={() => setWishOpen(false)} />
          <aside className="absolute right-0 top-0 h-full w-full max-w-[400px] bg-paper border-l border-line slide-in-right flex flex-col">
            <div className="flex items-center justify-between px-6 h-16 border-b border-line shrink-0">
              <p className="font-display font-medium text-lg">Mis deseos <span className="text-stone text-sm tnum">({wish.length})</span></p>
              <button onClick={() => setWishOpen(false)} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={18} /></button>
            </div>
            <div className="flex-1 overflow-y-auto px-6 py-5">
              {wish.length === 0 ? (
                <div className="h-full flex flex-col items-center justify-center text-center gap-4 text-stone">
                  <I n="heart" s={34} className="text-linedark" />
                  <p className="text-[14px]">Aún no guardas piezas en tus deseos.</p>
                  <button onClick={() => setWishOpen(false)} className="text-[13px] font-semibold text-ink u-grow">Descubrir la colección</button>
                </div>
              ) : (
                <ul className="space-y-5">
                  {wish.map((id) => {
                    const p = PRODUCTS.find((x) => x.id === id);
                    if (!p) return null;
                    return (
                      <li key={id} className="flex gap-4 fade-in">
                        <button onClick={() => { setQuick(p); setWishOpen(false); }} className="w-[72px] h-[88px] bg-paper2 shrink-0 overflow-hidden">
                          <img src={p.img} alt={p.name} className="w-full h-full object-cover" />
                        </button>
                        <div className="flex-1 min-w-0">
                          <div className="flex justify-between gap-3">
                            <p className="font-display font-medium text-[15px] leading-tight">{p.name}</p>
                            <button onClick={() => toggleWish(id)} className="text-stone hover:text-bad transition-colors" aria-label="Quitar"><I n="close" s={13} /></button>
                          </div>
                          <p className="text-[11.5px] text-stone mt-0.5">{p.material}</p>
                          <div className="flex items-center justify-between mt-3">
                            <p className="font-semibold text-[14px] tnum">{fmt(p.price)}</p>
                            <button onClick={() => { add(id); toggleWish(id); setWishOpen(false); setCartOpen(true); }}
                              className="text-[11.5px] font-semibold px-3 py-2 bg-ink text-paper hover:bg-maroon transition-colors flex items-center gap-1.5">
                              <I n="cart" s={12} /> Al carrito
                            </button>
                          </div>
                        </div>
                      </li>
                    );
                  })}
                </ul>
              )}
            </div>
          </aside>
        </div>
      )}

      {/* ================= APERTURA ================= */}
      <section id="top" className={`relative ${site.anuncioActivo && site.anuncioTexto ? "pt-[6.35rem]" : "pt-16"}`}>
        <div className="max-w-[1400px] mx-auto px-5 sm:px-8">
          <div className="grid lg:grid-cols-12 gap-8 lg:gap-0 items-stretch min-h-[calc(100vh-4rem)]">
            <div className="lg:col-span-7 flex flex-col justify-center py-12 lg:py-0 lg:pr-14 grain">
              <Reveal>
                <p className="eyebrow flex items-center gap-3">
                  <span className="w-1.5 h-1.5 bg-maroon inline-block" />
                  Muebles hechos a mano — Cuenca · Ecuador
                </p>
              </Reveal>
              <Reveal delay={90}>
                <h1 className="font-display font-medium text-[clamp(2.7rem,6.2vw,5.4rem)] leading-[1.0] tracking-[-0.015em] mt-6">
                  Cada pieza<br />define <em className="not-italic relative">tu espacio<span className="absolute left-0 -bottom-1 w-full h-[2px] bg-maroon/70" /></em>.
                </h1>
              </Reveal>
              <Reveal delay={180}>
                <p className="text-ink2 text-[15px] sm:text-base leading-relaxed max-w-[46ch] mt-7">
                  Muebles de nogal, roble y cuero vegetalizado: fabricados en nuestro taller y curados para durar décadas.
                  Pagas con <strong className="font-semibold">PayPhone</strong> — por link de un solo uso o directo en la web — y recibes en todo el Ecuador.
                </p>
              </Reveal>
              <Reveal delay={260}>
                <div className="flex flex-wrap items-center gap-4 mt-9">
                  <a href="#coleccion" className="group inline-flex items-center gap-3 bg-ink text-paper px-7 py-4 text-[13px] font-semibold tracking-wide hover:bg-maroon transition-colors duration-300">
                    Explorar la colección
                    <I n="arrow" s={16} className="transition-transform group-hover:translate-x-1" />
                  </a>
                  <a href="#taller" className="u-grow text-[13px] font-semibold text-ink2 hover:text-ink">Nuestro taller</a>
                </div>
              </Reveal>
              <Reveal delay={340}>
                <div className="flex flex-wrap gap-x-8 gap-y-2 mt-12 pt-6 border-t border-line text-[11.5px] font-medium text-stone tracking-wide">
                  <span className="flex items-center gap-2"><I n="shield" s={13} /> Garantía de 5 años</span>
                  <span className="flex items-center gap-2"><I n="card" s={13} /> IVA 15% incluido · Factura SRI</span>
                  <span className="flex items-center gap-2"><I n="truck" s={13} /> Entrega guante blanco nacional</span>
                </div>
              </Reveal>
            </div>

            <div className="lg:col-span-5 relative lg:min-h-[calc(100vh-4rem)]">
              <div className="relative h-[420px] sm:h-[520px] lg:h-full overflow-hidden bg-paper2">
                <img src={IMG.hero} alt="Butaca Aura de nogal y bouclé" className="kenburns w-full h-full object-cover" />
                <button
                  onClick={() => setQuick(destacado)}
                  className="group absolute bottom-5 left-5 right-5 sm:right-auto sm:w-[300px] bg-card/95 backdrop-blur border border-line p-4 flex items-center gap-4 text-left hover:border-maroon/50 transition-all duration-300 hover:-translate-y-0.5"
                >
                  <div className="w-12 h-14 overflow-hidden shrink-0">
                    <img src={destacado.img} alt="" className="w-full h-full object-cover" />
                  </div>
                  <div className="min-w-0">
                    <p className="font-display font-medium text-[15px] leading-tight truncate">{destacado.name}</p>
                    <p className="text-[11.5px] text-stone mt-0.5">{destacado.material} — {fmt(destacado.price)}</p>
                    <p className="text-[11px] font-semibold text-maroon mt-1.5 flex items-center gap-1 group-hover:gap-2 transition-all">
                      Ver pieza <I n="chev-r" s={11} />
                    </p>
                  </div>
                </button>
              </div>
              <span className="hidden xl:block absolute -right-7 top-1/2 -translate-y-1/2 rotate-90 origin-center text-[10px] font-semibold tracking-[0.5em] text-stone uppercase">
                Serie 2026
              </span>
            </div>
          </div>
        </div>

      </section>

      {/* ================= COLECCIÓN ================= */}
      <section id="coleccion" className="max-w-[1400px] mx-auto px-5 sm:px-8 py-20 sm:py-28 scroll-mt-16">
        <Reveal>
          <div className="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-12">
            <div>
              <p className="eyebrow flex items-center gap-3"><span className="w-1.5 h-1.5 bg-maroon inline-block" />La colección</p>
              <h2 className="font-display font-medium text-[clamp(1.9rem,3.6vw,3rem)] leading-[1.05] mt-4">
                Objetos serenos,<br className="hidden sm:block" /> líneas exactas.
              </h2>
            </div>
            <div className="flex flex-wrap gap-2">
              {catActivas.map((c) => (
                <button key={c} onClick={() => setCat(c)}
                  className={`px-4 py-2 text-[12.5px] font-semibold border transition-all duration-300 ${catFinal === c ? "bg-ink text-paper border-ink" : "border-linedark text-ink2 hover:border-ink hover:text-ink"}`}>
                  {c}
                </button>
              ))}
            </div>
          </div>
        </Reveal>

        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-14">
          {shown.map((p, i) => (
            <Reveal key={p.id} delay={(i % 3) * 90}>
              <article className="group cursor-pointer" onClick={() => setQuick(p)}>
                <div className="relative overflow-hidden bg-paper2 aspect-[4/5]">
                  <img src={p.img} alt={p.name} loading="lazy"
                    className="w-full h-full object-cover transition-transform duration-[900ms] ease-[cubic-bezier(0.2,0.7,0.2,1)] group-hover:scale-[1.05]" />
                  <span className={`absolute top-3.5 left-3.5 text-[10px] font-bold tracking-[0.16em] uppercase px-2.5 py-1.5 flex items-center gap-1.5 ${p.origin === "Taller BLETIA" ? "bg-card/95 text-ink" : "bg-ink/85 text-cream"}`}>
                    {p.origin === "Taller BLETIA" && <span className="w-1.5 h-1.5 bg-maroon" />}
                    {p.origin === "Taller BLETIA" ? "Taller" : "Curaduría"}
                  </span>
                  {p.stock <= 3 && (
                    <span className="absolute top-[46px] left-3.5 text-[10px] font-bold tracking-[0.14em] uppercase px-2.5 py-1.5 bg-card/95 text-warn">
                      Últimas {p.stock}
                    </span>
                  )}
                  <button onClick={(e) => { e.stopPropagation(); toggleWish(p.id); }}
                    aria-label={wish.includes(p.id) ? "Quitar de deseos" : "Guardar en deseos"}
                    className={`absolute top-3 right-3 w-8 h-8 flex items-center justify-center bg-card/95 backdrop-blur transition-all duration-300 hover:scale-110 ${wish.includes(p.id) ? "text-maroon" : "text-ink/70 hover:text-ink"}`}>
                    <I n={wish.includes(p.id) ? "heart-fill" : "heart"} s={16} />
                  </button>
                  <button
                    onClick={(e) => { e.stopPropagation(); add(p.id); setCartOpen(true); }}
                    className="absolute bottom-0 inset-x-0 bg-ink text-paper text-[12.5px] font-semibold py-3.5 flex items-center justify-center gap-2 translate-y-full group-hover:translate-y-0 transition-transform duration-400 hover:bg-maroon"
                  >
                    <I n="plus" s={14} /> Añadir al carrito — {fmt(p.price)}
                  </button>
                </div>
                <div className="pt-4 flex items-baseline justify-between gap-4">
                  <div>
                    <p className="text-[10.5px] font-semibold tracking-[0.18em] text-stone uppercase tnum">{p.sku}</p>
                    <a href={`#/producto/${slugDe(p.slug || p.name)}`} onClick={(e) => e.stopPropagation()}>
                      <h3 className="font-display font-medium text-[19px] mt-1 group-hover:text-maroon transition-colors duration-300">{p.name}</h3>
                    </a>
                    <p className="text-[12.5px] text-stone mt-0.5">{p.material}</p>
                  </div>
                  <div className="text-right shrink-0">
                    <p className="font-semibold tnum text-[15px]">{fmt(p.price)}</p>
                    <p className="text-[10.5px] text-stone">IVA incl.</p>
                  </div>
                </div>
              </article>
            </Reveal>
          ))}
        </div>
      </section>

      {/* ================= TALLER ================= */}
      <section id="taller" className="bg-coal text-cream scroll-mt-16">
        <div className="max-w-[1400px] mx-auto px-5 sm:px-8 py-20 sm:py-28">
          <div className="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            <Reveal>
              <div className="relative overflow-hidden">
                <img src={IMG.taller} alt="Maestro del taller BLETIA trabajando nogal" loading="lazy"
                  className="w-full h-[420px] sm:h-[520px] object-cover transition-transform duration-[1200ms] hover:scale-[1.03]" />
                <span className="absolute bottom-4 left-4 bg-coal/85 backdrop-blur text-cream text-[10.5px] font-semibold tracking-[0.2em] uppercase px-3 py-2 flex items-center gap-2">
                  <span className="w-1.5 h-1.5 bg-maroon pulse-maroon" /> Taller BLETIA · Quito
                </span>
              </div>
            </Reveal>
            <div>
              <Reveal><p className="eyebrow flex items-center gap-3 !text-cream/50"><span className="w-1.5 h-1.5 bg-maroon inline-block" />Taller BLETIA</p></Reveal>
              <Reveal delay={80}>
                <h2 className="font-display font-medium text-[clamp(1.9rem,3.6vw,3rem)] leading-[1.05] mt-4">
                  De la tabla<br />al objeto.
                </h2>
              </Reveal>
              <Reveal delay={150}>
                <p className="text-cream/70 text-[15px] leading-relaxed max-w-[52ch] mt-6">
                  Una parte de la colección nace aquí: madera certificada, ensambles de espiga y acabados a mano.
                  Cada pieza de taller sale numerada, firmada y con su orden de fabricación trazable desde el panel interno.
                </p>
              </Reveal>
              <div className="mt-10 grid sm:grid-cols-2 gap-x-10 gap-y-7">
                {[
                  ["01", "Selección", "Nogal y roble con certificado de origen, secados 24 meses."],
                  ["02", "Ensamble", "Caja y espiga. Sin clavos, sin prisa, sin atajos."],
                  ["03", "Acabado", "Aceites y ceras naturales aplicados en tres manos."],
                  ["04", "Firma", "Control de calidad y número de serie grabado a fuego."],
                ].map(([n, t, d], i) => (
                  <Reveal key={n} delay={200 + i * 80}>
                    <div className="flex gap-4 border-t border-cream/15 pt-5 group">
                      <span className="font-display text-maroon text-[13px] font-semibold pt-1">{n}</span>
                      <div>
                        <p className="font-display font-medium text-[17px] group-hover:translate-x-1 transition-transform duration-300">{t}</p>
                        <p className="text-cream/55 text-[13px] leading-relaxed mt-1.5">{d}</p>
                      </div>
                    </div>
                  </Reveal>
                ))}
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ================= SERVICIOS ================= */}
      <section id="servicios" className="max-w-[1400px] mx-auto px-5 sm:px-8 py-20 sm:py-28 scroll-mt-16">
        <div className="grid lg:grid-cols-12 gap-12">
          <div className="lg:col-span-4">
            <Reveal>
              <p className="eyebrow flex items-center gap-3"><span className="w-1.5 h-1.5 bg-maroon inline-block" />Servicios</p>
              <h2 className="font-display font-medium text-[clamp(1.9rem,3.4vw,2.8rem)] leading-[1.05] mt-4">
                Comprar es la parte fácil.
              </h2>
              <p className="text-ink2 text-[14.5px] leading-relaxed mt-5 max-w-[40ch]">
                Detrás de cada entrega hay una red propia de transporte, proveedores auditados y facturación electrónica al instante.
              </p>
              <div className="mt-8 overflow-hidden border border-line">
                <img src={IMG.detalle} alt="Materiales: cuero vegetalizado y nogal" loading="lazy" className="w-full h-44 object-cover hover:scale-105 transition-transform duration-700" />
              </div>
            </Reveal>
          </div>
          <div className="lg:col-span-8">
            {[
              ["01", "Entrega guante blanco", "Flota propia en Quito y red de transportistas auditados en el resto del país. Armamos en sitio, retiramos el embalaje y dejamos todo en su lugar.", "truck"],
              ["02", "Pago con PayPhone", "Elige: link de pago de un solo uso que llega a tu WhatsApp, o pago directo aquí en la web. Difiere con tu tarjeta, nosotros no tocamos tus datos.", "card"],
              ["03", "Garantía de 5 años", "Estructura y ensambles garantizados, con mantenimiento anual de cortesía durante los dos primeros años.", "shield"],
              ["04", "Proyectos a medida", "Arquitectos, hoteles y oficinas: cotización por plano, series numeradas y facturación electrónica autorizada por el SRI.", "doc"],
            ].map(([n, t, d, ic], i) => (
              <Reveal key={n} delay={i * 80}>
                <div className="group grid sm:grid-cols-[64px_44px_1fr] gap-4 sm:gap-6 items-start border-t border-linedark py-8 hover:bg-card transition-colors duration-300 px-2 -mx-2">
                  <span className="font-display font-medium text-[26px] text-linedark group-hover:text-maroon transition-colors duration-300 tnum">{n}</span>
                  <span className="mt-1 text-ink2 group-hover:text-maroon transition-colors"><I n={ic as "truck"} s={22} /></span>
                  <div>
                    <h3 className="font-display font-medium text-[20px]">{t}</h3>
                    <p className="text-ink2 text-[14px] leading-relaxed mt-2 max-w-[62ch]">{d}</p>
                  </div>
                </div>
              </Reveal>
            ))}
            <div className="border-t border-linedark" />
          </div>
        </div>
      </section>

      {/* ================= DIARIO ================= */}
      <section id="diario" className="border-t border-line bg-paper2/60 scroll-mt-16">
        <div className="max-w-[1400px] mx-auto px-5 sm:px-8 py-20">
          <Reveal>
            <div className="flex items-end justify-between gap-6 mb-10">
              <div>
                <p className="eyebrow flex items-center gap-3"><span className="w-1.5 h-1.5 bg-maroon inline-block" />Diario de taller</p>
                <h2 className="font-display font-medium text-[clamp(1.7rem,3vw,2.4rem)] mt-4">Notas que huelen a aserrín.</h2>
              </div>
              <a href="#diario" className="hidden sm:inline-flex items-center gap-2 text-[13px] font-semibold u-grow">Todo el diario <I n="arrow" s={14} /></a>
            </div>
          </Reveal>
          {/* filtro por categoría del blog */}
          <div className="flex flex-wrap gap-2 mb-6">
            {["Todo", ...BLOG_CATEGORIAS].map((c) => (
              <button key={c} onClick={() => setBlogCat(c)}
                className={`px-3.5 py-1.5 text-[12px] font-semibold border transition-colors ${blogCat === c ? "bg-ink text-paper border-ink" : "border-linedark text-ink2 hover:border-ink"}`}>
                {c}
              </button>
            ))}
          </div>

          {postsFiltrados.length === 0 ? (
            <p className="border-t border-linedark py-10 text-[13.5px] text-stone text-center">
              El diario está en pausa: el taller anda con las manos en la madera. Vuelve pronto.
            </p>
          ) : (
            postsFiltrados.map((d, i) => {
              const au = autorDe(d.autor);
              return (
                <Reveal key={d.id} delay={i * 70}>
                  <a href={`#/articulo/${slugDe(d.titulo)}`} className="group block border-t border-linedark py-7 hover:px-4 transition-all duration-300">
                    <div className="flex flex-wrap items-baseline gap-x-4 gap-y-1">
                      <span className="text-[11px] font-bold tracking-[0.16em] text-maroon uppercase">{d.num}</span>
                      <span className="text-[12px] text-stone tnum">{d.fecha}</span>
                      <span className="text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone bg-paper2 px-2 py-0.5">{d.tag}</span>
                      <span className="text-[11px] text-stone flex items-center gap-1"><I n="clock" s={11} /> {minutosLectura(d.cuerpo)} min de lectura</span>
                    </div>
                    <div className="grid sm:grid-cols-[1fr_auto] gap-4 mt-2.5">
                      <div>
                        <h3 className="font-display font-medium text-[18px] sm:text-[21px] group-hover:text-maroon transition-colors leading-snug">{d.titulo}</h3>
                        <p className="text-[13.5px] text-ink2 leading-relaxed mt-2 max-w-[68ch]">{d.cuerpo}</p>
                        {d.etiquetas && d.etiquetas.length > 0 && (
                          <div className="flex flex-wrap gap-1.5 mt-3">
                            {d.etiquetas.map((e) => (
                              <span key={e} className="text-[10.5px] font-semibold text-ink2 border border-linedark px-2 py-0.5 group-hover:border-maroon/40 transition-colors">#{e}</span>
                            ))}
                          </div>
                        )}
                      </div>
                      {au && (
                        <div className="flex sm:flex-col items-center sm:items-end gap-2.5 shrink-0">
                          <span className="w-10 h-10 bg-ink text-paper text-[12px] font-bold flex items-center justify-center shrink-0">
                            {au.nombre.split(" ").slice(0, 2).map((w) => w[0]).join("")}
                          </span>
                          <span className="text-right">
                            <span className="block text-[12px] font-semibold">{au.nombre}</span>
                            <span className="block text-[10.5px] text-stone">{au.cargo}</span>
                          </span>
                        </div>
                      )}
                    </div>
                  </a>
                </Reveal>
              );
            })
          )}
        </div>
      </section>

      {/* ================= FOOTER ================= */}
      <footer className="bg-ink text-cream">
        <div className="max-w-[1400px] mx-auto px-5 sm:px-8 pt-16 pb-8">
          {/* newsletter (alimenta Marketing · Digest del panel) */}
          <div className="grid md:grid-cols-2 gap-6 items-center border-b border-cream/15 pb-10 mb-12">
            <div>
              <p className="font-display font-medium text-[clamp(1.3rem,2.4vw,1.8rem)] leading-tight">El diario del taller, en tu correo.</p>
              <p className="text-cream/60 text-[13.5px] mt-2">Sin spam. Confirmas con un clic y te bajas cuando quieras.</p>
            </div>
            {nlOk ? (
              <p className="flex items-center gap-2.5 text-[13.5px] font-medium text-ok md:justify-end fade-in">
                <I n="check" s={16} /> ¡Gracias! Revisa tu correo para confirmar la suscripción.
              </p>
            ) : (
              <form
                onSubmit={(e) => { e.preventDefault(); if (/.+@.+\..+/.test(nlEmail)) { saveWebSuscriptor(nlEmail.trim()); setNlOk(true); } }}
                className="flex gap-2 md:justify-end"
              >
                <input value={nlEmail} onChange={(e) => setNlEmail(e.target.value)} type="email" required
                  placeholder="tu@correo.com"
                  className="flex-1 md:max-w-[280px] bg-transparent border border-cream/30 px-4 py-3 text-[13.5px] text-cream placeholder:text-cream/40 outline-none focus:border-maroon transition-colors" />
                <button type="submit" className="bg-maroon text-cream text-[13px] font-semibold px-6 hover:bg-maroon2 transition-colors">
                  Suscribirme
                </button>
              </form>
            )}
          </div>

          <div className="grid md:grid-cols-12 gap-10">
            <div className="md:col-span-5">
              <p className="font-display font-semibold tracking-[0.32em] text-xl">BLETIA<span className="text-maroon">.</span></p>
              <p className="text-cream/60 text-[13.5px] leading-relaxed mt-5 max-w-[38ch]">
                Mueblería de lujo minimalista. Fabricamos en Quito, entregamos en todo el Ecuador y cobramos como debe ser: seguro.
              </p>
              <div className="flex items-center gap-3 mt-7">
                <span className="w-8 h-8 border border-cream/25 flex items-center justify-center hover:bg-maroon hover:border-maroon transition-colors cursor-pointer"><I n="spark" s={14} /></span>
                <span className="w-8 h-8 border border-cream/25 flex items-center justify-center hover:bg-maroon hover:border-maroon transition-colors cursor-pointer"><I n="image" s={14} /></span>
                <span className="w-8 h-8 border border-cream/25 flex items-center justify-center hover:bg-maroon hover:border-maroon transition-colors cursor-pointer"><I n="link" s={14} /></span>
              </div>
            </div>
            <div className="md:col-span-2">
              <p className="text-[11px] font-bold tracking-[0.2em] uppercase text-cream/40 mb-4">Tienda</p>
              {site.menus.tienda.map((m) => (
                <a key={m.label} href={m.url} className="block text-[13.5px] text-cream/75 hover:text-cream py-1.5 u-grow w-fit">{m.label}</a>
              ))}
            </div>
            <div className="md:col-span-2">
              <p className="text-[11px] font-bold tracking-[0.2em] uppercase text-cream/40 mb-4">Empresa</p>
              {site.menus.empresa.map((m) => (
                <a key={m.label} href={m.url} className="block text-[13.5px] text-cream/75 hover:text-cream py-1.5 u-grow w-fit">{m.label}</a>
              ))}
            </div>
            <div className="md:col-span-3" id="legal">
              <p className="text-[11px] font-bold tracking-[0.2em] uppercase text-cream/40 mb-4">Legal · Ecuador</p>
              <p className="text-[12.5px] text-cream/60 leading-relaxed">
                BLETIA S.A.S. · RUC 1793442001001<br />
                Taller y showroom en Cuenca, Ecuador<br />
                Facturación electrónica autorizada por el SRI<br />
                Precios en USD · IVA 15% incluido
              </p>
              <p className="text-[11px] text-cream/40 mt-4 flex items-center gap-2">
                <span className="w-1.5 h-1.5 bg-maroon" /> Pagos procesados por PayPhone
              </p>
            </div>
          </div>
          <div className="border-t border-cream/15 mt-12 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11.5px] text-cream/40">
            <span>© 2026 BLETIA. Hecho en Ecuador, a mano y a tiempo.</span>
            <span className="flex items-center gap-2"><span className="w-1.5 h-1.5 bg-maroon" /> bletia.ec</span>
          </div>
        </div>
      </footer>

      {/* ================= QUICK VIEW ================= */}
      <Modal open={!!quick} onClose={() => setQuick(null)} w="max-w-4xl">
        {quick && (
          <div className="grid md:grid-cols-2">
            <div className="bg-paper2 h-64 md:h-auto overflow-hidden">
              <img src={quick.img} alt={quick.name} className="w-full h-full object-cover" />
            </div>
            <div className="p-6 sm:p-9">
              <div className="flex items-start justify-between gap-4">
                <div>
                  <p className="text-[10.5px] font-bold tracking-[0.18em] uppercase text-stone tnum">{quick.sku} · {quick.category}</p>
                  <h3 className="font-display font-medium text-[28px] leading-tight mt-2">{quick.name}</h3>
                </div>
                <button onClick={() => setQuick(null)} className="p-2 hover:bg-paper2 transition-colors" aria-label="Cerrar"><I n="close" s={18} /></button>
              </div>
              <p className="text-ink2 text-[13.5px] leading-relaxed mt-4">{quick.desc}</p>
              <dl className="mt-6 space-y-2.5 text-[13px]">
                {[
                  ["Material", quick.material], ["Dimensiones", quick.dims], ["Origen", quick.origin],
                  ["Disponibilidad", quick.state === "En taller" ? `En fabricación · ${quick.lead}` : `${quick.stock} en stock · ${quick.lead}`],
                  ...(quick.mto ? [["Made to Order", quick.mto] as [string, string]] : []),
                ].map(([k, v]) => (
                  <div key={k} className="flex justify-between gap-6 border-b border-line pb-2.5">
                    <dt className="text-stone">{k}</dt><dd className="font-medium text-right">{v}</dd>
                  </div>
                ))}
              </dl>

              {quickVars.length > 0 && (
                <div className="mt-6 space-y-4">
                  <p className="text-[10.5px] font-bold tracking-[0.18em] uppercase text-stone">Elige tu combinación</p>
                  {ATRIBUTOS.filter((a) => quickVars.some((v) => a.id in v.opciones)).map((a) => (
                    <div key={a.id}>
                      <p className="text-[12px] font-semibold text-ink2 mb-2">{a.nombre}</p>
                      <div className="flex flex-wrap gap-2">
                        {a.opciones.map((o) => {
                          const sel = varSel[a.id] === o.id;
                          return (
                            <button key={o.id} onClick={() => setVarSel((s) => ({ ...s, [a.id]: sel ? "" : o.id }))}
                              className={`inline-flex items-center gap-2 border px-3 py-2 text-[12.5px] transition-all duration-200 ${sel ? "border-ink bg-ink text-paper" : "border-linedark text-ink2 hover:border-ink"}`}>
                              {o.color && <span className="w-3.5 h-3.5 rounded-full border border-line shrink-0" style={{ background: o.color }} />}
                              {o.valor}
                            </button>
                          );
                        })}
                      </div>
                    </div>
                  ))}
                  {matchedVar && (
                    <p className="text-[11.5px] text-ok font-medium flex items-center gap-1.5 fade-in">
                      <I n="check" s={13} /> Combinación disponible · PVP {fmt(matchedVar.pvp)}
                    </p>
                  )}
                </div>
              )}

              <div className="flex items-end justify-between mt-7">
                <div>
                  <p className="font-display font-medium text-[26px] tnum">{fmt(precioFicha)}</p>
                  <p className="text-[11px] text-stone">IVA 15% incluido · {fmt2(precioFicha / 1.15)} base</p>
                </div>
                <div className="flex flex-col items-end gap-1.5">
                  {quick.state === "En taller" && (
                    <span className="text-[11px] font-bold tracking-wide uppercase px-2.5 py-1.5 bg-warnbg text-warn">Reserva tu serie</span>
                  )}
                  {quick.mto && (
                    <span className="text-[11px] font-bold tracking-wide uppercase px-2.5 py-1.5 bg-ink text-paper">Made to Order</span>
                  )}
                </div>
              </div>
              <div className="flex gap-3 mt-6">
                <button onClick={() => toggleWish(quick.id)}
                  aria-label="Guardar en deseos"
                  className={`w-[52px] shrink-0 border flex items-center justify-center transition-colors ${wish.includes(quick.id) ? "border-maroon text-maroon bg-maroon/5" : "border-linedark text-ink2 hover:border-ink hover:text-ink"}`}>
                  <I n={wish.includes(quick.id) ? "heart-fill" : "heart"} s={17} />
                </button>
                <button onClick={() => { add(quick.id); setQuick(null); setCartOpen(true); }}
                  className="flex-1 bg-ink text-paper text-[13px] font-semibold py-4 hover:bg-maroon transition-colors flex items-center justify-center gap-2">
                  <I n="cart" s={15} /> Añadir al carrito
                </button>
                <button onClick={() => { add(quick.id); setQuick(null); setCheckout(true); }}
                  className="flex-1 border border-ink text-ink text-[13px] font-semibold py-4 hover:bg-paper2 transition-colors">
                  Comprar ahora
                </button>
              </div>
            </div>
          </div>
        )}
      </Modal>

      {/* ================= CARRITO ================= */}
      {cartOpen && (
        <div className="fixed inset-0 z-[85]">
          <div className="absolute inset-0 bg-ink/45 fade-in" onClick={() => setCartOpen(false)} />
          <aside className="absolute right-0 top-0 h-full w-full max-w-[420px] bg-paper border-l border-line slide-in-right flex flex-col">
            <div className="flex items-center justify-between px-6 h-16 border-b border-line shrink-0">
              <p className="font-display font-medium text-lg">Tu carrito <span className="text-stone text-sm tnum">({count})</span></p>
              <button onClick={() => setCartOpen(false)} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={18} /></button>
            </div>
            <div className="flex-1 overflow-y-auto px-6 py-5">
              {lines.length === 0 ? (
                <div className="h-full flex flex-col items-center justify-center text-center gap-4 text-stone">
                  <I n="cart" s={34} className="text-linedark" />
                  <p className="text-[14px]">Aún no eliges ninguna pieza.</p>
                  <button onClick={() => setCartOpen(false)} className="text-[13px] font-semibold text-ink u-grow">Ver la colección</button>
                </div>
              ) : (
                <ul className="space-y-5">
                  {lines.map((l) => (
                    <li key={l.id} className="flex gap-4 fade-in">
                      <div className="w-[72px] h-[88px] bg-paper2 shrink-0 overflow-hidden">
                        <img src={l.p.img} alt={l.p.name} className="w-full h-full object-cover" />
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex justify-between gap-3">
                          <p className="font-display font-medium text-[15px] leading-tight">{l.p.name}</p>
                          <button onClick={() => setQty(l.id, 0)} className="text-stone hover:text-bad transition-colors" aria-label="Quitar"><I n="close" s={13} /></button>
                        </div>
                        <p className="text-[11.5px] text-stone mt-0.5">{l.p.material}</p>
                        <div className="flex items-center justify-between mt-3">
                          <div className="flex items-center border border-linedark">
                            <button onClick={() => setQty(l.id, l.qty - 1)} className="px-2.5 py-1.5 hover:bg-paper2" aria-label="Menos"><I n="minus" s={12} /></button>
                            <span className="px-2 text-[12.5px] font-semibold tnum">{l.qty}</span>
                            <button onClick={() => setQty(l.id, l.qty + 1)} className="px-2.5 py-1.5 hover:bg-paper2" aria-label="Más"><I n="plus" s={12} /></button>
                          </div>
                          <p className="font-semibold text-[14px] tnum">{fmt(l.p.price * l.qty)}</p>
                        </div>
                      </div>
                    </li>
                  ))}
                </ul>
              )}
            </div>
            {lines.length > 0 && (
              <div className="border-t border-line px-6 py-5 bg-card shrink-0">
                <div className="space-y-1.5 text-[13px]">
                  <div className="flex justify-between text-ink2"><span>Base imponible</span><span className="tnum">{fmt2(base)}</span></div>
                  <div className="flex justify-between text-ink2"><span>IVA 15%</span><span className="tnum">{fmt2(iva)}</span></div>
                  <div className="flex justify-between font-display font-medium text-[18px] pt-2 border-t border-line mt-2"><span>Total</span><span className="tnum">{fmt2(total)}</span></div>
                </div>
                <button onClick={() => { setCartOpen(false); setCheckout(true); }}
                  className="w-full mt-4 bg-ink text-paper text-[13px] font-semibold py-4 hover:bg-maroon transition-colors flex items-center justify-center gap-2">
                  Finalizar compra <I n="arrow" s={15} />
                </button>
                <p className="text-[11px] text-stone text-center mt-3 flex items-center justify-center gap-1.5">
                  <I n="shield" s={12} /> Pago seguro con PayPhone · Factura electrónica SRI
                </p>
              </div>
            )}
          </aside>
        </div>
      )}

      {/* ================= CHECKOUT ================= */}
      {checkout && (
        <CheckoutModal
          total={total} base={base} iva={iva} count={count} lastOrder={lastOrder}
          onClose={() => { setCheckout(false); }}
          onFinish={finishOrder}
        />
      )}
    </div>
  );
}

/* ---- Búsqueda global de todo el sitio público ---- */
const SECCIONES = [
  { label: "Colección", url: "#coleccion" },
  { label: "Taller", url: "#taller" },
  { label: "Servicios", url: "#servicios" },
  { label: "Diario", url: "#diario" },
];

function BusquedaGlobal({ open, onClose, onProduct }: {
  open: boolean; onClose: () => void; onProduct: (p: Product) => void;
}) {
  const [q, setQ] = useState("");
  useEffect(() => {
    if (open) setQ("");
  }, [open]);
  if (!open) return null;

  const t = q.trim().toLowerCase();
  const posts = loadCMS().filter((p) => p.estado === "Publicado");
  const prods = t ? PRODUCTS.filter((p) =>
    [p.name, p.category, p.material, p.sku, p.desc].join(" ").toLowerCase().includes(t)) : [];
  const notas = t ? posts.filter((p) => (p.titulo + " " + p.tag).toLowerCase().includes(t)) : [];
  const secciones = t ? SECCIONES.filter((s) => s.label.toLowerCase().includes(t)) : [];
  const vacio = t && prods.length === 0 && notas.length === 0 && secciones.length === 0;

  const goAnchor = (url: string) => { onClose(); window.location.hash = ""; setTimeout(() => { document.querySelector(url)?.scrollIntoView({ behavior: "smooth" }); }, 60); };

  return (
    <div className="fixed inset-0 z-[90]">
      <div className="absolute inset-0 bg-ink/50 backdrop-blur-[2px] fade-in" onClick={onClose} />
      <div className="relative max-w-[620px] mx-auto mt-[10vh] px-4">
        <div className="bg-card border border-line shadow-[0_30px_80px_rgba(20,16,10,0.35)] slide-up">
          <div className="flex items-center gap-3 px-5 border-b border-line">
            <I n="search" s={18} className="text-stone shrink-0" />
            <input autoFocus value={q} onChange={(e) => setQ(e.target.value)}
              placeholder="Busca muebles, materiales, notas del diario…"
              className="flex-1 py-4 bg-transparent outline-none text-[15px] placeholder:text-stone/70" />
            <button onClick={onClose} className="p-1.5 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={16} /></button>
          </div>
          <div className="max-h-[52vh] overflow-y-auto p-3">
            {!t && (
              <div className="p-6 text-center text-[13px] text-stone">
                Escribe para buscar en toda la tienda: piezas, categorías y el diario de taller.
              </div>
            )}
            {vacio && (
              <div className="p-6 text-center text-[13px] text-stone">Sin resultados para «{q}». Prueba con «nogal», «mesa» o «entrega».</div>
            )}
            {prods.length > 0 && <p className="px-3 pt-2 pb-1 text-[10px] font-bold tracking-[0.18em] uppercase text-stone">Piezas</p>}
            {prods.map((p) => (
              <button key={p.id} onClick={() => onProduct(p)}
                className="w-full flex items-center gap-3.5 px-3 py-2.5 text-left hover:bg-paper2 transition-colors">
                <img src={p.img} alt="" className="w-11 h-13 h-[52px] object-cover shrink-0 bg-paper2" />
                <span className="flex-1 min-w-0">
                  <span className="block font-medium text-[14px]">{p.name}</span>
                  <span className="block text-[11.5px] text-stone truncate">{p.material} · {p.category}</span>
                </span>
                <span className="text-[13px] font-semibold tnum">{fmt(p.price)}</span>
              </button>
            ))}
            {notas.length > 0 && <p className="px-3 pt-3 pb-1 text-[10px] font-bold tracking-[0.18em] uppercase text-stone">Diario de taller</p>}
            {notas.map((n) => (
              <button key={n.id} onClick={() => goAnchor("#diario")}
                className="w-full flex items-center gap-3.5 px-3 py-2.5 text-left hover:bg-paper2 transition-colors">
                <I n="doc" s={16} className="text-stone shrink-0" />
                <span className="flex-1 min-w-0">
                  <span className="block font-medium text-[14px]">{n.titulo}</span>
                  <span className="block text-[11.5px] text-stone">{n.fecha} · {n.tag}</span>
                </span>
                <I n="chev-r" s={13} className="text-stone" />
              </button>
            ))}
            {secciones.length > 0 && <p className="px-3 pt-3 pb-1 text-[10px] font-bold tracking-[0.18em] uppercase text-stone">Secciones</p>}
            {secciones.map((s) => (
              <button key={s.url} onClick={() => goAnchor(s.url)}
                className="w-full flex items-center gap-3.5 px-3 py-2.5 text-left hover:bg-paper2 transition-colors">
                <I n="arrow" s={15} className="text-stone shrink-0" />
                <span className="flex-1 font-medium text-[14px]">{s.label}</span>
                <I n="chev-r" s={13} className="text-stone" />
              </button>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

/* ------------------------------------------------ */
function CheckoutModal({ total, base, iva, count, lastOrder, onClose, onFinish }: {
  total: number; base: number; iva: number; count: number;
  lastOrder: { code: string; track: string } | null;
  onClose: () => void; onFinish: (code: string) => void;
}) {
  const [step, setStep] = useState<Step>(count > 0 ? "datos" : "listo");
  const [form, setForm] = useState({ nombre: "", doc: "", tel: "", ciudad: CITIES[0], dir: "" });
  const [err, setErr] = useState("");
  const site = loadSite();
  const [method, setMethod] = useState<"link" | "directo">(site.pagoLink ? "link" : "directo");
  const [linkCode] = useState(randomCode);
  const [payState, setPayState] = useState<"idle" | "proc" | "ok">("idle");
  const [orderCode] = useState(`BL-2026-0${148 + Math.floor(Math.random() * 40)}`);

  useEffect(() => {
    document.body.style.overflow = "hidden";
    return () => { document.body.style.overflow = ""; };
  }, []);

  const docV = detectarDocumento(form.doc);
  const validate = () => {
    if (form.nombre.trim().length < 3) return setErr("Ingresa el nombre completo o razón social.");
    if (!docV.valido) return setErr(`Documento inválido: ${docV.detalle}`);
    if (form.dir.trim().length < 6) return setErr("Necesitamos una dirección de entrega precisa.");
    setErr("");
    setStep("pago");
  };

  const processPay = () => {
    setPayState("proc");
    setTimeout(() => { setPayState("ok"); setTimeout(() => { onFinish(orderCode); setStep("listo"); }, 700); }, 1900);
  };

  const done = lastOrder && step === "listo";

  return (
    <Modal open onClose={onClose} w="max-w-xl">
      <div className="p-6 sm:p-9">
        <div className="flex items-center justify-between mb-1">
          <p className="text-[10.5px] font-bold tracking-[0.2em] uppercase text-stone">
            {done ? "Confirmación" : "Finalizar compra"}
          </p>
          <button onClick={onClose} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={17} /></button>
        </div>

        {/* pasos */}
        {!done && (
          <div className="flex items-center gap-2 my-4">
            {(["datos", "pago"] as const).map((s, i) => (
              <div key={s} className="flex items-center gap-2">
                <span className={`w-6 h-6 text-[11px] font-bold flex items-center justify-center border ${(step === s ? "bg-ink text-paper border-ink" : step === "link" || step === "directo" ? "bg-maroon text-cream border-maroon" : "border-linedark text-stone")}`}>
                  {step === "link" || step === "directo" ? <I n="check" s={11} /> : i + 1}
                </span>
                <span className={`text-[11.5px] font-semibold ${step === s ? "text-ink" : "text-stone"}`}>{s === "datos" ? "Entrega" : "Pago"}</span>
                {i === 0 && <span className="w-8 h-px bg-linedark" />}
              </div>
            ))}
          </div>
        )}

        {step === "datos" && (
          <div className="fade-in">
            <h3 className="font-display font-medium text-[22px]">¿A dónde lo llevamos?</h3>
            <div className="grid sm:grid-cols-2 gap-4 mt-6">
              <Field label="Nombre / Razón social" full>
                <input value={form.nombre} onChange={(e) => setForm({ ...form, nombre: e.target.value })}
                  className={inp} placeholder="Ej. María Fernanda Jaramillo" />
              </Field>
              <Field label="Cédula o RUC">
                <input value={form.doc} onChange={(e) => setForm({ ...form, doc: e.target.value })}
                  className={`${inp} font-mono`} placeholder="10 o 13 dígitos" inputMode="numeric" />
                {form.doc.trim().length >= 10 && (
                  <span className={`mt-1.5 inline-flex items-center gap-1.5 text-[11px] font-semibold ${docV.valido ? "text-ok" : "text-bad"}`}>
                    <I n={docV.valido ? "check" : "alert"} s={12} />
                    {docV.tipo} · {docV.valido ? "válido" : "revisar"}
                  </span>
                )}
              </Field>
              <Field label="Teléfono / WhatsApp">
                <input value={form.tel} onChange={(e) => setForm({ ...form, tel: e.target.value })}
                  className={inp} placeholder="099 000 0000" />
              </Field>
              <Field label="Ciudad">
                <select value={form.ciudad} onChange={(e) => setForm({ ...form, ciudad: e.target.value })} className={inp}>
                  {CITIES.map((c) => <option key={c}>{c}</option>)}
                </select>
              </Field>
              <Field label="Dirección de entrega">
                <input value={form.dir} onChange={(e) => setForm({ ...form, dir: e.target.value })}
                  className={inp} placeholder="Calle, número, referencia" />
              </Field>
            </div>
            {err && <p className="text-bad text-[12.5px] font-medium mt-4 flex items-center gap-2"><I n="alert" s={14} />{err}</p>}
            <button onClick={validate} className="w-full mt-6 bg-ink text-paper text-[13px] font-semibold py-4 hover:bg-maroon transition-colors flex items-center justify-center gap-2">
              Continuar al pago <I n="arrow" s={15} />
            </button>
          </div>
        )}

        {step === "pago" && (
          <div className="fade-in">
            <h3 className="font-display font-medium text-[22px]">¿Cómo prefieres pagar?</h3>
            <p className="text-[13px] text-ink2 mt-2">Procesado por <strong>PayPhone</strong>. Nunca vemos ni guardamos los datos de tu tarjeta.</p>
            <div className="grid gap-3 mt-6">
              {site.pagoLink && (
                <button onClick={() => setMethod("link")}
                  className={`text-left border p-4 flex items-start gap-3.5 transition-all ${method === "link" ? "border-maroon bg-card shadow-[inset_2px_0_0_#800000]" : "border-linedark hover:border-ink"}`}>
                  <I n="link" s={19} className={method === "link" ? "text-maroon" : "text-stone"} />
                  <span>
                    <span className="block font-semibold text-[14px]">Link de pago de un solo uso</span>
                    <span className="block text-[12.5px] text-stone mt-0.5">Generamos un link privado que expira en 24 h. Lo abres y pagas cuando quieras.</span>
                  </span>
                </button>
              )}
              {site.pagoDirecto && (
                <button onClick={() => setMethod("directo")}
                  className={`text-left border p-4 flex items-start gap-3.5 transition-all ${method === "directo" ? "border-maroon bg-card shadow-[inset_2px_0_0_#800000]" : "border-linedark hover:border-ink"}`}>
                  <I n="card" s={19} className={method === "directo" ? "text-maroon" : "text-stone"} />
                  <span>
                    <span className="block font-semibold text-[14px]">Pago directo en la web</span>
                    <span className="block text-[12.5px] text-stone mt-0.5">Tarjeta de crédito o débito, con opción de diferido. Confirmación inmediata.</span>
                  </span>
                </button>
              )}
            </div>
            <div className="flex items-center justify-between mt-5 px-1 text-[13px]">
              <span className="text-stone">{count} {count === 1 ? "pieza" : "piezas"} · IVA incluido</span>
              <span className="font-display font-medium text-[20px] tnum">{fmt2(total)}</span>
            </div>
            <div className="flex gap-3 mt-5">
              <button onClick={() => setStep("datos")} className="px-5 border border-linedark text-[13px] font-semibold hover:bg-paper2 transition-colors">Atrás</button>
              <button onClick={() => setStep(method)} className="flex-1 bg-ink text-paper text-[13px] font-semibold py-4 hover:bg-maroon transition-colors flex items-center justify-center gap-2">
                {method === "link" ? "Generar mi link" : "Ir a PayPhone"} <I n="arrow" s={15} />
              </button>
            </div>
          </div>
        )}

        {step === "link" && (
          <div className="fade-in text-center py-2">
            <span className="inline-flex w-12 h-12 bg-okbg text-ok items-center justify-center"><I n="link" s={22} /></span>
            <h3 className="font-display font-medium text-[22px] mt-4">Tu link está listo</h3>
            <p className="text-[13px] text-ink2 mt-2">Un solo uso · expira en 24 horas · monto bloqueado</p>
            <div className="mt-5 border border-linedark bg-card p-4 flex items-center justify-between gap-3">
              <code className="text-[13.5px] font-mono font-semibold truncate">pay.bletia.ec/l/{linkCode}</code>
              <CopyInline text={`https://pay.bletia.ec/l/${linkCode}`} />
            </div>
            <div className="mt-5 bg-paper2/70 border border-line p-4 text-left space-y-1.5 text-[12.5px] text-ink2">
              <div className="flex justify-between"><span>Base imponible</span><span className="tnum">{fmt2(base)}</span></div>
              <div className="flex justify-between"><span>IVA 15%</span><span className="tnum">{fmt2(iva)}</span></div>
              <div className="flex justify-between font-semibold text-ink pt-1.5 border-t border-line"><span>Total a pagar</span><span className="tnum">{fmt2(total)}</span></div>
            </div>
            <button onClick={processPay} disabled={payState === "proc"}
              className="w-full mt-5 bg-ink text-paper text-[13px] font-semibold py-4 hover:bg-maroon transition-colors disabled:opacity-70 flex items-center justify-center gap-2.5">
              {payState === "proc" ? (<><Spinner /> Confirmando con PayPhone…</>) : payState === "ok" ? (<><I n="check" s={15} /> Pago aprobado</>) : "Ya abrí el link y pagué"}
            </button>
            <p className="text-[11px] text-stone mt-3">Demo: en producción, PayPhone notifica el pago vía webhook al instante.</p>
          </div>
        )}

        {step === "directo" && (
          <div className="fade-in">
            {/* widget PayPhone simulado */}
            <div className="border border-linedark overflow-hidden">
              <div className="bg-payphone px-4 py-3 flex items-center justify-between">
                <span className="font-display font-bold text-[15px] text-ink tracking-tight">Pay<span className="opacity-70">Phone</span></span>
                <span className="text-[10.5px] font-bold uppercase tracking-wider text-ink/60 flex items-center gap-1.5"><I n="shield" s={12} /> Conexión cifrada</span>
              </div>
              <div className="p-5 bg-card">
                <div className="text-center">
                  <p className="text-[11px] font-bold tracking-[0.18em] uppercase text-stone">BLETIA S.A.S. · Comercio verificado</p>
                  <p className="font-display font-medium text-[30px] mt-2 tnum">{fmt2(total)}</p>
                  <p className="text-[12px] text-stone">Orden {orderCode}</p>
                </div>
                <div className="grid gap-3 mt-5">
                  <input className={inp} placeholder="Número de tarjeta" disabled={payState !== "idle"} />
                  <div className="grid grid-cols-2 gap-3">
                    <input className={inp} placeholder="MM / AA" disabled={payState !== "idle"} />
                    <input className={inp} placeholder="CVC" disabled={payState !== "idle"} />
                  </div>
                  <select className={inp} disabled={payState !== "idle"} defaultValue="0">
                    <option value="0">Contado</option><option value="3">3 meses</option><option value="6">6 meses</option><option value="12">12 meses</option>
                  </select>
                </div>
                <button onClick={processPay} disabled={payState !== "idle"}
                  className="w-full mt-4 bg-ink text-paper text-[13px] font-semibold py-4 hover:bg-maroon transition-colors disabled:opacity-70 flex items-center justify-center gap-2.5">
                  {payState === "proc" ? (<><Spinner /> Procesando pago…</>) : payState === "ok" ? (<><I n="check" s={15} /> Aprobado</>) : `Pagar ${fmt2(total)}`}
                </button>
              </div>
            </div>
            <button onClick={() => setStep("pago")} className="mt-4 text-[12.5px] font-semibold text-stone hover:text-ink u-grow">Elegir otro método</button>
          </div>
        )}

        {done && lastOrder && (
          <div className="fade-in text-center py-4">
            <span className="inline-flex w-14 h-14 bg-okbg text-ok items-center justify-center pulse-ok"><I n="check" s={26} /></span>
            <h3 className="font-display font-medium text-[26px] mt-5">Gracias. Ya es tuyo.</h3>
            <p className="text-[13.5px] text-ink2 mt-2 max-w-[42ch] mx-auto leading-relaxed">
              Tu orden <strong className="tnum">{lastOrder.code}</strong> quedó registrada. Te enviaremos la factura electrónica autorizada por el SRI al correo.
            </p>
            <div className="mt-6 border border-linedark bg-card p-4 text-left">
              <p className="text-[10.5px] font-bold tracking-[0.18em] uppercase text-stone flex items-center gap-2">
                <span className="w-1.5 h-1.5 bg-maroon" /> Seguimiento privado · un solo uso
              </p>
              <div className="flex items-center justify-between gap-3 mt-2.5">
                <code className="text-[13.5px] font-mono font-semibold truncate">{lastOrder.track}</code>
                <CopyInline text={`https://${lastOrder.track}`} />
              </div>
              <p className="text-[11.5px] text-stone mt-2.5">Guárdalo: muestra el avance de taller, transporte y entrega en tiempo real.</p>
            </div>
            <button onClick={onClose} className="w-full mt-6 bg-ink text-paper text-[13px] font-semibold py-4 hover:bg-maroon transition-colors">
              Volver a la tienda
            </button>
          </div>
        )}
      </div>
    </Modal>
  );
}

const inp = "w-full border border-linedark bg-card px-3.5 py-3 text-[13.5px] outline-none focus:border-ink transition-colors placeholder:text-stone/70";

function Field({ label, children, full }: { label: string; children: React.ReactNode; full?: boolean }) {
  return (
    <label className={`block ${full ? "sm:col-span-2" : ""}`}>
      <span className="block text-[11px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">{label}</span>
      {children}
    </label>
  );
}

function CopyInline({ text }: { text: string }) {
  const [ok, setOk] = useState(false);
  return (
    <button onClick={async () => { try { await navigator.clipboard.writeText(text); } catch { /* noop */ } setOk(true); setTimeout(() => setOk(false), 1500); }}
      className={`shrink-0 px-3.5 py-2 text-[12px] font-semibold border transition-colors ${ok ? "bg-okbg text-ok border-ok/30" : "border-ink hover:bg-ink hover:text-paper"}`}>
      {ok ? "Copiado ✓" : "Copiar"}
    </button>
  );
}

function Spinner() {
  return <span className="inline-block w-4 h-4 border-2 border-cream/30 border-t-cream rounded-full animate-spin" />;
}
