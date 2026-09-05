import { useMemo, useState } from "react";
import {
  ATRIBUTOS, BODEGAS, FORMULARIOS_SEED, LISTAS_SEED, MOV_STOCK_SEED, PRODUCTS,
  SUSCRIPTORES_SEED, VARIANTES_SEED, fmt2, nombreOpcion,
  type MovStock, type Suscriptor, type Variante,
} from "../../data";
import { I } from "../ui";
import { Bar, Card, Chip, SectionTitle, Stat, Td, Th, btnDark, btnGhost, inp } from "./pui";
import { StatusChip } from "./Panel";

const toneEstado: Record<Suscriptor["estado"], "ok" | "warn" | "bad" | "neutral"> = {
  Confirmado: "ok", Pendiente: "warn", Baja: "neutral", Rebotado: "bad",
};

/* ================= Variantes de producto ================= */
export function Variantes() {
  const [variantes, setVariantes] = useState<Variante[]>(VARIANTES_SEED);
  const [prodId, setProdId] = useState(PRODUCTS[0].id);

  const prod = PRODUCTS.find((p) => p.id === prodId)!;
  const delProd = variantes.filter((v) => v.productoId === prodId);
  const atributosUsados = ATRIBUTOS.filter((a) => delProd.some((v) => a.id in v.opciones));

  const margen = (v: Variante) => (v.pvp > 0 ? ((v.pvp - v.costo) / v.pvp) * 100 : 0);

  const agregar = () => {
    const primeras: Record<string, string> = {};
    atributosUsados.forEach((a) => (primeras[a.id] = a.opciones[0]?.id ?? ""));
    setVariantes((vs) => [...vs, { id: `v${Date.now()}`, productoId: prodId, opciones: primeras, pvp: prod.price, costo: Math.round(prod.price * 0.55) }]);
  };
  const editar = (id: string, campo: "pvp" | "costo", val: string) =>
    setVariantes((vs) => vs.map((v) => (v.id === id ? { ...v, [campo]: parseFloat(val) || 0 } : v)));
  const quitar = (id: string) => setVariantes((vs) => vs.filter((v) => v.id !== id));

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Variables & variantes de producto"
        sub="Cada variante es una combinación vendible con PVP, costo y foto. El selector de la tienda las arma al instante."
        right={
          <div className="flex gap-2">
            <select value={prodId} onChange={(e) => setProdId(e.target.value)} className={inp}>
              {PRODUCTS.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
            </select>
            <button onClick={agregar} className={btnDark}><I n="plus" s={14} /> Variante</button>
          </div>
        }
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Biblioteca de atributos" value={ATRIBUTOS.length} sub="Tapiz · Lado · Acabado" />
        <Stat label="Variantes de esta pieza" value={delProd.length} sub={prod.name} />
        <Stat label="Margen promedio" value={`${(delProd.length ? delProd.reduce((a, v) => a + margen(v), 0) / delProd.length : 0).toFixed(0)}%`} sub="PVP vs costo" />
        <Stat label="Modelo" value="Combinación" sub="PVP + costo + foto por fila" />
      </div>

      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[820px]">
            <thead><tr><Th>Combinación</Th><Th>PVP (IVA incl.)</Th><Th>Costo</Th><Th>Margen</Th><Th>Foto</Th><Th> </Th></tr></thead>
            <tbody>
              {delProd.map((v) => (
                <tr key={v.id} className="hover:bg-paper2/50 transition-colors fade-in">
                  <Td>
                    <span className="flex flex-wrap gap-1.5">
                      {Object.entries(v.opciones).map(([aid, oid]) => {
                        const atr = ATRIBUTOS.find((a) => a.id === aid);
                        const op = atr?.opciones.find((o) => o.id === oid);
                        return (
                          <span key={aid} className="inline-flex items-center gap-1.5 text-[11.5px] font-medium bg-paper2 px-2 py-1">
                            {op?.color && <span className="w-3 h-3 rounded-full border border-line" style={{ background: op.color }} />}
                            <span className="text-stone">{atr?.nombre}:</span> {op?.valor}
                          </span>
                        );
                      })}
                    </span>
                  </Td>
                  <Td><input type="number" value={v.pvp} onChange={(e) => editar(v.id, "pvp", e.target.value)} className={`${inp} !py-1.5 w-24 tnum`} /></Td>
                  <Td><input type="number" value={v.costo} onChange={(e) => editar(v.id, "costo", e.target.value)} className={`${inp} !py-1.5 w-24 tnum`} /></Td>
                  <Td>
                    <span className={`text-[12px] font-semibold tnum ${margen(v) < 40 ? "text-warn" : "text-ok"}`}>{margen(v).toFixed(0)}%</span>
                  </Td>
                  <Td><span className="text-[11px] text-stone flex items-center gap-1"><I n="image" s={13} /> foto.jpg</span></Td>
                  <Td>
                    <button onClick={() => quitar(v.id)} className="text-stone hover:text-bad transition-colors p-1" aria-label="Eliminar"><I n="close" s={14} /></button>
                  </Td>
                </tr>
              ))}
            </tbody>
          </table>
          {delProd.length === 0 && (
            <div className="p-10 text-center">
              <p className="text-[13.5px] text-stone">«{prod.name}» no tiene variantes: se vende como pieza única a {fmt2(prod.price)}.</p>
              <button onClick={agregar} className={`${btnGhost} mt-4`}><I n="plus" s={14} /> Crear primera variante</button>
            </div>
          )}
        </div>
        <div className="px-4 py-3 border-t border-line text-[11.5px] text-stone flex items-center gap-2">
          <I n="tag" s={13} />
          En la tienda, el cliente elige cada atributo; cuando la combinación coincide con una fila, se fija su PVP, foto y disponibilidad.
        </div>
      </Card>

      <Card className="p-5">
        <h3 className="font-bold text-[15px] tracking-tight mb-4">Biblioteca de atributos</h3>
        <div className="grid sm:grid-cols-3 gap-4">
          {ATRIBUTOS.map((a) => (
            <div key={a.id} className="border border-line p-4">
              <p className="text-[13px] font-bold">{a.nombre}</p>
              <p className="text-[10.5px] uppercase tracking-wider text-stone font-semibold mt-0.5">{a.tipo}</p>
              <div className="flex flex-wrap gap-1.5 mt-3">
                {a.opciones.map((o) => (
                  <span key={o.id} className="inline-flex items-center gap-1.5 text-[11.5px] bg-paper2 px-2 py-1">
                    {o.color && <span className="w-3 h-3 rounded-full border border-line" style={{ background: o.color }} />}
                    {o.valor}
                  </span>
                ))}
              </div>
            </div>
          ))}
        </div>
      </Card>
    </div>
  );
}

