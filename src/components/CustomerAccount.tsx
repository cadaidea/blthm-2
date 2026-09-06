import { useState } from "react";
import {
  PRODUCTS, TRACK_STATES, addEmail, avanzarPedidoCliente, buscarCuenta, crearCuenta,
  fmt2, loadCustomProducts, loadPedidosCliente, getSesionCliente, setSesionCliente,
  productosActivos,
  type CuentaCliente, type PedidoCliente,
} from "../data";
import { I, Modal, toast } from "./ui";
import { Card, Chip, Td, Th, btnDark } from "./panel/pui";

/* buscar un producto por id (catálogo + fichas propias del dueño) */
const buscarProd = (id: string) =>
  productosActivos().find((p) => p.id === id) ??
  [...PRODUCTS, ...loadCustomProducts()].find((p) => p.id === id);

/* =========================================================
   MODAL DE ACCESO (login / registro)
   ========================================================= */
export function AccountModal({ open, onClose, onLogged }: { open: boolean; onClose: () => void; onLogged: (c: CuentaCliente) => void }) {
  const [mode, setMode] = useState<"login" | "registro">("login");
  const [f, setF] = useState({ nombre: "", email: "", pass: "", telefono: "" });
  const [err, setErr] = useState("");

  const entrar = () => {
    const c = buscarCuenta(f.email.trim(), f.pass);
    if (!c) return setErr("Correo o contraseña incorrectos.");
    setSesionCliente(c.id);
    toast(`Hola de nuevo, ${c.nombre.split(" ")[0]}.`, "ok");
    onLogged(c); onClose(); setErr("");
  };

  const registrar = () => {
    if (f.nombre.trim().length < 2) return setErr("Escribe tu nombre completo.");
    if (!/^\S+@\S+\.\S+$/.test(f.email.trim())) return setErr("Ese correo no parece válido.");
    if (f.pass.length < 4) return setErr("La contraseña debe tener al menos 4 caracteres.");
    const c = crearCuenta(f.nombre.trim(), f.email.trim(), f.pass, f.telefono.trim() || undefined);
    if (!c) return setErr("Ya existe una cuenta con ese correo. Inicia sesión.");
    setSesionCliente(c.id);
    addEmail({ para: c.email, asunto: "Bienvenido a BLETIA", preview: `Hola ${c.nombre.split(" ")[0]}: tu cuenta está lista. Guarda tus deseos, sigue tus pedidos y paga más rápido.`, tipo: "confirmacion" });
    toast(`Cuenta creada. Bienvenido, ${c.nombre.split(" ")[0]}.`, "ok");
    onLogged(c); onClose(); setErr(""); setF({ nombre: "", email: "", pass: "", telefono: "" });
  };

  return (
    <Modal open={open} onClose={onClose} w="max-w-md">
      <div className="p-7 sm:p-9">
        <div className="flex items-start justify-between">
          <div>
            <p className="font-display font-semibold tracking-[0.3em] text-[15px]">BLETIA<span className="text-maroon">.</span></p>
            <h2 className="font-display font-medium text-[26px] leading-tight mt-3">
              {mode === "login" ? "Tu espacio personal." : "Crea tu cuenta."}
            </h2>
          </div>
          <button onClick={onClose} className="p-2 hover:bg-paper2 transition-colors" aria-label="Cerrar"><I n="close" s={17} /></button>
        </div>
        <p className="text-[13px] text-ink2 mt-2 leading-relaxed">
          {mode === "login"
            ? "Guarda tus deseos, sigue tus pedidos y paga en segundos."
            : "Una cuenta para seguir cada pieza, del taller a tu puerta."}
        </p>

        <div className="grid gap-3.5 mt-6">
          {mode === "registro" && (
            <input value={f.nombre} onChange={(e) => setF({ ...f, nombre: e.target.value })} className={inpPub} placeholder="Nombre completo" />
          )}
          <input value={f.email} onChange={(e) => setF({ ...f, email: e.target.value })} className={inpPub} placeholder="Correo electrónico" type="email" />
          <input value={f.pass} onChange={(e) => setF({ ...f, pass: e.target.value })} className={inpPub} placeholder="Contraseña" type="password"
            onKeyDown={(e) => e.key === "Enter" && (mode === "login" ? entrar() : registrar())} />
          {mode === "registro" && (
            <input value={f.telefono} onChange={(e) => setF({ ...f, telefono: e.target.value })} className={inpPub} placeholder="Teléfono (opcional)" />
          )}
        </div>

        {err && <p className="text-bad text-[12.5px] font-medium mt-3.5 flex items-center gap-2"><I n="alert" s={14} />{err}</p>}

        <button onClick={mode === "login" ? entrar : registrar}
          className="w-full mt-5 bg-ink text-paper text-[13px] font-semibold py-4 hover:bg-maroon transition-colors flex items-center justify-center gap-2">
          {mode === "login" ? "Entrar a mi cuenta" : "Crear mi cuenta"} <I n="arrow" s={15} />
        </button>

        <p className="text-center text-[12.5px] text-stone mt-4">
          {mode === "login" ? "¿Aún no tienes cuenta? " : "¿Ya tienes cuenta? "}
          <button onClick={() => { setMode(mode === "login" ? "registro" : "login"); setErr(""); }}
            className="font-semibold text-ink u-grow">{mode === "login" ? "Crea una aquí" : "Inicia sesión"}</button>
        </p>
      </div>
    </Modal>
  );
}

