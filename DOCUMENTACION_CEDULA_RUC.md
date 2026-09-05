# Validación de Cédula / RUC — BLETIA (Ecuador)

Implementación: `src/utils/sri.ts` · Ref: Resolución NAC-DGERCGC10-00320 (SRI)

## Algoritmos

| Documento | Estructura | Algoritmo | Verificador |
|---|---|---|---|
| Cédula | 10 dígitos · prov. 01–24 o 30 · 3.er dígito 0–5 | **Módulo 10** — coeficientes `2,1,2,1,2,1,2,1,2` sobre los 9 primeros (producto > 9 → restar 9) | 10.º dígito = `(10 − Σ mod 10) mod 10` |
| RUC persona natural | 13 dígitos · 3.er dígito 0–5 | Módulo 10 sobre los 10 primeros (= cédula) + sufijo de establecimiento | 10.º dígito |
| RUC sociedad privada | 13 dígitos · 3.er dígito = 9 | **Módulo 11** — coeficientes `4,3,2,7,6,5,4,3,2` sobre los 9 primeros | 10.º dígito = `11 − (Σ mod 11)` (11→0; 10→inválido) |
| RUC entidad pública | 13 dígitos · 3.er dígito = 6 | **Módulo 11** — coeficientes `3,2,7,6,5,4,3,2` sobre los 8 primeros | 9.º dígito |

`detectarDocumento()` limpia el ingreso (espacios, puntos, guiones), detecta el tipo
automáticamente por longitud y 3.er dígito, y aplica la validación estricta local.

## Uso en la plataforma

- **CRM → "Consulta por documento"**: valida, detecta el tipo y cruza contra la base de
  clientes. Respuestas en caché con **TTL de 5 minutos** (`TTL_CACHE_MS`) y botón de
  limpieza manual. Conmutador **SRI en línea / offline**: en offline se omite la consulta
  externa y se aplica solo la validación local estricta, con aviso visible.
- **CRM → Nuevo cliente**: el campo Cédula/RUC valida en vivo y bloquea documentos
  con dígito verificador incorrecto.
- **Tienda → Checkout**: el mismo validador exige cédula/RUC válidos antes de emitir
  el comprobante y la factura electrónica.

## Vectores de prueba

| Documento | Tipo | Resultado |
|---|---|---|
| `1710034065` | Cédula | ✅ válido |
| `1714552203001` | RUC natural | ✅ válido |
| `1791228847001` | RUC sociedad | ✅ válido |
| `1710034066` | Cédula | ❌ verificador no coincide |

## LOPDP

Los documentos se tratan como dato personal: nunca se registran en logs del bus de
eventos, solo su hash para deduplicación (ver módulo Seguridad & Porting).