/* ================= Marketing ================= */
export function Marketing() {
  const [subs, setSubs] = useState<Suscriptor[]>(SUSCRIPTORES_SEED);
  const [listas] = useState(LISTAS_SEED);
  const [forms, setForms] = useState(FORMULARIOS_SEED);
  const [tab, setTab] = useState<"suscriptores" | "listas" | "formularios">("suscriptores");
  const [filtro, setFiltro] = useState<"Todos" | Suscriptor["estado"]>("Todos");

  const mostrados = filtro === "Todos" ? subs : subs.filter((s) => s.estado === filtro);
  const confirmar = (id: string) => setSubs((ss) => ss.map((s) => (s.id === id ? { ...s, estado: "Confirmado" } : s)));
  const darBaja = (id: string) => setSubs((ss) => ss.map((s) => (s.id === id ? { ...s, estado: "Baja" } : s)));
  const toggleForm = (id: string) => setForms((fs) => fs.map((f) => (f.id === id ? { ...f, activo: !f.activo } : f)));

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Marketing · Digest"
        sub="Suscriptores con opt-in doble, listas segmentadas y formularios de captura. Envío vía Brevo (SMTP)."
        right={
          <div className="flex gap-1.5">
            {([["suscriptores", "Suscriptores"], ["listas", "Listas"], ["formularios", "Formularios"]] as const).map(([t, l]) => (
              <button key={t} onClick={() => setTab(t)}
                className={`px-3.5 py-2 text-[12px] font-semibold border transition-colors ${tab === t ? "bg-ink text-paper border-ink" : "border-linedark text-ink2 hover:border-ink"}`}>
                {l}
              </button>
            ))}
          </div>
        }
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Confirmados" value={subs.filter((s) => s.estado === "Confirmado").length} sub={<span className="text-ok font-semibold">Reciben campañas</span>} />
        <Stat label="Pendientes (opt-in)" value={subs.filter((s) => s.estado === "Pendiente").length} sub="Esperan confirmación" />
        <Stat label="Listas activas" value={listas.length} sub="Segmentación" />
        <Stat label="Formularios" value={forms.filter((f) => f.activo).length} sub={`${forms.length} en total`} />
      </div>

      {tab === "suscriptores" && (
        <Card className="overflow-hidden">
          <div className="flex flex-wrap gap-1.5 px-4 py-3 border-b border-line">
            {(["Todos", "Confirmado", "Pendiente", "Baja", "Rebotado"] as const).map((f) => (
              <button key={f} onClick={() => setFiltro(f)}
                className={`px-3 py-1.5 text-[11.5px] font-semibold border transition-colors ${filtro === f ? "bg-ink text-paper border-ink" : "border-linedark text-ink2 hover:border-ink"}`}>
                {f}
              </button>
            ))}
          </div>
          <div className="overflow-x-auto">
            <table className="w-full min-w-[760px]">
              <thead><tr><Th>Email</Th><Th>Nombre</Th><Th>Listas</Th><Th>Fuente</Th><Th>Fecha</Th><Th>Estado</Th><Th> </Th></tr></thead>
              <tbody>
                {mostrados.map((s) => (
                  <tr key={s.id} className="hover:bg-paper2/50 transition-colors fade-in">
                    <Td className="font-mono text-[12px]">{s.email}</Td>
                    <Td>{s.nombre}</Td>
                    <Td><div className="flex gap-1 flex-wrap">{s.listas.map((l) => <Chip key={l} tone="neutral">{l}</Chip>)}</div></Td>
                    <Td className="text-[12px] text-stone">{s.fuente}</Td>
                    <Td className="text-[12px] tnum">{s.fecha}</Td>
                    <Td><Chip tone={toneEstado[s.estado]} dot>{s.estado}</Chip></Td>
                    <Td>
                      {s.estado === "Pendiente" && (
                        <button onClick={() => confirmar(s.id)} className="text-[11.5px] font-semibold px-3 py-1.5 border border-linedark text-ok hover:bg-ok hover:text-paper hover:border-ok transition-colors">Confirmar</button>
                      )}
                      {s.estado === "Confirmado" && (
                        <button onClick={() => darBaja(s.id)} className="text-[11.5px] font-semibold px-3 py-1.5 border border-linedark text-stone hover:bg-ink hover:text-paper transition-colors">Baja</button>
                      )}
                    </Td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      )}

      {tab === "listas" && (
        <div className="grid sm:grid-cols-3 gap-4">
          {listas.map((l) => (
            <Card key={l.id} className="p-5">
              <p className="font-bold text-[15px]">{l.nombre}</p>
              <p className="text-[11px] font-mono text-stone mt-0.5">/{l.slug}</p>
              <p className="font-display font-medium text-[26px] mt-3 tnum">{l.suscriptores}</p>
              <p className="text-[11.5px] text-stone">suscriptores</p>
            </Card>
          ))}
        </div>
      )}

      {tab === "formularios" && (
        <Card className="overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[680px]">
              <thead><tr><Th>Formulario</Th><Th>Tipo</Th><Th>Listas</Th><Th>Estado</Th><Th> </Th></tr></thead>
              <tbody>
                {forms.map((f) => (
                  <tr key={f.id} className="hover:bg-paper2/50 transition-colors">
                    <Td className="font-medium">{f.nombre}</Td>
                    <Td><Chip tone="neutral">{f.tipo}</Chip></Td>
                    <Td><div className="flex gap-1 flex-wrap">{f.listas.map((l) => <Chip key={l} tone="neutral">{l}</Chip>)}</div></Td>
                    <Td><Chip tone={f.activo ? "ok" : "neutral"} dot>{f.activo ? "Activo" : "Apagado"}</Chip></Td>
                    <Td>
                      <button onClick={() => toggleForm(f.id)} className={`${btnGhost} !py-1.5 text-[11.5px]`}>{f.activo ? "Apagar" : "Activar"}</button>
                    </Td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      )}
    </div>
  );
}

/* ================= Stock & Bodegas ================= */
const toneMov: Record<MovStock["tipo"], "ok" | "bad" | "warn"> = { Entrada: "ok", Salida: "bad", Ajuste: "warn" };

export function Stock() {
  const [movs] = useState<MovStock[]>(MOV_STOCK_SEED);

  const porBodega = useMemo(
    () => BODEGAS.map((b) => ({ b, unidades: movs.filter((m) => m.bodega === b).reduce((a, m) => a + m.qty, 0) })),
    [movs],
  );

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Stock & bodegas"
        sub="Movimientos de entrada, salida y ajuste aplicados al stock de cada bodega. Venta y despacho descuentan solos."
      />

      <div className="grid sm:grid-cols-3 gap-4">
        {porBodega.map(({ b, unidades }) => (
          <Card key={b} className="p-5">
            <p className="text-[10.5px] font-bold tracking-[0.16em] uppercase text-stone">{b}</p>
            <p className={`font-display font-medium text-[28px] mt-2 tnum ${unidades < 0 ? "text-warn" : ""}`}>{unidades}</p>
            <p className="text-[11.5px] text-stone">unidades netas (movimientos)</p>
            <div className="mt-3"><Bar value={Math.min(Math.abs(unidades) * 8, 100)} tone={unidades < 0 ? "warn" : "ok"} /></div>
          </Card>
        ))}
      </div>

      <Card className="overflow-hidden">
        <div className="px-5 py-4 border-b border-line flex items-center justify-between">
          <h3 className="font-bold text-[15px] tracking-tight">Movimientos de stock</h3>
          <button className={btnDark}><I n="plus" s={14} /> Registrar movimiento</button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full min-w-[780px]">
            <thead><tr><Th>Fecha</Th><Th>Tipo</Th><Th>SKU · Pieza</Th><Th>Bodega</Th><Th>Cant.</Th><Th>Motivo</Th></tr></thead>
            <tbody>
              {movs.map((m) => (
                <tr key={m.id} className="hover:bg-paper2/50 transition-colors fade-in">
                  <Td className="tnum text-[12px]">{m.fecha}</Td>
                  <Td><Chip tone={toneMov[m.tipo]}>{m.tipo}</Chip></Td>
                  <Td><span className="font-mono text-[11.5px] text-stone">{m.sku}</span> <span className="font-medium">{m.pieza}</span></Td>
                  <Td className="text-[12.5px]">{m.bodega}</Td>
                  <Td className={`tnum font-bold ${m.qty < 0 ? "text-bad" : "text-ok"}`}>{m.qty > 0 ? `+${m.qty}` : m.qty}</Td>
                  <Td className="text-[12px] text-ink2">{m.motivo}</Td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="px-4 py-3 border-t border-line text-[11.5px] text-stone flex items-center gap-2">
          <I n="box" s={13} />
          Al despachar un pedido pagado, el OMS genera las salidas de stock automáticamente y actualiza cada bodega.
        </div>
      </Card>
    </div>
  );
}

/* helper usado en vitrina */
export { nombreOpcion };