const inpPub = "w-full border border-linedark bg-card px-4 py-3.5 text-[13.5px] outline-none focus:border-ink transition-colors placeholder:text-stone/60";

/* =========================================================
   PÁGINA "MI CUENTA"  (#/cuenta)
   ========================================================= */
type Tab = "pedidos" | "deseos" | "carrito" | "perfil";

export function CuentaPage() {
  const [sesion, setSesion] = useState<CuentaCliente | null>(() => getSesionCliente());
  const [tab, setTab] = useState<Tab>("pedidos");
  const [showLogin, setShowLogin] = useState(false);
  const [pedidos, setPedidos] = useState<PedidoCliente[]>(() => loadPedidosCliente());
  const [wish, setWish] = useState<string[]>(() => { try { return JSON.parse(localStorage.getItem("bletia-wish") || "[]"); } catch { return []; } });
  const [cart, setCart] = useState<{ id: string; qty: number }[]>(() => { try { return JSON.parse(localStorage.getItem("bletia-cart") || "[]"); } catch { return []; } });
  const [expandido, setExpandido] = useState<string | null>(null);

  const guardarWish = (w: string[]) => { setWish(w); localStorage.setItem("bletia-wish", JSON.stringify(w)); };
  const guardarCart = (c: { id: string; qty: number }[]) => { setCart(c); localStorage.setItem("bletia-cart", JSON.stringify(c)); };

  const salir = () => { setSesionCliente(null); setSesion(null); toast("Sesión cerrada. Vuelve pronto.", "info"); };

  const avanzar = (id: string) => {
    const p = avanzarPedidoCliente(id);
    if (p) { setPedidos(loadPedidosCliente()); toast(`Pedido ${p.code} → ${TRACK_STATES[p.estado]}`, "ok"); }
  };

  if (!sesion) {
    return (
      <div className="min-h-screen bg-paper text-ink font-dash">
        <AccountModal open={showLogin} onClose={() => setShowLogin(false)} onLogged={setSesion} />
        <TopBar />
        <div className="max-w-[620px] mx-auto px-5 py-24 text-center">
          <span className="inline-flex w-16 h-16 bg-paper2 items-center justify-center mb-6"><I n="user" s={28} className="text-stone" /></span>
          <h1 className="font-display font-medium text-[clamp(1.8rem,3.4vw,2.6rem)] leading-tight">Tu espacio te espera.</h1>
          <p className="text-[14px] text-ink2 mt-3 leading-relaxed">Inicia sesión o crea tu cuenta para guardar deseos, armar tu carrito y seguir tus pedidos en tiempo real.</p>
          <button onClick={() => setShowLogin(true)} className="mt-7 inline-flex items-center gap-2.5 bg-ink text-paper px-8 py-4 text-[13px] font-semibold hover:bg-maroon transition-colors">
            Entrar o crear cuenta <I n="arrow" s={15} />
          </button>
        </div>
      </div>
    );
  }

  const misPedidos = pedidos.filter((p) => p.email.toLowerCase() === sesion.email.toLowerCase());
  const wishProds = wish.map(buscarProd).filter(Boolean);
  const cartLines = cart.map((l) => ({ ...l, p: buscarProd(l.id) })).filter((l) => l.p);
  const cartTotal = cartLines.reduce((a, l) => a + (l.p!.price * l.qty), 0);

  return (
    <div className="min-h-screen bg-paper text-ink font-dash">
      <TopBar />
      <div className="max-w-[1100px] mx-auto px-5 sm:px-8 py-12">
        {/* saludo */}
        <div className="flex flex-wrap items-end justify-between gap-4">
          <div>
            <p className="text-[12px] font-semibold tracking-[0.2em] uppercase text-stone">Mi cuenta</p>
            <h1 className="font-display font-medium text-[clamp(1.8rem,3.4vw,2.6rem)] leading-tight mt-2">Hola, {sesion.nombre.split(" ")[0]}.</h1>
          </div>
          <a href="#/" className="text-[13px] font-semibold text-ink2 u-grow flex items-center gap-2"><I n="back" s={14} /> Seguir comprando</a>
        </div>

        {/* tabs */}
        <div className="flex flex-wrap gap-2 mt-8 border-b border-line pb-px">
          {([
            ["pedidos", "Mis pedidos", misPedidos.length],
            ["deseos", "Mis deseos", wish.length],
            ["carrito", "Mi carrito", cart.length],
            ["perfil", "Mi perfil", null],
          ] as [Tab, string, number | null][]).map(([id, label, count]) => (
            <button key={id} onClick={() => setTab(id)}
              className={`px-4 py-3 text-[13px] font-semibold border-b-2 -mb-px transition-colors flex items-center gap-2 ${tab === id ? "border-maroon text-ink" : "border-transparent text-stone hover:text-ink"}`}>
              {label}
              {count !== null && count > 0 && <span className={`text-[10.5px] font-bold px-1.5 py-0.5 tnum ${tab === id ? "bg-maroon text-cream" : "bg-paper2 text-stone"}`}>{count}</span>}
            </button>
          ))}
        </div>

        <div className="mt-8">
          {tab === "pedidos" && (
            misPedidos.length === 0 ? (
              <EmptyState icon="box" titulo="Aún no tienes pedidos." texto="Cuando compres una pieza, aquí podrás seguir su camino del taller a tu puerta." cta="Explorar la colección" href="#/" />
            ) : (
              <div className="grid gap-5">
                {misPedidos.map((p) => (
                  <div key={p.id} className="border border-line bg-card">
                    <button onClick={() => setExpandido(expandido === p.id ? null : p.id)}
                      className="w-full flex flex-wrap items-center gap-4 px-5 py-4 text-left hover:bg-paper2/50 transition-colors">
                      <div className="flex -space-x-2">
                        {p.items.slice(0, 3).map((it) => (
                          <span key={it.id} className="w-11 h-13 h-14 w-11 border-2 border-card bg-paper2 overflow-hidden shrink-0">
                            <img src={it.img} alt="" className="w-full h-full object-cover" />
                          </span>
                        ))}
                      </div>
                      <div className="flex-1 min-w-[160px]">
                        <p className="font-mono text-[12px] font-semibold">{p.code}</p>
                        <p className="text-[12px] text-stone mt-0.5">{p.fecha} · {p.items.reduce((a, i) => a + i.qty, 0)} piezas</p>
                      </div>
                      <p className="font-semibold tnum">{fmt2(p.total)}</p>
                      <span className={`text-[11px] font-bold px-2.5 py-1.5 uppercase tracking-wide ${estadoColor(p.estado)}`}>{TRACK_STATES[p.estado]}</span>
                      <I n="chev-r" s={15} className={`text-stone transition-transform ${expandido === p.id ? "rotate-90" : ""}`} />
                    </button>

                    {expandido === p.id && (
                      <div className="px-5 pb-5 pt-1 border-t border-line fade-in">
                        <Timeline estado={p.estado} historial={p.historial} />
                        <div className="mt-5 grid sm:grid-cols-2 gap-5">
                          <div>
                            <p className="text-[11px] font-bold tracking-[0.16em] uppercase text-stone mb-2.5">Piezas</p>
                            {p.items.map((it) => (
                              <div key={it.id} className="flex items-center gap-3 py-1.5">
                                <span className="w-9 h-11 bg-paper2 overflow-hidden shrink-0"><img src={it.img} alt="" className="w-full h-full object-cover" /></span>
                                <span className="flex-1 text-[13px]">{it.name} <span className="text-stone">× {it.qty}</span></span>
                                <span className="text-[12.5px] tnum text-ink2">{fmt2(it.price * it.qty)}</span>
                              </div>
                            ))}
                          </div>
                          <div>
                            <p className="text-[11px] font-bold tracking-[0.16em] uppercase text-stone mb-2.5">Entrega</p>
                            <p className="text-[13px] leading-relaxed">{p.direccion}<br />{p.ciudad}</p>
                            <p className="text-[12px] text-stone mt-2">Pago: {p.metodo === "link" ? "Link PayPhone" : "Tarjeta (directo)"}</p>
                            <a href={`#/pedido/${p.tracking}`} className="mt-3 inline-flex items-center gap-2 text-[12.5px] font-semibold text-maroon u-grow">
                              <I n="eye" s={13} /> Página de seguimiento
                            </a>
                          </div>
                        </div>
                        {/* solo visible si hay sesión de admin en el mismo navegador (demo de gestión) */}
                        {p.estado < TRACK_STATES.length - 1 && (
                          <button onClick={() => avanzar(p.id)} className="mt-5 text-[12px] font-semibold border border-linedark px-4 py-2.5 hover:bg-ink hover:text-paper transition-colors">
                            [Demo personal] Avanzar a: {TRACK_STATES[p.estado + 1]}
                          </button>
                        )}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            )
          )}

          {tab === "deseos" && (
            wishProds.length === 0 ? (
              <EmptyState icon="heart" titulo="Tu lista de deseos está vacía." texto="Toca el corazón en cualquier pieza de la colección para guardarla aquí." cta="Ver la colección" href="#/" />
            ) : (
              <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {wishProds.map((p) => (
                  <div key={p!.id} className="group border border-line bg-card">
                    <a href={`#/producto/${p!.slug || p!.name}`} className="block aspect-[4/3] overflow-hidden bg-paper2">
                      <img src={p!.img} alt={p!.name} className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-[1.05]" />
                    </a>
                    <div className="p-4">
                      <p className="font-display font-medium text-[16px] leading-tight">{p!.name}</p>
                      <p className="text-[12px] text-stone mt-0.5">{p!.material}</p>
                      <div className="flex items-center justify-between mt-3.5">
                        <p className="font-semibold tnum">{fmt2(p!.price)}</p>
                        <div className="flex gap-1.5">
                          <button onClick={() => guardarWish(wish.filter((w) => w !== p!.id))} className="p-2 text-stone hover:text-bad transition-colors" title="Quitar"><I n="heart-fill" s={15} /></button>
                          <button onClick={() => { agregarCarrito(p!.id); guardarWish(wish.filter((w) => w !== p!.id)); }}
                            className="text-[11.5px] font-semibold px-3 py-2 bg-ink text-paper hover:bg-maroon transition-colors flex items-center gap-1.5"><I n="cart" s={12} /> Al carrito</button>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )
          )}

          {tab === "carrito" && (
            cartLines.length === 0 ? (
              <EmptyState icon="cart" titulo="Tu carrito está vacío." texto="Agrega piezas desde la colección y vuelve aquí para pagarlas." cta="Ir a la tienda" href="#/" />
            ) : (
              <div className="grid lg:grid-cols-[1fr_320px] gap-8">
                <div className="grid gap-4">
                  {cartLines.map((l) => (
                    <div key={l.id} className="flex items-center gap-4 border border-line bg-card p-3.5">
                      <span className="w-16 h-20 bg-paper2 overflow-hidden shrink-0"><img src={l.p!.img} alt="" className="w-full h-full object-cover" /></span>
                      <div className="flex-1 min-w-0">
                        <p className="font-display font-medium text-[15px]">{l.p!.name}</p>
                        <p className="text-[11.5px] text-stone">{l.p!.material}</p>
                      </div>
                      <div className="flex items-center border border-linedark">
                        <button onClick={() => cambiarQty(l.id, l.qty - 1)} className="px-2.5 py-1.5 hover:bg-paper2"><I n="minus" s={12} /></button>
                        <span className="px-2 text-[12.5px] font-semibold tnum">{l.qty}</span>
                        <button onClick={() => cambiarQty(l.id, l.qty + 1)} className="px-2.5 py-1.5 hover:bg-paper2"><I n="plus" s={12} /></button>
                      </div>
                      <p className="font-semibold tnum w-20 text-right">{fmt2(l.p!.price * l.qty)}</p>
                      <button onClick={() => guardarCart(cart.filter((c) => c.id !== l.id))} className="p-2 text-stone hover:text-bad"><I n="close" s={14} /></button>
                    </div>
                  ))}
                </div>
                <div className="border border-line bg-card p-5 h-fit sticky top-24">
                  <p className="font-display font-medium text-[18px]">Resumen</p>
                  <div className="space-y-1.5 mt-4 text-[13px]">
                    <div className="flex justify-between text-ink2"><span>Base imponible</span><span className="tnum">{fmt2(cartTotal / 1.15)}</span></div>
                    <div className="flex justify-between text-ink2"><span>IVA 15%</span><span className="tnum">{fmt2(cartTotal - cartTotal / 1.15)}</span></div>
                    <div className="flex justify-between font-display font-medium text-[18px] pt-2 border-t border-line"><span>Total</span><span className="tnum">{fmt2(cartTotal)}</span></div>
                  </div>
                  <a href="#/" className="mt-5 w-full bg-ink text-paper text-[13px] font-semibold py-4 hover:bg-maroon transition-colors flex items-center justify-center gap-2">
                    Ir a la tienda a pagar <I n="arrow" s={15} />
                  </a>
                  <p className="text-[11px] text-stone text-center mt-3 flex items-center justify-center gap-1.5"><I n="shield" s={12} /> Pago seguro con PayPhone</p>
                </div>
              </div>
            )
          )}

          {tab === "perfil" && (
            <div className="max-w-[520px] border border-line bg-card p-6">
              <p className="font-display font-medium text-[18px]">Mis datos</p>
              <div className="grid gap-4 mt-5">
                <Campo label="Nombre" valor={sesion.nombre} />
                <Campo label="Correo" valor={sesion.email} />
                <Campo label="Teléfono" valor={sesion.telefono || "No registrado"} />
                <Campo label="Cliente desde" valor={sesion.creado} />
              </div>
              <button onClick={salir} className="mt-6 text-[12.5px] font-semibold border border-linedark px-5 py-3 hover:bg-bad hover:text-paper hover:border-bad transition-colors flex items-center gap-2">
                <I n="back" s={14} /> Cerrar sesión
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );

  function agregarCarrito(id: string) {
    const ex = cart.find((c) => c.id === id);
    guardarCart(ex ? cart.map((c) => (c.id === id ? { ...c, qty: Math.min(c.qty + 1, 12) } : c)) : [...cart, { id, qty: 1 }]);
    toast("Agregado al carrito.", "ok");
  }
  function cambiarQty(id: string, qty: number) {
    guardarCart(qty <= 0 ? cart.filter((c) => c.id !== id) : cart.map((c) => (c.id === id ? { ...c, qty: Math.min(qty, 12) } : c)));
  }
}

function Campo({ label, valor }: { label: string; valor: string }) {
  return (
    <div className="flex items-center justify-between gap-4 border-b border-line pb-3">
      <span className="text-[12px] font-semibold text-stone">{label}</span>
      <span className="text-[13.5px] font-medium text-right">{valor}</span>
    </div>
  );
}

function estadoColor(estado: number) {
  if (estado === 0) return "bg-warnbg text-warn";
  if (estado === TRACK_STATES.length - 1) return "bg-okbg text-ok";
  return "bg-paper2 text-ink2";
}

/* =========================================================
   LÍNEA DE TIEMPO DE SEGUIMIENTO
   ========================================================= */
export function Timeline({ estado, historial }: { estado: number; historial: { estado: number; fecha: string }[] }) {
  const fechaDe = (e: number) => historial.find((h) => h.estado === e)?.fecha;
  return (
    <div className="mt-4">
      <div className="flex items-center">
        {TRACK_STATES.map((_, i) => (
          <div key={i} className="flex items-center flex-1 last:flex-none">
            <span className={`w-7 h-7 shrink-0 flex items-center justify-center border-2 text-[11px] font-bold transition-colors ${i < estado ? "bg-ink text-paper border-ink" : i === estado ? "bg-maroon text-cream border-maroon" : "bg-card text-stone border-linedark"}`}>
              {i < estado ? <I n="check" s={12} /> : i + 1}
            </span>
            {i < TRACK_STATES.length - 1 && <span className={`h-0.5 flex-1 mx-1 ${i < estado ? "bg-ink" : "bg-linedark"}`} />}
          </div>
        ))}
      </div>
      <div className="flex justify-between mt-2.5">
        {TRACK_STATES.map((s, i) => (
          <div key={s} className={`text-center ${i === 0 ? "text-left" : i === TRACK_STATES.length - 1 ? "text-right" : ""} flex-1`}>
            <p className={`text-[10.5px] font-semibold leading-tight ${i === estado ? "text-maroon" : i < estado ? "text-ink" : "text-stone"}`}>{s}</p>
            {fechaDe(i) && <p className="text-[9.5px] text-stone tnum mt-0.5">{fechaDe(i)}</p>}
          </div>
        ))}
      </div>
    </div>
  );
}

/* =========================================================
   PÁGINA PÚBLICA DE SEGUIMIENTO  (#/pedido/{codigo})
   ========================================================= */
export function PedidoTrackingPage({ codigo }: { codigo: string }) {
  const pedido = loadPedidosCliente().find((p) => p.tracking === codigo || p.code === codigo);

  return (
    <div className="min-h-screen bg-paper text-ink font-dash">
      <TopBar />
      <div className="max-w-[760px] mx-auto px-5 sm:px-8 py-14">
        {!pedido ? (
          <EmptyState icon="search" titulo="No encontramos ese pedido." texto="Revisa el código que recibiste en tu correo, o escríbenos y te ayudamos a rastrearlo." cta="Ir a la tienda" href="#/" />
        ) : (
          <div>
            <p className="text-[12px] font-semibold tracking-[0.2em] uppercase text-stone">Seguimiento de pedido</p>
            <h1 className="font-display font-medium text-[clamp(1.8rem,3.4vw,2.6rem)] leading-tight mt-2">{pedido.code}</h1>
            <p className="text-[13.5px] text-ink2 mt-2">Hola {pedido.nombre.split(" ")[0]}: así va tu pieza, del taller a tu puerta.</p>

            <div className="border border-line bg-card px-6 py-6 mt-8">
              <div className="flex items-center justify-between mb-2">
                <p className="font-display font-medium text-[17px]">Estado actual</p>
                <span className={`text-[11px] font-bold px-2.5 py-1.5 uppercase tracking-wide ${estadoColor(pedido.estado)}`}>{TRACK_STATES[pedido.estado]}</span>
              </div>
              <Timeline estado={pedido.estado} historial={pedido.historial} />
            </div>

            <div className="grid sm:grid-cols-2 gap-6 mt-6">
              <div className="border border-line bg-card p-5">
                <p className="text-[11px] font-bold tracking-[0.16em] uppercase text-stone mb-3">Piezas ({pedido.items.reduce((a, i) => a + i.qty, 0)})</p>
                {pedido.items.map((it) => (
                  <div key={it.id} className="flex items-center gap-3 py-2 border-b border-line last:border-0">
                    <span className="w-10 h-12 bg-paper2 overflow-hidden shrink-0"><img src={it.img} alt="" className="w-full h-full object-cover" /></span>
                    <span className="flex-1 text-[13px]">{it.name} <span className="text-stone">× {it.qty}</span></span>
                    <span className="text-[12.5px] tnum">{fmt2(it.price * it.qty)}</span>
                  </div>
                ))}
                <p className="flex justify-between font-semibold tnum mt-3 pt-2 border-t border-line text-[14px]"><span>Total (IVA incl.)</span><span>{fmt2(pedido.total)}</span></p>
              </div>
              <div className="border border-line bg-card p-5">
                <p className="text-[11px] font-bold tracking-[0.16em] uppercase text-stone mb-3">Entrega guante blanco</p>
                <p className="text-[13px] leading-relaxed">{pedido.direccion}<br />{pedido.ciudad}</p>
                <p className="text-[12px] text-stone mt-3 flex items-center gap-2"><I n="truck" s={14} /> Armamos en sitio y retiramos el embalaje.</p>
                <p className="text-[12px] text-stone mt-1.5 flex items-center gap-2"><I n="card" s={14} /> Pago: {pedido.metodo === "link" ? "Link PayPhone" : "Tarjeta"}</p>
                <a href="#/" className="mt-5 inline-flex items-center gap-2 text-[12.5px] font-semibold text-maroon u-grow"><I n="back" s={13} /> Volver a la tienda</a>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

/* ---------- barra superior compartida ---------- */
function TopBar() {
  return (
    <header className="border-b border-line bg-paper/90 backdrop-blur sticky top-0 z-40">
      <div className="max-w-[1200px] mx-auto px-5 sm:px-8 h-16 flex items-center justify-between">
        <a href="#/" className="font-display font-semibold tracking-[0.32em] text-[17px]">BLETIA<span className="text-maroon">.</span></a>
        <a href="#/" className="text-[12.5px] font-semibold text-ink2 u-grow flex items-center gap-2"><I n="back" s={14} /> Volver a la tienda</a>
      </div>
    </header>
  );
}

/* ---------- estado vacío reutilizable ---------- */
function EmptyState({ icon, titulo, texto, cta, href }: { icon: "box" | "heart" | "cart" | "search"; titulo: string; texto: string; cta: string; href: string }) {
  return (
    <div className="border border-dashed border-linedark bg-card/50 py-20 text-center px-6">
      <span className="inline-flex w-14 h-14 bg-paper2 items-center justify-center mb-5"><I n={icon} s={24} className="text-stone" /></span>
      <h3 className="font-display font-medium text-[22px]">{titulo}</h3>
      <p className="text-[13.5px] text-stone mt-2 max-w-[44ch] mx-auto leading-relaxed">{texto}</p>
      <a href={href} className="mt-6 inline-flex items-center gap-2 bg-ink text-paper px-7 py-3.5 text-[13px] font-semibold hover:bg-maroon transition-colors">{cta} <I n="arrow" s={14} /></a>
    </div>
  );
}

/* =========================================================
   GESTIÓN DE PEDIDOS WEB (para el personal, dentro del panel)
   Revisar cada orden, seguir el proceso y avanzar el estado.
   Al avanzar, se "envía" el correo del nuevo estado al cliente.
   ========================================================= */
export function AdminPedidosWeb() {
  const [pedidos, setPedidos] = useState<PedidoCliente[]>(() => loadPedidosCliente());
  const [sel, setSel] = useState<string | null>(null);

  const refrescar = () => setPedidos(loadPedidosCliente());
  const pedidoSel = pedidos.find((p) => p.id === sel) || null;

  const avanzar = (p: PedidoCliente) => {
    const nuevo = avanzarPedidoCliente(p.id);
    if (!nuevo) return;
    addEmail({
      para: nuevo.email, tipo: "estado", code: nuevo.code,
      asunto: `Tu pedido ${nuevo.code} ahora está: ${TRACK_STATES[nuevo.estado]}`,
      preview: `Hola ${nuevo.nombre.split(" ")[0]}: tu pedido avanzó a «${TRACK_STATES[nuevo.estado]}». Sigue el detalle aquí: bletia.ec/#/pedido/${nuevo.tracking}`,
    });
    refrescar();
    toast(`Pedido ${nuevo.code} → ${TRACK_STATES[nuevo.estado]} · correo enviado al cliente`, "ok");
  };

  const chipEstado = (e: number) =>
    e === 0 ? <Chip tone="warn" dot>{TRACK_STATES[e]}</Chip>
      : e === TRACK_STATES.length - 1 ? <Chip tone="ok" dot>{TRACK_STATES[e]}</Chip>
      : <Chip tone="maroon" dot>{TRACK_STATES[e]}</Chip>;

  return (
    <Card className="overflow-hidden mt-6">
      <div className="flex items-center justify-between px-5 py-4 border-b border-line">
        <div>
          <h3 className="font-bold text-[15px] tracking-tight">Pedidos de la tienda web</h3>
          <p className="text-[12px] text-stone mt-0.5">Cuentas de clientes reales. Avanza el proceso y el cliente recibe el correo de cada estado.</p>
        </div>
        <span className="text-[11px] font-bold px-2.5 py-1.5 bg-paper2 text-ink2 tnum">{pedidos.length} pedidos</span>
      </div>

      {pedidos.length === 0 ? (
        <div className="py-14 text-center px-6">
          <p className="font-display font-medium text-[19px]">Aún no hay pedidos de la web.</p>
          <p className="text-[12.5px] text-stone mt-2 max-w-[44ch] mx-auto">Cuando un cliente compre en bletia.ec, su orden aparecerá aquí y en la campana de notificaciones.</p>
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full min-w-[720px]">
            <thead><tr><Th>Pedido</Th><Th>Cliente</Th><Th>Piezas</Th><Th>Total</Th><Th>Estado</Th><Th> </Th></tr></thead>
            <tbody>
              {pedidos.map((p) => (
                <tr key={p.id} className="hover:bg-paper2/50 transition-colors cursor-pointer" onClick={() => setSel(sel === p.id ? null : p.id)}>
                  <Td>
                    <span className="font-mono text-[12px] font-semibold">{p.code}</span>
                    <span className="block text-[10.5px] text-stone">{p.fecha}</span>
                  </Td>
                  <Td>
                    <span className="font-medium">{p.nombre}</span>
                    <span className="block text-[10.5px] text-stone">{p.email}</span>
                  </Td>
                  <Td className="tnum">{p.items.reduce((a, i) => a + i.qty, 0)}</Td>
                  <Td className="tnum font-semibold">{fmt2(p.total)}</Td>
                  <Td>{chipEstado(p.estado)}</Td>
                  <Td><I n="chev-r" s={14} className={`text-stone transition-transform ${sel === p.id ? "rotate-90" : ""}`} /></Td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {pedidoSel && (
        <div className="px-5 py-5 border-t border-line fade-in">
          <div className="flex flex-wrap items-center justify-between gap-3 mb-1">
            <p className="font-bold text-[14px]">Seguimiento de {pedidoSel.code}</p>
            <a href={`#/pedido/${pedidoSel.tracking}`} className="text-[11.5px] font-semibold text-maroon u-grow flex items-center gap-1.5"><I n="eye" s={12} /> Ver como cliente</a>
          </div>
          <Timeline estado={pedidoSel.estado} historial={pedidoSel.historial} />
          <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
            <p className="text-[12px] text-stone">Entrega: {pedidoSel.direccion}, {pedidoSel.ciudad} · Pago: {pedidoSel.metodo === "link" ? "Link PayPhone" : "Tarjeta"}</p>
            {pedidoSel.estado < TRACK_STATES.length - 1 ? (
              <button onClick={() => avanzar(pedidoSel)} className={`${btnDark} !py-2.5 text-[12.5px]`}>
                <I n="arrow" s={13} /> Avanzar a «{TRACK_STATES[pedidoSel.estado + 1]}» y notificar al cliente
              </button>
            ) : (
              <span className="text-[12px] font-semibold text-ok flex items-center gap-1.5"><I n="check" s={13} /> Pedido entregado</span>
            )}
          </div>
        </div>
      )}
    </Card>
  );
}
