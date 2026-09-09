import { useMemo, useState } from "react";
import { fmt2, seed } from "../../data";
import { I, Modal } from "../ui";
import { Bar, Card, Chip, SectionTitle, Stat, Td, Th, btnDark, btnGhost } from "./pui";
import { StatusChip } from "./Panel";

/* ================= Logística · guías de remisión SRI ================= */
type Guia = {
  id: string; num: string; order: string; carrier: string; origin: string; dest: string;
  fecha: string; bultos: number; auth: string; status: "Autorizada" | "En tránsito" | "Entregada";
};
const GUIAS: Guia[] = seed<Guia[]>("bletia-guias", [
  { id: "g1", num: "001-002-000000321", order: "BL-2026-0144", carrier: "TransCosta Logística", origin: "Bodega Quito", dest: "Manta", fecha: "07 feb 2026", bultos: 2, auth: "07022026061793445522001123456789100321", status: "En tránsito" },
  { id: "g2", num: "001-002-000000320", order: "BL-2026-0143", carrier: "Sierra Express Carga", origin: "Bodega Quito", dest: "Quito · entrega local", fecha: "06 feb 2026", bultos: 7, auth: "06022026061791228843001123456789100320", status: "En tránsito" },
  { id: "g3", num: "001-002-000000318", order: "BL-2026-0139", carrier: "TransCosta Logística", origin: "Bodega Quito", dest: "Guayaquil", fecha: "20 ene 2026", bultos: 8, auth: "20012026060993118802001123456789100318", status: "Entregada" },
  { id: "g4", num: "001-002-000000322", order: "BL-2026-0145", carrier: "Fletes del Austro", origin: "Taller BLETIA", dest: "Cuenca", fecha: "hoy", bultos: 18, auth: "—", status: "Autorizada" },
]);

