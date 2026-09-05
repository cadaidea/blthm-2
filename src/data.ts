/* =========================================================
   BLETIA · Datos semilla (demo funcional del stack)
   Moneda: USD (Ecuador) · IVA 15% · Facturación SRI
   Cédulas/RUC calculados con Módulo 10/11 reales (utils/sri.ts)
   ========================================================= */

export const IVA = 0.15;

export const fmt = (n: number): string =>
  "$" + Math.round(n).toLocaleString("es-EC");

export const fmt2 = (n: number): string =>
  "$" + n.toLocaleString("es-EC", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/* ---------- Imágenes del catálogo ----------
   En producción: reemplaza cada URL por /img/<nombre>.jpg servido desde tu VPS
   (coloca las fotos reales en public/img/ y cambia solo este mapa). */
export const IMG = {
  hero: "https://image.qwenlm.ai/generated-images/09da4d6b-8efb-4034-abd5-a944d06e99e8/_result.png",
  sofa: "https://image.qwenlm.ai/generated-images/15788a49-b3f6-4714-b2c6-fe2c2375329b/_result.png",
  mesa: "https://image.qwenlm.ai/generated-images/0a21d224-ed62-46b0-b372-2528ba5e6c07/_result.png",
  estanteria: "https://image.qwenlm.ai/generated-images/c5cd9da4-c0db-4188-8385-f4aa3760edc3/_result.png",
  silla: "https://image.qwenlm.ai/generated-images/1910365a-0b2f-4bca-a629-61765c90981d/_result.png",
  cama: "https://image.qwenlm.ai/generated-images/6ba1afba-097a-4471-82d1-ee268df9a2a4/_result.png",
  aparador: "https://image.qwenlm.ai/generated-images/565de814-33d0-46c0-8f6f-1bf68cb12434/_result.png",
  taller: "https://image.qwenlm.ai/generated-images/02bb7b91-83a7-4a3f-bde3-6e927c3b8951/_result.png",
  detalle: "https://image.qwenlm.ai/generated-images/7a099c07-d65e-481b-97d3-b1fbd3a433bf/_result.png",
};

/* ---------- Catálogo (PIM) ---------- */
export type Product = {
  id: string;
  sku: string;
  name: string;
  category: "Asientos" | "Mesas" | "Almacenaje" | "Descanso";
  price: number; // precio final, IVA incluido
  material: string;
  dims: string;
  img: string;
  stock: number;
  state: "Publicado" | "Borrador" | "En taller";
  origin: "Taller BLETIA" | "Proveedor";
  lead: string;
  desc: string;
  channels: string[];
};

export const PRODUCTS: Product[] = [
  {
    id: "p1", sku: "BLT-101", name: "Butaca Aura", category: "Asientos",
    price: 1190, material: "Nogal americano · Bouclé crudo", dims: "78 × 82 × 74 cm",
    img: IMG.hero, stock: 6, state: "Publicado", origin: "Taller BLETIA", lead: "3 semanas",
    desc: "Curva continua tallada en nogal, cojín en bouclé de lana. Ensamble de espiga a la vista, sin herrajes. Serie numerada y firmada por el maestro de taller.",
    channels: ["Web", "Showroom", "Catálogo"],
  },
  {
    id: "p2", sku: "BLT-204", name: "Sofá Nudo", category: "Asientos",
    price: 2890, material: "Lino avena · Patas de nogal", dims: "228 × 95 × 80 cm",
    img: IMG.sofa, stock: 4, state: "Publicado", origin: "Proveedor", lead: "5 semanas",
    desc: "Tres cuerpos, plumón recuperado y espuma de alta densidad. Funda removible lavable. Estructura garantizada por 10 años.",
    channels: ["Web", "Showroom"],
  },
  {
    id: "p3", sku: "BLT-310", name: "Mesa Raíz", category: "Mesas",
    price: 1750, material: "Roble europeo ahumado", dims: "200 × 100 × 75 cm",
    img: IMG.mesa, stock: 3, state: "Publicado", origin: "Taller BLETIA", lead: "4 semanas",
    desc: "Tablero monolítico de roble ahumado con aceite natural. Patas cónicas torneadas a mano. Admite extensión a 260 cm bajo pedido.",
    channels: ["Web", "Showroom", "Catálogo"],
  },
  {
    id: "p4", sku: "BLT-412", name: "Estantería Trama", category: "Almacenaje",
    price: 1320, material: "Nogal · Entrepaños de 18 mm", dims: "160 × 32 × 190 cm",
    img: IMG.estanteria, stock: 8, state: "Publicado", origin: "Proveedor", lead: "2 semanas",
    desc: "Sistema modular de entrepaños flotantes. Soporta 40 kg por nivel. Anclaje antisísmico incluido para pared.",
    channels: ["Web", "Catálogo"],
  },
  {
    id: "p5", sku: "BLT-115", name: "Silla Vela", category: "Asientos",
    price: 420, material: "Nogal · Asiento de cuero vegetalizado", dims: "46 × 52 × 81 cm",
    img: IMG.silla, stock: 24, state: "Publicado", origin: "Taller BLETIA", lead: "2 semanas",
    desc: "Respaldo curvado al vapor, una sola pieza. Cuero de curtiembre local con sello ambiental. Apilable de a dos.",
    channels: ["Web", "Showroom", "Catálogo"],
  },
  {
    id: "p6", sku: "BLT-521", name: "Cama Duna", category: "Descanso",
    price: 2140, material: "Nogal · Cabecero tapizado marfil", dims: "205 × 190 × 95 cm (king)",
    img: IMG.cama, stock: 5, state: "Publicado", origin: "Proveedor", lead: "4 semanas",
    desc: "Plataforma baja sin boxspring. Cabecero flotante tapizado en lino marfil. Ensamble sin herramientas en 10 minutos.",
    channels: ["Web", "Showroom"],
  },
  {
    id: "p7", sku: "BLT-630", name: "Aparador Bruma", category: "Almacenaje",
    price: 1980, material: "Nogal ranurado · Pies de latón", dims: "180 × 45 × 78 cm",
    img: IMG.aparador, stock: 2, state: "En taller", origin: "Taller BLETIA", lead: "5 semanas",
    desc: "Puertas ranuradas a mano, interior en cedro aromático. Bisagras de cierre suave. Stock limitado por serie.",
    channels: ["Showroom"],
  },
];

/* ---------- CRM ---------- */
export type Customer = {
  id: string; name: string; contact: string; city: string;
  segment: "Residencial" | "Arquitecto" | "Hotelero" | "Corporativo";
  orders: number; ltv: number; last: string; doc: string;
  timeline: { date: string; text: string; kind: "pago" | "pedido" | "nota" | "entrega" }[];
};

export const CUSTOMERS: Customer[] = [
  {
    id: "c1", name: "María Fernanda Jaramillo", contact: "mfjaramillo@gmail.com · 099 412 8830",
    city: "Quito", segment: "Residencial", orders: 3, ltv: 5480, last: "hace 2 días", doc: "1714552203001",
    timeline: [
      { date: "12 ene 2026", text: "Pago PayPhone aprobado · $1.190 · Butaca Aura", kind: "pago" },
      { date: "12 ene 2026", text: "Pedido BL-2026-0141 creado", kind: "pedido" },
      { date: "03 feb 2026", text: "Entrega guante blanco en Cumbayá", kind: "entrega" },
    ],
  },
  {
    id: "c2", name: "Estudio Alvarado & Reyes", contact: "proyectos@alvaradoreyes.ec · 098 771 4456",
    city: "Guayaquil", segment: "Arquitecto", orders: 11, ltv: 48900, last: "hoy", doc: "0992334870001",
    timeline: [
      { date: "09 feb 2026", text: "Cotización 14 piezas · Hotel Río Verde", kind: "nota" },
      { date: "28 ene 2026", text: "Pago PayPhone diferido 6 meses · $8.430", kind: "pago" },
    ],
  },
  {
    id: "c3", name: "Hotel Casa del Patio", contact: "gerencia@casadelpatio.ec · 072 844 190",
    city: "Cuenca", segment: "Hotelero", orders: 6, ltv: 31250, last: "hace 1 semana", doc: "0190445528001",
    timeline: [
      { date: "22 ene 2026", text: "Orden de fabricación OF-2210 · 18 sillas Vela", kind: "pedido" },
      { date: "15 ene 2026", text: "Anticipo 50% vía link PayPhone", kind: "pago" },
    ],
  },
  {
    id: "c4", name: "Andrés Valencia", contact: "avalencia@outlook.com · 096 220 1178",
    city: "Manta", segment: "Residencial", orders: 1, ltv: 2890, last: "hace 3 días", doc: "1312884404",
    timeline: [
      { date: "06 feb 2026", text: "Pago PayPhone aprobado · $2.890 · Sofá Nudo", kind: "pago" },
      { date: "06 feb 2026", text: "Asignado a TransCosta Logística", kind: "entrega" },
    ],
  },
  {
    id: "c5", name: "Corporativo Andino S.A.", contact: "compras@corpandino.ec · 023 445 090",
    city: "Quito", segment: "Corporativo", orders: 4, ltv: 19700, last: "hace 2 semanas", doc: "1791228847001",
    timeline: [
      { date: "30 ene 2026", text: "Factura 001-001-000001238 · Autorizada SRI", kind: "nota" },
    ],
  },
  {
    id: "c6", name: "Lucía Briones", contact: "lucia.briones@gmail.com · 099 018 3342",
    city: "Loja", segment: "Residencial", orders: 2, ltv: 2370, last: "hace 5 días", doc: "1104229875",
    timeline: [
      { date: "02 feb 2026", text: "Pedido BL-2026-0146 · Estantería Trama", kind: "pedido" },
    ],
  },
  {
    id: "c7", name: "Boutique Hotel Yaku", contact: "admin@hotelyaku.ec · 042 551 208",
    city: "Guayaquil", segment: "Hotelero", orders: 3, ltv: 22840, last: "hace 1 mes", doc: "0993118801001",
    timeline: [
      { date: "10 ene 2026", text: "Mantenimiento anual de cortesía programado", kind: "nota" },
    ],
  },
];

/* ---------- OMS ---------- */
export type OrderStatus = "Pago pendiente" | "Pago aprobado" | "En taller" | "En transporte" | "Entregado";
export const ORDER_FLOW: OrderStatus[] = ["Pago pendiente", "Pago aprobado", "En taller", "En transporte", "Entregado"];

export type Order = {
  id: string; code: string; customer: string; city: string; item: string;
  total: number; pay: "Link PayPhone" | "Web PayPhone" | "Transferencia";
  status: OrderStatus; carrier: string; date: string;
};

export const ORDERS: Order[] = [
  { id: "o1", code: "BL-2026-0147", customer: "Estudio Alvarado & Reyes", city: "Guayaquil", item: "2 × Mesa Raíz", total: 3500, pay: "Link PayPhone", status: "Pago pendiente", carrier: "—", date: "hoy, 09:41" },
  { id: "o2", code: "BL-2026-0146", customer: "Lucía Briones", city: "Loja", item: "1 × Estantería Trama", total: 1320, pay: "Web PayPhone", status: "Pago aprobado", carrier: "—", date: "hoy, 08:15" },
  { id: "o3", code: "BL-2026-0145", customer: "Hotel Casa del Patio", city: "Cuenca", item: "18 × Silla Vela", total: 7560, pay: "Link PayPhone", status: "En taller", carrier: "—", date: "ayer, 17:02" },
  { id: "o4", code: "BL-2026-0144", customer: "Andrés Valencia", city: "Manta", item: "1 × Sofá Nudo", total: 2890, pay: "Web PayPhone", status: "En transporte", carrier: "TransCosta Logística", date: "06 feb, 11:20" },
  { id: "o5", code: "BL-2026-0143", customer: "Corporativo Andino S.A.", city: "Quito", item: "6 × Silla Vela · 1 × Aparador Bruma", total: 4500, pay: "Link PayPhone", status: "En transporte", carrier: "Sierra Express Carga", date: "05 feb, 15:44" },
  { id: "o6", code: "BL-2026-0141", customer: "María Fernanda Jaramillo", city: "Quito", item: "1 × Butaca Aura", total: 1190, pay: "Web PayPhone", status: "Entregado", carrier: "Flota propia BLETIA", date: "28 ene, 10:05" },
  { id: "o7", code: "BL-2026-0139", customer: "Boutique Hotel Yaku", city: "Guayaquil", item: "4 × Cama Duna", total: 8560, pay: "Transferencia", status: "Entregado", carrier: "TransCosta Logística", date: "21 ene, 09:12" },
];

/* ---------- Proveedores ---------- */
export type Supplier = {
  id: string; name: string; type: "Muebles" | "Transporte"; specialty: string;
  city: string; lead: string; rating: number; active: number; sla: string; contact: string;
};

export const SUPPLIERS: Supplier[] = [
  { id: "s1", name: "Casa Roble Import", type: "Muebles", specialty: "Sofás y camas tapizadas", city: "Guayaquil", lead: "5 semanas", rating: 4.8, active: 2, sla: "98,2% entregas a tiempo", contact: "pedidos@casaroble.ec" },
  { id: "s2", name: "Nórdica EC", type: "Muebles", specialty: "Sistemas de almacenaje modulares", city: "Quito", lead: "2 semanas", rating: 4.6, active: 1, sla: "96,7% entregas a tiempo", contact: "b2b@nordica.ec" },
  { id: "s3", name: "Maderera del Austro", type: "Muebles", specialty: "Tableros de nogal y roble certificado", city: "Cuenca", lead: "10 días", rating: 4.9, active: 3, sla: "99,1% entregas a tiempo", contact: "ventas@maderaustral.ec" },
  { id: "s4", name: "Sierra Express Carga", type: "Transporte", specialty: "Carga consolidada Sierra centro", city: "Quito", lead: "2–4 días", rating: 4.7, active: 3, sla: "97,4% a tiempo · seguro incluido", contact: "ops@sierraexpress.ec" },
  { id: "s5", name: "TransCosta Logística", type: "Transporte", specialty: "Ruta Costa · mudanzas guante blanco", city: "Guayaquil", lead: "3–5 días", rating: 4.5, active: 2, sla: "95,9% a tiempo · GPS en ruta", contact: "cargas@transcosta.ec" },
  { id: "s6", name: "Fletes del Austro", type: "Transporte", specialty: "Última milla Cuenca y Loja", city: "Cuenca", lead: "24–48 h", rating: 4.4, active: 1, sla: "94,8% a tiempo", contact: "despachos@fletesaustro.ec" },
];

/* ---------- Taller (órdenes de fabricación) ---------- */
export type Phase = "Corte" | "Ensamble" | "Acabado" | "Control de calidad";
export const PHASES: Phase[] = ["Corte", "Ensamble", "Acabado", "Control de calidad"];

export type WorkOrder = {
  id: string; ref: string; piece: string; qty: number; order: string;
  phase: number; progress: number; artisan: string; due: string; wood: string;
};

export const WORK_ORDERS: WorkOrder[] = [
  { id: "w1", ref: "OF-2212", piece: "Aparador Bruma · serie 08", qty: 2, order: "Stock taller", phase: 1, progress: 55, artisan: "Maestro E. Cuarán", due: "28 feb 2026", wood: "Nogal americano" },
  { id: "w2", ref: "OF-2210", piece: "Silla Vela", qty: 18, order: "BL-2026-0145 · Hotel Casa del Patio", phase: 0, progress: 22, artisan: "Línea 2 · J. Espinoza", due: "05 mar 2026", wood: "Nogal + cuero" },
  { id: "w3", ref: "OF-2209", piece: "Mesa Raíz 260 cm", qty: 1, order: "BL-2026-0138 · a medida", phase: 2, progress: 80, artisan: "Maestro E. Cuarán", due: "19 feb 2026", wood: "Roble ahumado" },
  { id: "w4", ref: "OF-2207", piece: "Butaca Aura · serie 12", qty: 4, order: "Reposición showroom", phase: 3, progress: 96, artisan: "QC · R. Mena", due: "14 feb 2026", wood: "Nogal americano" },
];

/* ---------- DAM ---------- */
export type Asset = {
  id: string; name: string; img: string; kind: "Fotografía" | "Material" | "Campaña";
  size: string; tags: string[]; status: "Aprobado" | "En revisión"; uses: number; date: string;
};

export const ASSETS: Asset[] = [
  { id: "a1", name: "butaca-aura_editorial_01.jpg", img: IMG.hero, kind: "Fotografía", size: "4,2 MB", tags: ["aura", "web", "hero"], status: "Aprobado", uses: 14, date: "02 feb 2026" },
  { id: "a2", name: "sofa-nudo_frontal.jpg", img: IMG.sofa, kind: "Fotografía", size: "3,8 MB", tags: ["nudo", "web", "catálogo"], status: "Aprobado", uses: 9, date: "02 feb 2026" },
  { id: "a3", name: "mesa-raiz_lateral.jpg", img: IMG.mesa, kind: "Fotografía", size: "3,5 MB", tags: ["raíz", "web"], status: "Aprobado", uses: 7, date: "28 ene 2026" },
  { id: "a4", name: "estanteria-trama_detalle.jpg", img: IMG.estanteria, kind: "Fotografía", size: "4,0 MB", tags: ["trama", "catálogo"], status: "En revisión", uses: 2, date: "05 feb 2026" },
  { id: "a5", name: "silla-vela_perfil.jpg", img: IMG.silla, kind: "Fotografía", size: "3,1 MB", tags: ["vela", "web", "ads"], status: "Aprobado", uses: 18, date: "20 ene 2026" },
  { id: "a6", name: "cama-duna_ambient.jpg", img: IMG.cama, kind: "Fotografía", size: "4,6 MB", tags: ["duna", "web"], status: "Aprobado", uses: 5, date: "22 ene 2026" },
  { id: "a7", name: "aparador-bruma_front.jpg", img: IMG.aparador, kind: "Fotografía", size: "3,9 MB", tags: ["bruma", "showroom"], status: "En revisión", uses: 1, date: "07 feb 2026" },
  { id: "a8", name: "materiales_cuero-nogal.jpg", img: IMG.detalle, kind: "Material", size: "2,7 MB", tags: ["materiales", "blog", "prensa"], status: "Aprobado", uses: 11, date: "15 ene 2026" },
  { id: "a9", name: "taller_proceso-sanded.jpg", img: IMG.taller, kind: "Campaña", size: "5,1 MB", tags: ["taller", "blog", "nosotros"], status: "Aprobado", uses: 8, date: "12 ene 2026" },
];

/* ---------- Contabilidad (SRI Ecuador) ---------- */
export type Invoice = {
  id: string; number: string; date: string; customer: string; ruc: string;
  base: number; iva: number; total: number; auth: string; status: "Autorizada" | "En contingencia";
};

export const INVOICES: Invoice[] = [
  { id: "f1", number: "001-001-000001244", date: "08 feb 2026", customer: "Andrés Valencia", ruc: "1312884404", base: 2513.04, iva: 376.96, total: 2890, auth: "080220260113128844041234567891044", status: "Autorizada" },
  { id: "f2", number: "001-001-000001243", date: "06 feb 2026", customer: "Lucía Briones", ruc: "1104229875", base: 1147.83, iva: 172.17, total: 1320, auth: "060220260111042298751234567891043", status: "Autorizada" },
  { id: "f3", number: "001-001-000001242", date: "04 feb 2026", customer: "Hotel Casa del Patio", ruc: "0190445528001", base: 6573.91, iva: 986.09, total: 7560, auth: "040220260101904455281234567891042", status: "Autorizada" },
  { id: "f4", number: "001-001-000001241", date: "01 feb 2026", customer: "Corporativo Andino S.A.", ruc: "1791228847001", base: 3913.04, iva: 586.96, total: 4500, auth: "010220260117912288471234567891041", status: "Autorizada" },
  { id: "f5", number: "001-001-000001240", date: "29 ene 2026", customer: "Estudio Alvarado & Reyes", ruc: "0992334870001", base: 7330.43, iva: 1099.57, total: 8430, auth: "290120260109923348701234567891040", status: "Autorizada" },
  { id: "f6", number: "001-001-000001239", date: "28 ene 2026", customer: "María F. Jaramillo", ruc: "1714552203001", base: 1034.78, iva: 155.22, total: 1190, auth: "280120260117145522031234567891039", status: "Autorizada" },
  { id: "f7", number: "001-001-000001238", date: "21 ene 2026", customer: "Boutique Hotel Yaku", ruc: "0993118801001", base: 7443.48, iva: 1116.52, total: 8560, auth: "210120260109931188011234567891038", status: "En contingencia" },
];

export const CASHFLOW = [
  { m: "Sep", in: 31200, out: 19800 }, { m: "Oct", in: 35800, out: 21400 },
  { m: "Nov", in: 40100, out: 22900 }, { m: "Dic", in: 52400, out: 27600 },
  { m: "Ene", in: 44900, out: 24100 }, { m: "Feb", in: 48600, out: 23200 },
];

/* ---------- Enlaces de un solo uso ---------- */
export type PayLink = {
  id: string; code: string; type: "Pago PayPhone" | "Acceso al panel" | "Catálogo mayorista" | "Seguimiento de pedido";
  who: string; amount: number | null; expires: string; status: "Activo" | "Usado" | "Revocado" | "Expirado";
};

export const LINKS_SEED: PayLink[] = [
  { id: "l1", code: "8FK2-Q9ZD", type: "Pago PayPhone", who: "Estudio Alvarado & Reyes", amount: 3500, expires: "24 h", status: "Activo" },
  { id: "l2", code: "M3TP-W21A", type: "Acceso al panel", who: "R. Burbano · Contador externo", amount: null, expires: "1 uso", status: "Activo" },
  { id: "l3", code: "ZK77-HD4C", type: "Catálogo mayorista", who: "Hotel Casa del Patio", amount: null, expires: "7 días", status: "Usado" },
  { id: "l4", code: "Q1BV-88RN", type: "Seguimiento de pedido", who: "Andrés Valencia · BL-2026-0144", amount: null, expires: "1 uso", status: "Activo" },
  { id: "l5", code: "T5XJ-00PL", type: "Pago PayPhone", who: "Lucía Briones", amount: 1320, expires: "24 h", status: "Usado" },
  { id: "l6", code: "W9CC-3F6M", type: "Acceso al panel", who: "J. Espinoza · Jefe de taller", amount: null, expires: "1 uso", status: "Expirado" },
];

/* ---------- Motor de eventos ---------- */
export const EVENT_TYPES = [
  "pago.payphone.aprobado", "pedido.creado", "oms.estado.actualizado", "pim.precio.sincronizado",
  "crm.cliente.creado", "crm.documento.validado", "dam.asset.procesado", "factura.sri.autorizada",
  "taller.fase.avanzada", "link.uso_registrado", "transporte.gps.ping", "inventario.movimiento",
  "sesion.panel.iniciada",
];

export const CITIES = ["Quito", "Guayaquil", "Cuenca", "Manta", "Ambato", "Loja", "Riobamba", "Ibarra"];

export const randomCode = () => {
  const c = "ABCDEFGHJKLMNPQRSTUVWXYZ0123456789";
  const p = () => Array.from({ length: 4 }, () => c[Math.floor(Math.random() * c.length)]).join("");
  return `${p()}-${p()}`;
};
