import { useEffect, useMemo, useState } from "react";
import { ATRIBUTOS, BLOG_CATEGORIAS, CITIES, IMG, PRODUCTS, VERSION, addEmail, addNotif, articuloUrl, autorDe, crearPedidoCliente, fmt, fmt2, getSesionCliente, loadCMS, loadSecciones, loadSite, minutosLectura, productosActivos, randomCode, saveWebSuscriptor, slugDe, tagTexto, tagUrl, variantesDe, type CuentaCliente, type Product, type SeccionHome } from "./data";
import { useProductsStore } from "./store";
import { detectarDocumento } from "./utils/sri";
import { I, Modal, Reveal } from "./components/ui";
import { AccountModal } from "./components/CustomerAccount";

type CartLine = { id: string; qty: number };
type Step = "datos" | "pago" | "link" | "directo" | "listo";

const CATS = ["Todo", "Sofás", "Sillones", "Mesas", "Sillas", "Centros", "Almacenaje", "Descanso"] as const;

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

  const [cliente, setCliente] = useState<CuentaCliente | null>(() => getSesionCliente());
  const [authOpen, setAuthOpen] = useState(false);

  const [searchOpen, setSearchOpen] = useState(false);
  const [accountOpen, setAccountOpen] = useState(false);
  const [wishOpen, setWishOpen] = useState(false);
  const [wish, setWish] = useState<string[]>(() => {
    try { return JSON.parse(localStorage.getItem("bletia-wish") || "[]"); } catch { return []; }
  });
  const [cuenta, setCuenta] = useState<string>(() => localStorage.getItem("bletia-cuenta") || "");
  const [accForm, setAccForm] = useState({ nombre: "", email: "" });

  const [varSel, setVarSel] = useState<Record<string, string>>({});
  const [nlEmail, setNlEmail] = useState("");
  const [nlOk, setNlOk] = useState(false);

  const [site] = useState(loadSite);
  const [secciones] = useState(loadSecciones);
  const firstIsHero = secciones.find((s) => s.visible)?.tipo === "hero";
  const [posts] = useState(() => loadCMS().filter((p) => p.estado === "Publicado"));
  const [blogCat, setBlogCat] = useState("Todo");
  const postsFiltrados = blogCat === "Todo" ? posts : posts.filter((p) => p.tag === blogCat);
  
  // CONECTADO A POSTGRESQL VIA API
  const { products: storeProducts, fetchProducts, isLoading: productsLoading } = useProductsStore();
  
  useEffect(() => {
    fetchProducts();
  }, [fetchProducts]);
  
  const activos = useMemo(() => storeProducts.filter((p) => p.active), [storeProducts]);
  const destacado = activos.find((p) => p.id === site.destacadoId) ?? activos[0];
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
  const lines = cart.map((l) => ({ ...l, p: activos.find((p) => p.id === l.id)! })).filter((l) => l.p);
  const total = lines.reduce((a, l) => a + l.p.price * l.qty, 0);
  const base = total / 1.15;
  const iva = total - base;

  const catFinal = catActivas.includes(cat) ? cat : "Todo";
  const visibles = useMemo(() => activos.filter((p) => site.colecciones[p.category] !== false), [site, activos]);
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

  const finishOrder = (metodo: "link" | "directo", info: { nombre: string; email: string; ciudad: string; direccion: string }) => {
    const ses = cliente ?? getSesionCliente();
    const email = ses?.email || info.email;
    const nombre = ses?.nombre || info.nombre;
    const items = cart.map((l) => {
      const p = activos.find((x) => x.id === l.id);
      return { id: l.id, name: p?.name ?? "Pieza", qty: l.qty, price: p?.price ?? 0, img: p?.img ?? IMG.detalle };
    });
    const pedido = crearPedidoCliente({
      email, nombre, items, total, estado: 1, ciudad: info.ciudad, direccion: info.direccion, metodo,
    });
    addNotif({ tipo: "pedido", texto: `Nueva orden ${pedido.code} de ${nombre} (${items.reduce((a, i) => a + i.qty, 0)} piezas, ${fmt2(pedido.total)}).`, code: pedido.code });
    addEmail({
      para: email, tipo: "confirmacion", code: pedido.code,
      asunto: `Tu pedido ${pedido.code} está confirmado`,
      preview: `Gracias, ${nombre.split(" ")[0]}. Recibimos tu pago y tu pieza ya está en camino. Sigue tu pedido aquí: bletia.ec/#/pedido/${pedido.tracking}`,
    });
    setCart([]);
    setLastOrder({ code: pedido.code, track: pedido.tracking });
  };

  if (productsLoading) {
    return <div className="min-h-screen flex items-center justify-center"><p>Cargando productos...</p></div>;
  }

  return (
    <div className="min-h-screen bg-paper text-ink font-dash">
      <header className={`fixed top-0 inset-x-0 z-50 transition-all duration-500 ${scrolled ? "bg-paper/90 backdrop-blur-md border-b border-line" : "bg-transparent border-b border-transparent"}`}>
        {site.anuncioActivo && site.anuncioTexto && (
          <div className="bg-maroon text-cream text-center py-2 px-4 text-[12px] font-semibold">
            {site.anuncioTexto}
          </div>
        )}
        <div className="max-w-[1400px] mx-auto px-5 sm:px-8 h-16 flex items-center justify-between gap-6">
          <a href="#top" className="font-display font-semibold tracking-[0.32em] text-[17px] leading-none select-none">
            BLETIA<span className="text-maroon">.</span>
          </a>
          <nav className="hidden md:flex items-center gap-8 text-[13px] font-medium text-ink2">
            {site.menus.tienda.map((m) => (
              <a key={m.label} href={m.url} className="u-grow hover:text-ink">{m.label}</a>
            ))}
          </nav>
          <div className="flex items-center gap-1 sm:gap-1.5">
            <button onClick={() => setSearchOpen(true)} className="p-2.5 hover:bg-paper2 transition-colors" aria-label="Buscar">
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
                <span className="fade-in absolute -top-0.5 -right-0.5 min-w-[17px] h-[17px] px-1 bg-maroon text-cream text-[10px] font-bold flex items-center justify-center tnum">{wish.length}</span>
              )}
            </button>
            <button onClick={() => setCartOpen(true)} className="relative p-2.5 hover:bg-paper2 transition-colors" aria-label="Carrito">
              <I n="cart" s={19} />
              {count > 0 && (
                <span className="fade-in absolute -top-0.5 -right-0.5 min-w-[17px] h-[17px] px-1 bg-maroon text-cream text-[10px] font-bold flex items-center justify-center tnum">{count}</span>
              )}
            </button>
            <button onClick={() => setMenu(true)} className="md:hidden p-2.5 hover:bg-paper2" aria-label="Menú">
              <I n="menu" s={19} />
            </button>
          </div>
        </div>
      </header>

      {menu && (
        <div className="fixed inset-0 z-[80] bg-paper flex flex-col">
          <div className="h-16 px-5 flex items-center justify-between border-b border-line">
            <span className="font-display font-semibold tracking-[0.32em]">BLETIA<span className="text-maroon">.</span></span>
            <button onClick={() => setMenu(false)} className="p-2.5 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={18} /></button>
          </div>
          <nav className="flex flex-col p-8 gap-6 font-display text-3xl">
            {site.menus.tienda.map((m) => (
              <a key={m.label} href={m.url} onClick={() => setMenu(false)} className="hover:text-maroon transition-colors">{m.label}</a>
            ))}
          </nav>
        </div>
      )}

      <section id="top" className={`relative ${firstIsHero ? "" : site.anuncioActivo && site.anuncioTexto ? "pt-[6.35rem]" : "pt-16"}`}>
        <div className="max-w-[1400px] mx-auto px-5 sm:px-8">
          <div className="grid lg:grid-cols-12 gap-8 lg:gap-0 items-stretch min-h-[calc(100vh-4rem)]">
            <div className="lg:col-span-7 flex flex-col justify-center py-12 lg:py-0 lg:pr-14 grain">
              <Reveal>
                <h1 className="font-display font-medium text-[clamp(2.7rem,6.2vw,5.4rem)] leading-[1.0] tracking-[-0.015em]">
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
            </div>
            <div className="lg:col-span-5 relative lg:min-h-[calc(100vh-4rem)]">
              <div className="relative h-[420px] sm:h-[520px] lg:h-full overflow-hidden bg-paper2">
                <img src={IMG.hero} alt="Mueblería BLETIA" className="kenburns w-full h-full object-cover" />
                {destacado && (
                  <button onClick={() => setQuick(destacado)}
                    className="group absolute bottom-5 left-5 right-5 sm:right-auto sm:w-[300px] bg-card/95 backdrop-blur border border-line p-4 flex items-center gap-4 text-left hover:border-maroon/50 transition-all duration-300 hover:-translate-y-0.5">
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
                )}
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="coleccion" className="max-w-[1400px] mx-auto px-5 sm:px-8 py-20 sm:py-28 scroll-mt-16">
        <Reveal>
          <div className="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-12">
            <div>
              <h2 className="font-display font-medium text-[clamp(1.9rem,3.6vw,3rem)] leading-[1.05]">
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
        {shown.length === 0 ? (
          <Reveal>
            <div className="border border-dashed border-linedark bg-card/50 py-24 text-center">
              <p className="font-display font-medium text-[22px]">Nuestra colección está en camino.</p>
              <p className="text-[13.5px] text-stone mt-2.5">Las piezas se publican aquí en cuanto salen del taller.</p>
            </div>
          </Reveal>
        ) : (
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-14">
            {shown.map((p, i) => (
              <Reveal key={p.id} delay={(i % 3) * 90}>
                <article className="group cursor-pointer" onClick={() => setQuick(p)}>
                  <div className="relative overflow-hidden bg-paper2 aspect-[4/5]">
                    <img src={p.img} alt={p.name} loading="lazy"
                      className="w-full h-full object-cover transition-transform duration-[900ms] ease-[cubic-bezier(0.2,0.7,0.2,1)] group-hover:scale-[1.05]" />
                    <span className={`absolute top-3.5 left-3.5 text-[10px] font-bold tracking-[0.16em] uppercase px-2.5 py-1.5 flex items-center gap-1.5 ${(p as any).origin === "Taller BLETIA" ? "bg-card/95 text-ink" : "bg-ink/85 text-cream"}`}>
                      {(p as any).origin === "Taller BLETIA" && <span className="w-1.5 h-1.5 bg-maroon" />}
                      {(p as any).origin === "Taller BLETIA" ? "Taller" : "Curaduría"}
                    </span>
                    {p.stock <= 3 && p.stock > 0 && (
                      <span className="absolute top-3.5 right-3.5 text-[10px] font-bold tracking-[0.14em] uppercase px-2.5 py-1.5 bg-card/95 text-warn">
                        Últimas {p.stock}
                      </span>
                    )}
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
                      <h3 className="font-display font-medium text-[19px] mt-1 group-hover:text-maroon transition-colors duration-300">{p.name}</h3>
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
        )}
      </section>

      <section id="taller" className="bg-coal text-cream scroll-mt-16">
        <div className="max-w-[1400px] mx-auto px-5 sm:px-8 py-20 sm:py-28">
          <div className="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            <Reveal>
              <div className="relative overflow-hidden">
                <img src={IMG.taller} alt="Maestro del taller BLETIA trabajando nogal" loading="lazy"
                  className="w-full h-[420px] sm:h-[520px] object-cover transition-transform duration-[1200ms] hover:scale-[1.03]" />
                <span className="absolute bottom-4 left-4 bg-coal/85 backdrop-blur text-cream text-[10.5px] font-semibold tracking-[0.2em] uppercase px-3 py-2 flex items-center gap-2">
                  <span className="w-1.5 h-1.5 bg-maroon pulse-maroon" /> Taller BLETIA · Cuenca
                </span>
              </div>
            </Reveal>
            <div>
              <Reveal>
                <h2 className="font-display font-medium text-[clamp(1.9rem,3.6vw,3rem)] leading-[1.05]">
                  De la tabla<br />al objeto.
                </h2>
              </Reveal>
              <Reveal delay={150}>
                <p className="text-cream/70 text-[15px] leading-relaxed max-w-[52ch] mt-6">
                  Una parte de la colección nace aquí: madera certificada, ensambles de espiga y acabados a mano.
                  Cada pieza de taller sale numerada, firmada y con su historia de fabricación trazable de punta a punta.
                </p>
              </Reveal>
            </div>
          </div>
        </div>
      </section>

      <footer className="bg-ink text-cream">
        <div className="max-w-[1400px] mx-auto px-5 sm:px-8 pt-16 pb-8">
          <div className="grid md:grid-cols-12 gap-10">
            <div className="md:col-span-5">
              <p className="font-display font-semibold tracking-[0.32em] text-xl">BLETIA<span className="text-maroon">.</span></p>
              <p className="text-cream/60 text-[13.5px] leading-relaxed mt-5 max-w-[38ch]">
                Mueblería de lujo minimalista. Fabricamos en Cuenca, entregamos en todo el Ecuador y cobramos como debe ser: seguro.
              </p>
            </div>
            <div className="md:col-span-3">
              <p className="text-[11px] font-bold tracking-[0.2em] uppercase text-cream/40 mb-4">Tienda</p>
              {site.menus.tienda.map((m) => (
                <a key={m.label} href={m.url} className="block text-[13.5px] text-cream/75 hover:text-cream py-1.5 u-grow w-fit">{m.label}</a>
              ))}
            </div>
            <div className="md:col-span-4" id="legal">
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
          <div className="border-t border-cream/15 mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11.5px] text-cream/40">
            <span>© 2026 BLETIA. Hecho en Ecuador, a mano y a tiempo.</span>
            <span className="flex items-center gap-2"><span className="w-1.5 h-1.5 bg-maroon" /> bletia.ec <span className="text-cream/25 tnum">· {VERSION}</span></span>
          </div>
        </div>
      </footer>
    </div>
  );
}
