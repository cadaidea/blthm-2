# BLETIA · Estado del proyecto (backup documental)

> Este archivo documenta el estado funcional del proyecto. **No perder**: toda mejora nueva
> se agrega SOBRE este estado, sin reescribir lo que ya funciona.

## 1. Inventario de lo implementado (estado actual)

### Tienda pública (Geomanist · bletia.ec)
- Apertura editorial con Butaca Aura (Ken Burns), tarjeta de producto superpuesta, marquee de materiales
- Colección: 7 productos, filtros por categoría, quick view con specs, desglose base/IVA 15%
- Carrito persistente (localStorage) con cantidades y totales
- Checkout PayPhone: link de un solo uso (24 h) y pago directo (diferido), confirmación con
  código de orden + link privado de seguimiento de un uso
- Secciones: Taller (proceso 4 pasos), Servicios (guante blanco, garantía, proyectos), Diario, footer legal EC

### Panel interno (Inter · /dash → login con logo BLETIA)
- **Roles**: Gerencia (los 13 módulos), Ventas, Taller, Logística, Contabilidad — cada uno ve solo su área.
  El dueño entra automáticamente como Gerencia (sesión v2); colaboradores vía menú del avatar.
- **Contraste del dash** claro/oscuro (solo panel, la tienda siempre va en claro)
- **Bienvenida editable** ("inspírate · editar mensaje", persiste)
- **14 módulos en 3 grupos** (5 + 5 + 4 + encabezados = 17 filas en sidebar):
  - Operación: Panel de control (bus de eventos +2.000 ev/s, prueba de carga, cola Redis),
    Pedidos OMS (**máquina de 15 estados** + specs con foto etiquetada por campo),
    Logística Guías SRI (49 dígitos + etiquetas de bulto), Taller MES, BOM & MRP
  - Cliente & catálogo: Clientes CRM (búsqueda, segmentos, ficha, línea de tiempo, link PayPhone),
    Proveedores SRM, Cobros PayPhone, Productos PIM, Fototeca DAM (aprobación + subida + preview)
  - Finanzas & sistema: Contabilidad SRI (KPIs, flujo de caja, facturas 49 dígitos, CSV),
    Accesos de un uso (generador, revocar), Seguridad LOPDP, Ajustes & despliegue
    (stack, OVH zip/GitHub, flujo staging→main, `.gitignore` descargable)

### Diseño y datos
- `index.css`: sistema TALLER UNO — Geomanist (público) / Inter (dash), `font-display: swap`,
  paleta paper/ink/wine(#800000)/pine/oak/steel/brick/moss, modo oscuro `.dark`, animaciones
- `data.ts`: semilla completa + mapa `IMG` (9 fotos remotas; en producción: `public/img/`)
- Ruta: `#/dash` y `#/dash/login` → panel; `#/panel` alias interno

## 2. Comparativa con las 33 tasks de github.com/cadaidea/blthm (rama ac8f5)

| # | Task (fecha/hora EC) | Estado aquí |
|---|---|---|
| 1–6 | Snapshots iniciales del ERP | ✅ portado |
| 7 | "Tenemos algo ahora" (bletia/ legacy Laravel + .env + SQL) | ⛔ no se porta (contiene secretos; es el origen del porting) |
| 8–25 | Construcción de los 13 módulos | ✅ portado (14 pantallas) |
| 26 | Tipografía Geomanist/Inter + fondos oscuros + diseño | ✅ portado (index.css) |
| 27 | date-fns FP (solo commiteó node_modules) | ⛔ ruido — no aporta código de app |
| 28 | .gitignore de build artifacts | ✅ cubierto (botón .gitignore correcto en Ajustes) |
| 29 | **Validación cédula/RUC (Módulo 10/11) + consulta por documento + caché 5 min + modo offline + DOCUMENTACION_CEDULA_RUC.md** | ❌ **FALTA** (se había revertido) → se reimplementa |
| 30 | "Open Preview" + gitignore de Python | ⛔ artefacto del sandbox; gitignore Python incorrecto para Node |
| README | **Contabilidad: partida doble + Formulario 104** | ❌ **FALTA** → se agrega al módulo Contabilidad |

## 3. Decisiones de esta ronda (completadas ✅)
1. ✅ Reintegrada la task 29 (SRI): `utils/sri.ts` + Consulta por documento en CRM (caché 5 min, modo
   offline) + validación en alta de clientes y checkout + `DOCUMENTACION_CEDULA_RUC.md`.
2. ✅ Contabilidad completada: pestañas **Facturas SRI** (por defecto) / **Partida doble** (libro diario
   cuadrado Debe=Haber) / **Formulario 104** (borrador calculado).
3. ✅ Datos semilla con documentos matemáticamente válidos (coherentes con el validador).
4. ✅ Nada de lo documentado en la sección 1 se modificó (roles, auto-Gerencia, imágenes, 14 módulos intactos).