export function Logistica() {
  const [sel, setSel] = useState<Guia | null>(null);
  const enTransito = GUIAS.filter((g) => g.status === "En tránsito").length;

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Logística & guías de remisión"
        sub="Todo despacho lleva su guía SRI (XML + autorización de 49 dígitos) y etiqueta de bulto."
        right={<button className={btnDark}><I n="plus" s={14} /> Nueva guía</button>}
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Guías este mes" value={GUIAS.length} sub="100% con XML generado" />
        <Stat label="En tránsito" value={enTransito} sub="GPS activo en ruta" />
        <Stat label="Bultos movilizados" value={GUIAS.reduce((a, g) => a + g.bultos, 0)} sub="Etiquetados individualmente" />
        <Stat label="Entregas a tiempo" value="97,0%" sub={<span className="text-ok font-semibold">SLA red de transporte</span>} />
      </div>

      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[860px]">
            <thead><tr><Th>N° guía</Th><Th>Pedido</Th><Th>Transportista</Th><Th>Origen → Destino</Th><Th>Bultos</Th><Th>Fecha</Th><Th>Estado</Th><Th> </Th></tr></thead>
            <tbody>
              {GUIAS.map((g) => (
                <tr key={g.id} className="hover:bg-paper2/50 transition-colors">
                  <Td className="font-mono text-[12px] font-semibold whitespace-nowrap">{g.num}</Td>
                  <Td className="font-mono text-[12px]">{g.order}</Td>
                  <Td>{g.carrier}</Td>
                  <Td className="text-[12.5px] text-ink2 whitespace-nowrap">{g.origin} → {g.dest}</Td>
                  <Td className="tnum">{g.bultos}</Td>
                  <Td className="whitespace-nowrap">{g.fecha}</Td>
                  <Td><StatusChip s={g.status === "En tránsito" ? "En transporte" : g.status === "Entregada" ? "Entregado" : "Autorizada"} /></Td>
                  <Td>
                    <button onClick={() => setSel(g)} className={`${btnGhost} !py-1.5 text-[11.5px]`}>
                      <I n="eye" s={13} /> Ver
                    </button>
                  </Td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>

      <Modal open={!!sel} onClose={() => setSel(null)} w="max-w-xl">
        {sel && (
          <div className="p-6 sm:p-8">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="font-bold text-[17px]">Guía de remisión SRI</h3>
                <p className="text-[12.5px] text-stone font-mono mt-0.5">{sel.num}</p>
              </div>
              <button onClick={() => setSel(null)} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={16} /></button>
            </div>
            <dl className="mt-6 space-y-2.5 text-[13px]">
              {[
                ["Pedido", sel.order], ["Transportista", sel.carrier],
                ["Origen", sel.origin], ["Destino", sel.dest],
                ["Bultos", `${sel.bultos} · etiquetados`], ["Fecha", sel.fecha],
              ].map(([k, v]) => (
                <div key={k} className="flex justify-between gap-6 border-b border-line pb-2.5">
                  <dt className="text-stone">{k}</dt><dd className="font-medium text-right">{v}</dd>
                </div>
              ))}
            </dl>
            <div className="mt-5 border border-linedark bg-card p-3.5">
              <p className="text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone">Autorización SRI (49 dígitos)</p>
              <code className="block font-mono text-[11.5px] mt-1.5 break-all text-ink2">{sel.auth}</code>
            </div>
            <div className="flex gap-2.5 mt-5">
              <button className={`${btnGhost} flex-1`}><I n="doc" s={14} /> Descargar XML</button>
              <button className={`${btnDark} flex-1`}><I n="tag" s={14} /> Imprimir etiquetas</button>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}

/* ================= BOM & MRP ================= */
type BomItem = { id: string; comp: string; qty: number; unit: string; cost: number; stock: number; need: number };
const BOM_ITEMS: BomItem[] = seed<BomItem[]>("bletia-bom", [
  { id: "b1", comp: "Tablero nogal 25 mm", qty: 2.4, unit: "m²", cost: 96, stock: 18, need: 43 },
  { id: "b2", comp: "Cuero vegetalizado cognac", qty: 1.6, unit: "m²", cost: 74, stock: 30, need: 29 },
  { id: "b3", comp: "Espiga de haya 8 mm", qty: 32, unit: "ud", cost: 0.4, stock: 900, need: 576 },
  { id: "b4", comp: "Aceite natural mate", qty: 0.35, unit: "L", cost: 22, stock: 12, need: 6 },
  { id: "b5", comp: "Pies de latón torneado", qty: 4, unit: "ud", cost: 9.5, stock: 40, need: 72 },
]);

export function BOM() {
  const rollup = useMemo(
    () => BOM_ITEMS.reduce((a, b) => a + b.qty * b.cost, 0),
    [],
  );
  const faltantes = BOM_ITEMS.filter((b) => b.stock < b.need);

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="BOM & materiales · MRP"
        sub="Lista de materiales de la Silla Vela (18 ud) con roll-up de costos y cálculo de faltantes."
        right={<Chip tone={faltantes.length ? "warn" : "ok"} dot>{faltantes.length ? `${faltantes.length} faltantes` : "Stock completo"}</Chip>}
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Costo material / ud" value={fmt2(rollup)} sub="Roll-up automático del BOM" />
        <Stat label="Margen objetivo" value="44%" sub={`Precio venta ${fmt2(420)}`} />
        <Stat label="Componentes" value={BOM.length} sub="Vinculados a proveedores" />
        <Stat label="Órdenes de compra" value={faltantes.length} sub={<span className={faltantes.length ? "text-warn font-semibold" : "text-ok font-semibold"}>{faltantes.length ? "Por emitir" : "Ninguna pendiente"}</span>} />
      </div>

      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[820px]">
            <thead><tr><Th>Componente</Th><Th>Cant. / ud</Th><Th>Costo unit.</Th><Th>Costo total</Th><Th>Stock</Th><Th>Requerido (18 ud)</Th><Th>Cobertura</Th></tr></thead>
            <tbody>
              {BOM_ITEMS.map((b) => {
                const pct = Math.min((b.stock / b.need) * 100, 100);
                const short = b.stock < b.need;
                return (
                  <tr key={b.id} className="hover:bg-paper2/50 transition-colors">
                    <Td className="font-medium">{b.comp}</Td>
                    <Td className="tnum">{b.qty} {b.unit}</Td>
                    <Td className="tnum">{fmt2(b.cost)}</Td>
                    <Td className="tnum font-semibold">{fmt2(b.qty * b.cost)}</Td>
                    <Td className="tnum">{b.stock} {b.unit}</Td>
                    <Td className={`tnum ${short ? "text-warn font-semibold" : ""}`}>{b.need} {b.unit}</Td>
                    <Td className="w-[160px]">
                      <div className="flex items-center gap-2.5">
                        <Bar value={pct} tone={short ? "warn" : "ok"} />
                        <span className={`text-[11px] tnum shrink-0 ${short ? "text-warn font-semibold" : "text-stone"}`}>{Math.round(pct)}%</span>
                      </div>
                    </Td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
        <div className="px-4 py-3 border-t border-line text-[11.5px] text-stone flex items-center gap-2">
          <I n="hammer" s={13} />
          Al confirmar una orden de fabricación, el MRP descuenta requerimientos del stock y emite alertas de compra a los proveedores.
        </div>
      </Card>
    </div>
  );
}

/* ================= Seguridad & porting · LOPDP ================= */
const CAPAS = [
  { n: "Perímetro", d: "Nginx + Let's Encrypt · TLS 1.3 · cabeceras de seguridad", ok: true },
  { n: "Autenticación", d: "Sesión por rol · enlaces de un solo uso · expiración", ok: true },
  { n: "Autorización", d: "Cada rol solo ve su área · Gerencia audita todo", ok: true },
  { n: "Datos", d: "PostgreSQL cifrado en reposo · respaldos diarios", ok: true },
  { n: "LOPDP", d: "Datos personales de clientes: consentimiento y minimización", ok: false },
];
const PORTING = [
  { area: "Catálogo público", estado: "Completado", pct: 100 },
  { area: "OMS + máquina de 15 estados", estado: "Completado", pct: 100 },
  { area: "Guías de remisión SRI", estado: "Completado", pct: 100 },
  { area: "BOM & MRP", estado: "En curso", pct: 70 },
  { area: "Contabilidad partida doble", estado: "En curso", pct: 55 },
  { area: "Portal del cliente", estado: "Pendiente", pct: 15 },
];

export function Seguridad() {
  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Seguridad & porting"
        sub="Postura por capas, cumplimiento LOPDP (Ecuador) y avance del porting desde Laravel."
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Capas activas" value={`${CAPAS.filter((c) => c.ok).length} / ${CAPAS.length}`} sub="LOPDP en implementación" />
        <Stat label="Enlaces de un uso" value="Consumo atómico" sub="Nunca reutilizables" />
        <Stat label="Datos cifrados" value="100%" sub="En reposo y tránsito" />
        <Stat label="Porting Laravel" value="62%" sub={<span className="text-ok font-semibold">2 áreas completadas</span>} />
      </div>

      <div className="grid xl:grid-cols-2 gap-6">
        <Card className="p-5 sm:p-6">
          <h3 className="font-bold text-[15px] tracking-tight mb-4">Postura por capas</h3>
          <div className="space-y-3">
            {CAPAS.map((c) => (
              <div key={c.n} className="flex items-start gap-3.5 border border-line bg-card p-3.5">
                <span className={`w-6 h-6 shrink-0 flex items-center justify-center ${c.ok ? "bg-okbg text-ok" : "bg-warnbg text-warn"}`}>
                  <I n={c.ok ? "shield" : "alert"} s={14} />
                </span>
                <div>
                  <p className="text-[13.5px] font-semibold flex items-center gap-2">{c.n}
                    <Chip tone={c.ok ? "ok" : "warn"}>{c.ok ? "Activa" : "En curso"}</Chip>
                  </p>
                  <p className="text-[12px] text-stone mt-0.5 leading-relaxed">{c.d}</p>
                </div>
              </div>
            ))}
          </div>
        </Card>

        <Card className="p-5 sm:p-6">
          <h3 className="font-bold text-[15px] tracking-tight mb-4">Porting desde Laravel</h3>
          <div className="space-y-4">
            {PORTING.map((p) => (
              <div key={p.area}>
                <div className="flex justify-between text-[12.5px] mb-1.5">
                  <span className="font-medium">{p.area}</span>
                  <span className="text-stone">{p.estado} · <span className="tnum">{p.pct}%</span></span>
                </div>
                <Bar value={p.pct} tone={p.pct === 100 ? "ok" : p.pct >= 50 ? "maroon" : "warn"} />
              </div>
            ))}
          </div>
          <p className="text-[11.5px] text-stone mt-5 pt-4 border-t border-line leading-relaxed">
            Los módulos ya portados corren sobre el stack open source (React + Fastify + PostgreSQL + Redis).
            El legacy de Laravel permanece en solo-lectura hasta completar la migración.
          </p>
        </Card>
      </div>
    </div>
  );
}

/* ================= Cobros PayPhone ================= */
const COBROS = [
  { id: "cb1", who: "Estudio Alvarado & Reyes", concepto: "Anticipo 50% · Hotel Río Verde", monto: 4215, estado: "Link activo", metodo: "Link un uso" },
  { id: "cb2", who: "Lucía Briones", concepto: "Estantería Trama", monto: 1320, estado: "Aprobado", metodo: "Web directo" },
  { id: "cb3", who: "Hotel Casa del Patio", concepto: "18 × Silla Vela · saldo", monto: 3780, estado: "Link activo", metodo: "Link un uso" },
  { id: "cb4", who: "Andrés Valencia", concepto: "Sofá Nudo · diferido 6m", monto: 2890, estado: "Aprobado", metodo: "Web directo" },
];

export function Cobros() {
  const porCobrar = COBROS.filter((c) => c.estado === "Link activo").reduce((a, c) => a + c.monto, 0);
  const aprobado = COBROS.filter((c) => c.estado === "Aprobado").reduce((a, c) => a + c.monto, 0);

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Cobros · PayPhone"
        sub="Links de un solo uso y pago directo en la web. Cada pago aprobada genera recibo y factura."
        right={<button className={btnDark}><I n="link" s={14} /> Generar link de cobro</button>}
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Por cobrar (links activos)" value={fmt2(porCobrar)} sub={`${COBROS.filter((c) => c.estado === "Link activo").length} links vigentes`} />
        <Stat label="Aprobado esta semana" value={fmt2(aprobado)} sub="Webhook PayPhone al instante" />
        <Stat label="Recibos auto-validados" value={COBROS.filter((c) => c.estado === "Aprobado").length} sub="Conciliados sin tocar nada" />
        <Stat label="Método favorito" value="Link un uso" sub="68% de los cobros" />
      </div>

      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[760px]">
            <thead><tr><Th>Cliente</Th><Th>Concepto</Th><Th>Monto</Th><Th>Método</Th><Th>Estado</Th></tr></thead>
            <tbody>
              {COBROS.map((c) => (
                <tr key={c.id} className="hover:bg-paper2/50 transition-colors">
                  <Td className="font-medium">{c.who}</Td>
                  <Td className="text-ink2">{c.concepto}</Td>
                  <Td className="tnum font-semibold">{fmt2(c.monto)}</Td>
                  <Td><Chip tone={c.metodo === "Link un uso" ? "maroon" : "neutral"}>{c.metodo}</Chip></Td>
                  <Td><StatusChip s={c.estado === "Aprobado" ? "Pago aprobado" : "Pago pendiente"} /></Td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="px-4 py-3 border-t border-line text-[11.5px] text-stone flex items-center gap-2">
          <I n="card" s={13} />
          PayPhone nunca nos expone datos de tarjeta: solo recibimos la confirmación firmada (webhook) y emitimos la factura SRI.
        </div>
      </Card>
    </div>
  );
}
