/* =========================================================
   BLETIA · Validación de documentos de Ecuador (SRI)
   Cédula / RUC natural → Módulo 10
   RUC sociedad privada → Módulo 11 (coef. 4,3,2,7,6,5,4,3,2)
   RUC entidad pública  → Módulo 11 (coef. 3,2,7,6,5,4,3,2)
   Ref: Resolución NAC-DGERCGC10-00320 · DOCUMENTACION_CEDULA_RUC.md
   ========================================================= */

export type DocType =
  | "Cédula"
  | "RUC persona natural"
  | "RUC sociedad privada"
  | "RUC entidad pública"
  | "Documento";

export interface DocResult {
  limpio: string;
  tipo: DocType;
  valido: boolean;
  detalle: string;
}

/** TTL de la caché de consultas documentales (5 minutos) */
export const TTL_CACHE_MS = 5 * 60 * 1000;

const SOLO_DIGITOS = /^\d+$/;

export function limpiarDoc(raw: string): string {
  return raw.replace(/[\s.\-]/g, "");
}

function provinciaOk(d: string): boolean {
  const prov = parseInt(d.slice(0, 2), 10);
  return (prov >= 1 && prov <= 24) || prov === 30;
}

/** Módulo 10 — cédula (y base del RUC persona natural) */
export function validarCedula(d: string): { ok: boolean; msg: string } {
  if (d.length !== 10) return { ok: false, msg: "La cédula tiene exactamente 10 dígitos" };
  if (!provinciaOk(d)) return { ok: false, msg: `Código de provincia inválido (${d.slice(0, 2)})` };
  if (parseInt(d[2], 10) > 5)
    return { ok: false, msg: "El 3.er dígito debe ser 0–5 (persona natural)" };
  const coefs = [2, 1, 2, 1, 2, 1, 2, 1, 2];
  let suma = 0;
  for (let i = 0; i < 9; i++) {
    let p = parseInt(d[i], 10) * coefs[i];
    if (p > 9) p -= 9;
    suma += p;
  }
  const verif = (10 - (suma % 10)) % 10;
  if (verif !== parseInt(d[9], 10))
    return { ok: false, msg: "Dígito verificador no coincide (Módulo 10)" };
  return { ok: true, msg: "Cédula válida · Módulo 10 verificado" };
}

/** Módulo 11 — RUC sociedad privada (3.er dígito = 9, verificador = 10.º dígito) */
export function validarRucSociedad(d: string): { ok: boolean; msg: string } {
  const coefs = [4, 3, 2, 7, 6, 5, 4, 3, 2];
  let suma = 0;
  for (let i = 0; i < 9; i++) suma += parseInt(d[i], 10) * coefs[i];
  const residuo = suma % 11;
  const verif = residuo === 0 ? 0 : 11 - residuo;
  if (verif === 10) return { ok: false, msg: "RUC sociedad: verificador imposible (Módulo 11)" };
  if (verif !== parseInt(d[9], 10))
    return { ok: false, msg: "Dígito verificador no coincide (Módulo 11)" };
  return { ok: true, msg: "RUC sociedad privada válido · Módulo 11 verificado" };
}

/** Módulo 11 — RUC entidad pública (3.er dígito = 6, verificador = 9.º dígito) */
export function validarRucPublica(d: string): { ok: boolean; msg: string } {
  const coefs = [3, 2, 7, 6, 5, 4, 3, 2];
  let suma = 0;
  for (let i = 0; i < 8; i++) suma += parseInt(d[i], 10) * coefs[i];
  const residuo = suma % 11;
  const verif = residuo === 0 ? 0 : 11 - residuo;
  if (verif === 10) return { ok: false, msg: "RUC pública: verificador imposible (Módulo 11)" };
  if (verif !== parseInt(d[8], 10))
    return { ok: false, msg: "Dígito verificador no coincide (Módulo 11)" };
  return { ok: true, msg: "RUC entidad pública válido · Módulo 11 verificado" };
}

/**
 * Detección automática del tipo de documento + validación estricta local.
 * 10 dígitos → cédula · 13 dígitos → RUC (natural / sociedad / pública según 3.er dígito)
 */
export function detectarDocumento(raw: string): DocResult {
  const limpio = limpiarDoc(raw);
  if (!limpio) return { limpio, tipo: "Documento", valido: false, detalle: "Documento vacío" };
  if (!SOLO_DIGITOS.test(limpio))
    return { limpio, tipo: "Documento", valido: false, detalle: "Solo dígitos: sin guiones, puntos ni letras" };

  if (limpio.length === 10) {
    const v = validarCedula(limpio);
    return { limpio, tipo: "Cédula", valido: v.ok, detalle: v.msg };
  }

  if (limpio.length === 13) {
    if (!provinciaOk(limpio))
      return { limpio, tipo: "RUC persona natural", valido: false, detalle: `Código de provincia inválido (${limpio.slice(0, 2)})` };
    const tercero = parseInt(limpio[2], 10);
    if (tercero < 6) {
      const v = validarCedula(limpio.slice(0, 10));
      return {
        limpio, tipo: "RUC persona natural", valido: v.ok,
        detalle: v.ok ? "RUC natural válido · base cédula verificada (Módulo 10)" : v.msg,
      };
    }
    if (tercero === 6) {
      const v = validarRucPublica(limpio);
      return { limpio, tipo: "RUC entidad pública", valido: v.ok, detalle: v.msg };
    }
    if (tercero === 9) {
      const v = validarRucSociedad(limpio);
      return { limpio, tipo: "RUC sociedad privada", valido: v.ok, detalle: v.msg };
    }
    return { limpio, tipo: "RUC persona natural", valido: false, detalle: "3.er dígito inválido para RUC (esperado 0–5, 6 o 9)" };
  }

  return {
    limpio, tipo: "Documento", valido: false,
    detalle: limpio.length < 10 ? "Faltan dígitos · cédula 10 / RUC 13" : "Longitud inválida · cédula 10 / RUC 13",
  };
}
